<?php
//namespace Services; 

use Requests\Requisition\MoveRequisitionItemsRequest;

class RequisitionService
{
    use \Loggable;

    protected Com_requisicionModel $requisicionModel;

    protected Inv_inventarioModel $inventarioModel;

    protected $db;

    public function __construct()
    {
        $this->requisicionModel = new Com_requisicionModel;
        $this->db = $this->requisicionModel->getConexion();
    }

    public function index(): ServiceResponse
    {
        return ServiceResponse::success($this->requisicionModel->getAllRequisitions());
    }

    /**
     * Obtiene la requisición completa (cabecera + partidas) para pintar la UI.
     */
    public function getRequisitionWithDetails(int $requisitionId, int $userId): ServiceResponse
    {
        try {
            // 1. Obtener la cabecera
            $requisition = $this->requisicionModel->getRequisition($requisitionId);

            // 2. Validación de Existencia
            if (!$requisition) {
                throw new \Exception("La requisición #{$requisitionId} no existe.", 404);
            }

            // 3. Validación de Seguridad (IDOR de Lectura)
            // Evita que el Usuario A lea por URL la requisición del Usuario B
            if ((int)$requisition['usuarioid'] !== $userId) {
                throw new \Exception("Security Error: No tienes permisos para ver esta requisición.", 403);
            }

            // 4. Obtener las partidas
            $items = $this->requisicionModel->getRequisitionItems($requisitionId);

            // 5. Ensamblar la respuesta para el Frontend
            // Anidamos las partidas dentro del objeto principal
            $requisition['items'] = $items;

            return ServiceResponse::success(
                $requisition,
                "Requisición recuperada exitosamente.",
                200
            );

        } catch (\Exception $e) {
            $this->logMessage($e, \LogLevel::WARNING, [
                'action' => 'getRequisitionWithDetails',
                'requisition_id' => $requisitionId,
                'user_id' => $userId
            ]);

            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return ServiceResponse::error(message: $e->getMessage(), code: $code);
        }
    }

    public function moveItems(int $sourceRequisitionId, int $userId): ServiceResponse
    {
        $request = new MoveRequisitionItemsRequest();

        try {
            $request->validate();
            $payload = $request->all();
            
            $this->db->beginTransaction();

            // 1. VALIDACIÓN DE COMPLIANCE Y SEGURIDAD (IDOR)
            $sourceReq = $this->requisicionModel->getRequisition($sourceRequisitionId);
            
            if (!$sourceReq) {
                throw new \Exception("La requisición de origen #{$sourceRequisitionId} no existe.", 404);
            }
            if ($sourceReq['estatus'] !== 'borrador') {
                throw new \Exception("Compliance Error: Solo se pueden mover partidas de una requisición en estado DRAFT.", 403);
            }
            if ($sourceReq['usuarioid'] !== $userId) {
                throw new \Exception("Security Error: No tienes permisos sobre este DRAFT.", 403); // Prevención IDOR
            }

            // 2. RESOLVER EL DESTINO (Split o Merge)
            $targetReqId = null;
            if ($payload['create_new'] === true) {
                // Caso: Urgencia o Capex/Opex (Crear nuevo DRAFT)
                $targetReqId = $this->createNewDraft($userId, "Split de Requisición #{$sourceRequisitionId}");
            } else {
                // Caso: Mover a DRAFT existente
                $targetReqId = $payload['target_requisition_id'];
                $targetReq = $this->requisicionModel->getRequisition($targetReqId);
                
                if (!$targetReq) {
                    throw new \Exception("La requisición destino #{$targetReqId} no existe.", 404);
                }
                if ($targetReq['estatus'] !== 'borrador') {
                    throw new \Exception("Compliance Error: El destino también debe ser un DRAFT.", 403);
                }
            }
    
            // 3. MOVER LAS PARTIDAS (Lógica iterativa)
            $itemsErrors = [];
            foreach ($payload['items'] as $item) {

                // A) Leer TODO el registro a la memoria RAM primero (y validamos IDOR de la partida)
                $sourceItemDetails = $this->requisicionModel->getItemDetails($sourceRequisitionId, $item['requisition_item_id']);
                
                if (!$sourceItemDetails) {
                    $itemsErrors[] = $item['requisition_item_id'] . " (No existe o no te pertenece)";
                    continue;
                }

                $currentQty = (float) $sourceItemDetails['cantidad'];
                
                if ($item['qty_to_move'] > $currentQty) {
                    $itemsErrors[] = $item['requisition_item_id'] . " (Stock insuficiente)";
                    continue;
                }
                    
                // B) Restar del Origen (o eliminar si se mueve todo)
                // El registro desaparece de MySQL, pero sigue vivo en la variable $sourceItemDetails
                if ($item['qty_to_move'] == $currentQty) {
                    $this->requisicionModel->deleteItemFromRequisition($item['requisition_item_id']);
                } else {
                    $this->requisicionModel->reduceItemQty($item['requisition_item_id'], $item['qty_to_move']);
                }

                // C) Insertar/Sumar al Destino
                // Pasamos el array de datos en memoria, evitando volver a consultar MySQL
                $this->addItemToRequisition($targetReqId, $sourceItemDetails, $item['qty_to_move']);
            }

            if ($itemsErrors) {
                throw new \Exception("Inventario Error: Intentas mover más cantidad de la que existe en la(s) partida(s): " . implode(',', $itemsErrors) , 400);
            }

            // 4. RECALCULAR MONTOS (Consistencia Financiera)
            $this->requisicionModel->updateEstimatedAmount($sourceRequisitionId);
            $this->requisicionModel->updateEstimatedAmount($targetReqId);

            $this->db->commit();

            return ServiceResponse::success(
                [
                    "source_requisition_id" => $sourceRequisitionId,
                    "target_requisition_id" => $targetReqId,
                ],
                "Partidas movidas exitosamente.",
                200
            );
        }
        catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ServiceResponse::validation(errors: $i->getMessage());
        }
        catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->logMessage($p, \LogLevel::CRITICAL,[
                'action' => 'moveItems',
                'id_user' => $userId
            ]);
            
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
        }
        catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->logMessage($e, \LogLevel::WARNING,[
                'payload' => $payload
            ]);

            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return ServiceResponse::error(message: $e->getMessage(), code: $code);
        }

    }

    /**
     * @param int $requisitionId
     * @param int $itemId
     * @param int $userId
     * @return ServiceResponse
     */
    public function deleteItem(int $requisitionId, int $itemId, int $userId): ServiceResponse
    {
        try {
            $this->db->beginTransaction();

            // 1. VALIDACIÓN DE COMPLIANCE Y SEGURIDAD (IDOR Cabecera)
            $requisition = $this->requisicionModel->getRequisition($requisitionId);
            
            if (!$requisition) {
                throw new \Exception("La requisición #{$requisitionId} no existe.", 404);
            }
            if ($requisition['estatus'] !== 'borrador') {
                throw new \Exception("Compliance Error: Solo se pueden eliminar partidas de una requisición en estado DRAFT.", 403);
            }
            if ($requisition['usuarioid'] !== $userId) {
                throw new \Exception("Security Error: No tienes permisos para modificar esta requisición.", 403);
            }

            // 2. VALIDACIÓN DE EXISTENCIA DE LA PARTIDA (Previene IDOR Partida)
            // Aseguramos que la partida realmente pertenezca a ESTA requisición
            $itemDetails = $this->requisicionModel->getItemDetails($requisitionId, $itemId);
            
            if (!$itemDetails) {
                throw new \Exception("La partida #{$itemId} no existe o no pertenece a esta requisición.", 404);
            }

            // 3. EJECUTAR EL BORRADO
            $deleted = $this->requisicionModel->deleteItemFromRequisition($itemId);

            if (!$deleted) {
                throw new \Exception("Error interno: No se pudo eliminar la partida.", 500);
            }

            // 4. RECALCULAR MONTO ESTIMADO (Consistencia Financiera)
            // Como borramos un item, el total de la cabecera debe disminuir
            $this->requisicionModel->updateEstimatedAmount($requisitionId);

            $this->db->commit();

            return ServiceResponse::success(
                ["deleted_item_id" => $itemId],
                "Partida eliminada exitosamente.",
                200
            );

        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'deleteItem',
                'requisition_id' => $requisitionId,
                'item_id' => $itemId,
                'id_user' => $userId
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($e, \LogLevel::WARNING, [
                'action' => 'deleteItem',
                'requisition_id' => $requisitionId,
                'item_id' => $itemId
            ]);
            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return ServiceResponse::error(message: $e->getMessage(), code: $code);
        }
    }

    /**
     * Crea una nueva cabecera de requisición en estado DRAFT.
     * Es un método helper privado y no maneja su propia transacción.
     *
     * @param int $userId El ID del usuario que crea el DRAFT.
     * @param string $description La descripción para el nuevo DRAFT.
     * @return int El ID de la nueva requisición creada.
     */
    private function createNewDraft(int $userId, string $description): int {
        $headerData = [
            'user_id' => $userId,
            'titulo' => $description,
            'estatus' => 'borrador' // Forzamos el estado a DRAFT
        ];

        // Llamamos a un método del modelo que solo crea la cabecera
        $newRequisitionId = $this->requisicionModel->createHeader($headerData);

        if ($newRequisitionId <= 0) {
            // Esta excepción será capturada por el try/catch de moveItems()
            throw new \Exception('No se pudo crear el nuevo DRAFT de destino.', 500);
        }
        
        $this->requisicionModel->logAudit($newRequisitionId, AuditAction::CREATED, 'Creación automática por split.', $userId);
        
        return $newRequisitionId;
    }

    /**
     * Añade una partida a la requisición destino utilizando los detalles en memoria.
     * 
     * @param int $targetRequisitionId El ID de la requisición destino.
     * @param array $sourceItemDetails Los datos de la partida origen (extraídos previamente).
     * @param float $quantityToAdd La cantidad a mover.
     */
    private function addItemToRequisition(int $targetRequisitionId, array $sourceItemDetails, float $quantityToAdd): bool
    {
        $existingTargetItemId = $this->requisicionModel->findItemByInventarioId($targetRequisitionId, $sourceItemDetails['inventarioid']);

        if ($existingTargetItemId !== null) {
            return $this->requisicionModel->increaseItemQty($existingTargetItemId, $quantityToAdd);
        } else {
            $newItemData = [
                'inventarioid' => $sourceItemDetails['inventarioid'],
                'cantidad' => $quantityToAdd,
                'precio_unitario_estimado' => $sourceItemDetails['precio_unitario_estimado'],
                'notas' => $sourceItemDetails['notas']
            ];
            return $this->requisicionModel->createDetail($targetRequisitionId, $newItemData);
        }
    }
}