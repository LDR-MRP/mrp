<?php
//namespace Services; 

use Requests\Requisition\MoveRequisitionItemsRequest;
use Requests\Requisition\StoreRequisitionRequest;

/**
 * Class RequisitionService
 *
 * @package Services
 * @description Orquesta toda la lógica de negocio para el módulo de Requisiciones de Compra.
 *              Maneja la creación, actualización, cambios de estado y operaciones complejas
 *              como el movimiento de partidas (Split/Merge), asegurando la integridad transaccional (ACID).
 */
class RequisitionService
{
    use \Loggable;

    protected Com_requisicionModel $requisicionModel;

    protected PurchaseOrderService $purchaseOrderService;

    protected readonly Com_ordenCompraModel $ordenCompraModel;

    protected object $db;

    public function __construct()
    {
        $this->requisicionModel = new Com_requisicionModel;
        $this->purchaseOrderService = new PurchaseOrderService;
        $this->ordenCompraModel = new Com_ordenCompraModel;
        $this->db = $this->requisicionModel->getConexion();
    }

    public function index(array $filters, array $userContext): ServiceResponse
    {
        $role = RoleEnum::tryFrom((int)$userContext['rolid']);
        $scope = $role?->getScope() ?? 'propio';

        // APLICACIÓN DE LA MATRIZ DE VISIBILIDAD
        $scopeFilters = match($scope) {
            'propio' => ['usuarioid' => (int)$userContext['id']],
            'planta'  => ['plantaid' => (int)$userContext['plantaid']],
            'total'  => true,
            default  => false
        };

        if(!empty($scopeFilters) && is_array($scopeFilters)) {
            $filters += $scopeFilters;
        }

        return ServiceResponse::success($this->requisicionModel->getAllRequisitions($filters));
    }

    /**
     * Obtiene la requisición completa (cabecera + partidas) para ser mostrada en la UI.
     * Incluye una validación de seguridad para prevenir que un usuario vea requisiciones ajenas (IDOR).
     *
     * @param int $requisitionId ID de la requisición a obtener.
     * @param array $userContext contexto del usuario que solicita la información.
     * @return ServiceResponse Objeto con los datos de la requisición o un error si no se encuentra/no tiene permisos.
     */
    public function getRequisitionWithDetails(int $requisitionId, array $userContext): ServiceResponse
    {
        try {
            $role = RoleEnum::tryFrom((int)$userContext['rolid']);
            $scope = $role?->getScope() ?? 'propio';

            // 1. Obtener la cabecera
            $requisition = $this->requisicionModel->getRequisitionForUpdate($requisitionId);

            // 2. Validación de Existencia
            if (!$requisition) {
                throw new \Exception("La requisición #{$requisitionId} no existe.", 404);
            }

            // APLICACIÓN DE LA MATRIZ DE VISIBILIDAD
            $isAllowed = match($scope) {
                'propio' => (int)$requisition['usuarioid'] === (int)$userContext['id'],
                'planta'  => (int)$requisition['plantaid'] === (int)$userContext['plantaid'],
                'total'  => true,
                default  => false
            };

            // 3. Validación de Seguridad (IDOR de Lectura)
            if (!$isAllowed) {
                return ServiceResponse::error("Security Error: No tienes permisos para ver esta requisición.", 403);
            }

            // 4. Obtener las partidas
            $items = $this->requisicionModel->getRequisitionItems($requisitionId);
            $balances = $this->requisicionModel->getRequisitionBalances($requisitionId);

            // Indexamos los saldos por ID de partida para acceso instantáneo
            $balanceMap = array_column($balances, null, 'idrequisicionarticulo');

            // Enriquecemos el objeto
            $requisition['items'] = array_map(function($item) use ($balanceMap) {
                $b = $balanceMap[$item['idrequisicionarticulo']] ?? null;
                
                $item['qty_comprada']  = (float)($b['cantidad_ya_comprada'] ?? 0);
                $item['qty_pendiente'] = (float)($b['cantidad_pendiente'] ?? $item['cantidad']);
                
                // Calculamos el porcentaje de surtido (0 a 100)
                $item['porcentaje_surtido'] = $item['cantidad'] > 0 
                    ? round(($item['qty_comprada'] / $item['cantidad']) * 100, 2) 
                    : 0;

                return $item;
            }, $items);

            // 5. OBTENER ÓRDENES DE COMPRA RELACIONADAS (The Missing Link)
            // This ensures the frontend knows which POs were generated from this Req.
            $requisition['related_pos'] = $this->ordenCompraModel->getRelatedPOsByRequisition($requisitionId);

            return ServiceResponse::success(
                $requisition,
                "Requisición recuperada exitosamente.",
                200
            );

        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'getRequisitionWithDetails',
                'id_user' => $userContext['id']
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (\Exception $e) {
            $this->logMessage($e, \LogLevel::WARNING, [
                'action' => 'getRequisitionWithDetails',
                'requisition_id' => $requisitionId,
                'user_id' => $userContext['id']
            ]);

            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return ServiceResponse::error(message: $e->getMessage(), code: $code);
        }
    }

    /**
     * Almacena una nueva requisición, aplicando reglas laxas (borrador) o estrictas (enviar a aprobación)
     * basándose en el campo 'action' del payload.
     *
     * @param array $userContext ID del usuario que crea la solicitud.
     * @return ServiceResponse Objeto con el resultado de la operación.
     */
    public function store(array $userContext): ServiceResponse
    {
        $request = new StoreRequisitionRequest();

        try {
            // 1. VALIDACIÓN CONDICIONAL
            $request->validate();
            $payload = $request->all();

            $this->db->beginTransaction();

            // 2. DETERMINAR EL ESTATUS FINAL
            $isSubmit = ($payload['action'] === 'submit_approval');
            $estatusFinal = $isSubmit ? 'pendiente' : 'borrador';

            // 3. CREAR CABECERA
            $headerData = [
                'user_id'         => $userContext['id'],
                'planta_id'       => $userContext['plantaid'],
                'estatus'         => $estatusFinal,
                'titulo'          => $payload['titulo'],
                'tipo_requisicion' => $payload['tipo_requisicion'] ?? 'standard',
                'idmetodopago'     => $payload['idmetodopago'] ?: null,
                'url_referencia'   => $payload['url_referencia'] ?: null,
                'departamentoid'  => !empty($payload['departamentoid']) ? $payload['departamentoid'] : null,
                'fecha_requerida' => !empty($payload['fecha_requerida']) ? $payload['fecha_requerida'] : null,
                'prioridad'       => $payload['prioridad'] ?? 'media',
                'justificacion'   => $payload['justificacion'] ?? '',
                'monto_estimado'  => 0.00 // Inicia en 0, se recalcula al final
            ];

            $requisitionId = $this->requisicionModel->createHeader($headerData);

            if ($requisitionId <= 0) {
                throw new \Exception('Error de integridad al registrar la cabecera de la requisición.', 500);
            }

            // 4. CREAR PARTIDAS (Si existen)
            if (!empty($payload['articulos'])) {
                foreach ($payload['articulos'] as $item) {
                    // Lógica de protección: Si el ID viene vacío o como string '', lo convertimos a NULL real
                    $invId = (!empty($item['inventarioid'])) ? (int)$item['inventarioid'] : null;

                    $itemData = [
                        'inventarioid'             => $invId, // <-- Ahora enviamos NULL real o un entero
                        'cantidad'                 => (float)($item['cantidad'] ?? 0),
                        'precio_unitario_estimado' => (float)($item['precio_unitario_estimado'] ?? 0),
                        'notas'                    => $item['notas'] ?? '',
                        'user_id'                  => $userContext['id']
                    ];
                    
                    // 1. Insertamos la partida y obtenemos su ID
                    $idReqArticulo = $this->requisicionModel->createDetail($requisitionId, $itemData);

                    // --- NUEVA LÓGICA: SOURCING ---
                    // Si no tiene inventarioid y trae el nodo 'specs', guardamos la ficha técnica
                    if (is_null($itemData['inventarioid']) && !empty($item['specs'])) {
                        $specData = $item['specs'];
                        $specData['requisicionid'] = $requisitionId;
                        $specData['idrequisicionarticulo'] = $idReqArticulo;
                        $specData['user_id'] = $userContext['id'];

                        // Llamamos al método que ya procesa el guardado en 'com_requisicion_items_nuevos'
                        // Nota: Se debe asegurar que este método no cierre la transacción
                        $this->persistSpecialSpecs($specData);
                    }
                    // ------------------------------
                }

                // 5. RECALCULAR MONTO ESTIMADO (Consistencia Financiera)
                $this->requisicionModel->updateEstimatedAmount($requisitionId);
            }

            // 6. LOG DE AUDITORÍA
            $auditMsg = $isSubmit ? 'Creada y enviada a aprobación.' : 'Creada como borrador.';
            $this->requisicionModel->logAudit($requisitionId, AuditAction::CREATED, $auditMsg, $userContext['id']);

            $this->db->commit();

            return ServiceResponse::success(
                ['requisicion_id' => $requisitionId],
                $isSubmit ? 'Solicitud enviada a aprobación correctamente.' : 'Borrador guardado exitosamente.',
                201 // 201 Created
            );

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ServiceResponse::validation(errors: $i->getMessage());
            
        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'storeRequisition',
                'id_user' => $userContext['id']
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($e, \LogLevel::WARNING, [
                'action' => 'storeRequisition',
                'payload' => $payload ?? []
            ]);
            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return ServiceResponse::error(message: $e->getMessage(), code: $code);
        }
    }

    /**
     * Actualiza una requisición existente. Solo permite la edición de requisiciones en estado 'borrador'.
     * Realiza una sincronización completa de las partidas (UPSERT lógico y eliminación de huérfanas).
     *
     * @param int $requisitionId ID de la requisición a actualizar.
     * @param array $userContext ID del usuario que realiza la edición.
     * @return ServiceResponse Objeto con el resultado de la operación.
     */
    public function update(int $requisitionId, array $userContext): ServiceResponse
    {
        $request = new \Requests\Requisition\StoreRequisitionRequest();

        try {
            $userId = (int)$userContext['id'];
            $userPlantaId = (int)$userContext['plantaid'];
            $userRolId = (int)$userContext['rolid'];
            $request->validate();
            $payload = $request->all();
            $this->db->beginTransaction();
            $existingReq = $this->requisicionModel->getRequisition($requisitionId);
            
            if (!$existingReq) {
                throw new \Exception("La requisición #{$requisitionId} no existe.", 404);
            }            
            // IMPORTANTE: Solo se puede editar si está en borrador. 
            // Si ya fue enviada a aprobación, está bloqueada.
            if ($existingReq['estatus'] !== 'borrador') {
                throw new \Exception("Compliance Error: No se puede editar una requisición que ya no es un borrador.", 403);
            }

            $role = RoleEnum::tryFrom($userRolId);
            $scope = $role?->getScope() ?? 'propio';
            
            // Aplicación de la matriz de visibilidad
            $isAllowed = match($scope) {
                'propio' => (int)$existingReq['usuarioid'] === $userId,
                'planta'  => (int)$existingReq['plantaid'] === $userPlantaId,
                'total'  => true,
                default  => false
            };

            // Validación de Seguridad
            if (!$isAllowed) {
                return ServiceResponse::error("Security Error: No tienes permisos para editar esta requisición.", 403);
            }

            // Determinar el nuevo estatus final
            $isSubmit = ($payload['action'] === 'submit_approval');
            $estatusFinal = $isSubmit ? 'pendiente' : 'borrador';

            // Actualizar cabecera
            $headerData = [
                'estatus'         => $estatusFinal,
                'titulo'          => $payload['titulo'],
                'tipo_requisicion' => $payload['tipo_requisicion'] ?? 'standard',
                'idmetodopago'     => $payload['idmetodopago'] ?: null,
                'url_referencia'   => $payload['url_referencia'] ?: null,
                'departamentoid'  => !empty($payload['departamentoid']) ? $payload['departamentoid'] : null,
                'fecha_requerida' => !empty($payload['fecha_requerida']) ? $payload['fecha_requerida'] : null,
                'prioridad'       => $payload['prioridad'] ?? 'media',
                'justificacion'   => $payload['justificacion'] ?? ''
            ];

            $headerUpdated = $this->requisicionModel->updateHeader($requisitionId, $headerData);

            // Procesar partidas (UPSERT Lógico)
            if (!empty($payload['articulos'])) {
                
                // Recolectamos los IDs que nos mandó el Frontend para saber cuáles NO borrar
                $incomingItemIds = [];

                foreach ($payload['articulos'] as $item) {
                    $itemId = $item['idrequisicionarticulo'] ?? null;
                    // Lógica de protección: Si el ID viene vacío o como string '', lo convertimos a NULL real
                    $invId = (!empty($item['inventarioid'])) ? (int)$item['inventarioid'] : null;
                    
                    $itemData = [
                        'inventarioid'             => $invId, // <-- Ahora enviamos NULL real o un entero
                        'cantidad'                 => (float)($item['cantidad'] ?? 0),
                        'precio_unitario_estimado' => (float)($item['precio_unitario_estimado'] ?? 0),
                        'notas'                    => $item['notas'] ?? ''
                    ];

                    if (!empty($itemId)) {
                        // A. ACTUALIZAR PARTIDA EXISTENTE
                        // Validar propiedad de la partida por seguridad
                        $existingItem = $this->requisicionModel->getItemDetails($requisitionId, $itemId);
                        if ($existingItem) {
                            $this->requisicionModel->updateDetail($itemId, $itemData);
                            $incomingItemIds[] = $itemId;
                            
                            // --- FIX: ASIGNAR EL ID ACTUAL ---
                            $currentIdArt = (int)$itemId; 
                        }
                    } else {
                        // B. INSERTAR NUEVA PARTIDA
                        $currentIdArt = $this->requisicionModel->createDetail($requisitionId, $itemData);
                    }

                    // --- NUEVA LÓGICA: SOURCING (Edición) ---
                    // Aplicamos el mismo check: si es sourcing y trae specs, persistimos
                    if (is_null($itemData['inventarioid']) && !empty($item['specs'])) {
                        $specData = $item['specs'];
                        $specData['precio_objetivo'] = $itemData['precio_unitario_estimado'];
                        $specData['requisicionid'] = $requisitionId;
                        $specData['idrequisicionarticulo'] = $currentIdArt;
                        $specData['user_id'] = $userId;
                        $this->persistSpecialSpecs($specData);
                    }
                    // ----------------------------------------
                }

            } else {
                // Si el frontend manda un array vacío de artículos, borramos todo el detalle
                $this->requisicionModel->deleteAllItems($requisitionId);
            }

            // Recalcular monto estimado
            $this->requisicionModel->updateEstimatedAmount($requisitionId);

            // Log de auditoría
            $auditMsg = $isSubmit ? 'Editada y enviada a aprobación.' : 'Borrador actualizado.';
            $this->requisicionModel->logAudit($requisitionId, AuditAction::UPDATED, $auditMsg, $userId);

            $this->db->commit();

            return ServiceResponse::success(
                ['requisicion_id' => $requisitionId],
                $isSubmit ? 'Solicitud enviada a aprobación correctamente.' : 'Borrador actualizado exitosamente.',
                200 // 200 OK (Ya existía)
            );

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::validation(errors: $i->getMessage());
        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logMessage($p, \LogLevel::CRITICAL, ['action' => 'updateRequisition', 'id_user' => $userId]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logMessage($e, \LogLevel::WARNING, ['action' => 'updateRequisition', 'payload' => $payload ?? []]);
            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return ServiceResponse::error(message: $e->getMessage(), code: $code);
        }
    }

    /**
     * Mueve partidas específicas entre requisiciones en estado borrador.
     * 
     * Soporta dos flujos:
     * 1. Split: Crea una nueva requisición y mueve las partidas hacia ella.
     * 2. Merge/Transfer: Mueve partidas hacia otra requisición existente.
     * 
     * @param int $sourceRequisitionId ID de la requisición de origen.
     * @param array $userContext ID del usuario autenticado (inyectado por Middleware).
     * 
     * @return ServiceResponse Objeto estandarizado con el resultado de la operación.
     * 
     * @throws \Exception Si el origen no es borrador, si hay violación de IDOR, 
     *                    o si las cantidades a mover exceden la existencia.
     */
    public function moveItems(int $sourceRequisitionId, array $userContext): ServiceResponse
    {
        $request = new MoveRequisitionItemsRequest();
        $request->validate();
        $payload = $request->all();

        try {
            
            $this->db->beginTransaction();

            // 1. VALIDACIÓN DE COMPLIANCE Y SEGURIDAD (IDOR)
            $sourceReq = $this->requisicionModel->getRequisition($sourceRequisitionId);
            
            if (!$sourceReq) {
                throw new \Exception("La requisición de origen #{$sourceRequisitionId} no existe.", 404);
            }
            if ($sourceReq['estatus'] !== 'borrador') {
                throw new \Exception("Compliance Error: Solo se pueden mover partidas de una requisición en estado DRAFT.", 403);
            }
            if ($sourceReq['usuarioid'] !== $userContext['id']) {
                throw new \Exception("Security Error: No tienes permisos sobre este DRAFT.", 403); // Prevención IDOR
            }

            // 2. RESOLVER EL DESTINO (Split o Merge)
            $targetReqId = null;
            if ($payload['create_new'] === true) {
                // Caso: Urgencia o Capex/Opex (Crear nuevo DRAFT)
                $targetReqId = $this->createNewDraft($userContext, "Split de Requisición #{$sourceRequisitionId}");
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
                // --- FIX DE SEGURIDAD (IDOR CRUZADO) ---
                // Validamos que el destino también pertenezca al usuario logueado
                if ((int)$targetReq['usuarioid'] !== $userContext['id']) {
                    throw new \Exception("Security Error: No tienes permisos sobre la requisición destino #{$targetReqId}.", 403);
                }
            }
    
            // 3. MOVER LAS PARTIDAS (Lógica iterativa)
            $itemsErrors = [];
            foreach ($payload['items'] as $item) {

                $idItemOrigen = (int)$item['requisition_item_id'];
                $qtyToMove = (float)$item['qty_to_move'];

                // A) Leer registro bloqueando fila (FOR UPDATE)
                $sourceItemDetails = $this->requisicionModel->getItemDetailsForUpdate($sourceRequisitionId, $idItemOrigen);
                
                if (!$sourceItemDetails) {
                    $itemsErrors[] = $idItemOrigen . " (No existe o no te pertenece)";
                    continue;
                }

                $currentQty = (float) $sourceItemDetails['cantidad'];
                
                if ($item['qty_to_move'] > $currentQty) {
                    $itemsErrors[] = $idItemOrigen . " (Stock insuficiente)";
                    continue;
                }

                // --- INICIO DE LÓGICA DE TRANSFERENCIA DE SOURCING ---

                // B) Insertar/Sumar al Destino PRIMERO
                // IMPORTANTE: addItemToRequisition DEBE devolver el ID insertado en la nueva requisición
                $newIdItemDestino = $this->addItemToRequisition($targetReqId, $sourceItemDetails, $qtyToMove);
                    
                // C) ¿Es un movimiento TOTAL de la partida?
                if ($qtyToMove == $currentQty) {
                    /**
                     * RE-PARENTING: Si movemos todo, el "ADN" del Sourcing (Specs y Cotizaciones) 
                     * debe viajar a la nueva partida antes de borrar la vieja.
                     * Esto soluciona el ERROR 1451.
                     */
                    $this->requisicionModel->transferSourcingData($idItemOrigen, $newIdItemDestino);

                    // Ahora que los hijos ya tienen nuevo padre, borramos al padre original
                    $this->requisicionModel->deleteItemFromRequisition($idItemOrigen);
                } else {
                    /**
                     * Si es movimiento PARCIAL, la partida original sobrevive (reducida).
                     * Por regla de negocio, los hijos (Cotizaciones) se quedan con el remanente 
                     * en el origen, la nueva partida en el destino nace "limpia".
                     */
                    $this->requisicionModel->reduceItemQty($idItemOrigen, $qtyToMove);
                }
                // --- FIN DE LÓGICA DE SOURCING ---
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
                'id_user' => $userContext['id']
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
     * @param array $userContext
     * @return ServiceResponse
     */
    public function deleteItem(int $requisitionId, int $itemId, array $userContext): ServiceResponse
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
            if ($requisition['usuarioid'] !== $userContext['id']) {
                throw new \Exception("Security Error: No tienes permisos para modificar esta requisición.", 403);
            }

            // 2. VALIDACIÓN DE EXISTENCIA DE LA PARTIDA (Previene IDOR Partida)
            // Aseguramos que la partida realmente pertenezca a ESTA requisición
            $itemDetails = $this->requisicionModel->getItemDetails($requisitionId, $itemId);

            // --- INICIO DE INTEGRACIÓN: LIMPIEZA DE SOURCING
            /**
             * Antes de eliminar la partida, debemos limpiar las tablas hijas para evitar el 
             * error de restricción de llave foránea (FK Constraint Violation).
             */
            $this->requisicionModel->deleteSourcingDataByItem($itemId, $userContext['id']);
            // --- FIN DE INTEGRACIÓN ---
            
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
                'id_user' => $userContext['id']
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
     * Cambia el estado de una requisición a 'aprobada'.
     * Solo es válido si el estado actual es 'pendiente'.
     *
     * @param int $requisitionId ID de la requisición a aprobar.
     * @param array $userContext ID del usuario que realiza la aprobación.
     * @return ServiceResponse
     */
    public function approve(int $requisitionId, array $userContext): ServiceResponse
    {
        $request = new \Requests\Requisition\ChangeStatusRequest();

        $role = RoleEnum::tryFrom((int)$userContext['rolid']);

        // 1. ¿Tiene permiso de firma?
        $level = $role?->getApprovalLevel();
        if (!$level) {
            return ServiceResponse::error("Tu rol no tiene facultades de aprobación.", 403);
        }

        $requisition = $this->requisicionModel->getRequisition($requisitionId);

        // 2. Validación de Scope (Jefe Depto/Planta solo aprueba lo suyo)
        if ($role === RoleEnum::JEFE_DEPARTAMENTO && $requisition['plantaid'] !== $userContext['plantaid']) {
            return ServiceResponse::error("Solo puedes aprobar requisiciones de tu propio departamento/planta.", 403);
        }

        // 3. Máquina de Estados L1 -> L2
        // TODO: Determinamos el estado origen y destino basado en quién firma
        $config = match($level) {
            'L1' => [
                'from' => ['pendiente'], 
                'to'   => 'pendiente_l2', // Siguiente nivel
                'msg'  => 'Aprobación L1 (Jefe Depto) completada.'
            ],
            'L2' => [
                'from' => ['pendiente', 'pendiente_l2'], // Puede aprobar directo o tras L1
                'to'   => 'aprobada', 
                'msg'  => 'Aprobación final L2 completada exitosamente.'
            ]
        };
        
        // 4. EJECUTAR CAMBIO DE ESTADO
        $response = $this->changeStatus(
            $requisitionId,
            $userContext,
            'aprobada',
            ['pendiente'], // Solo se puede aprobar si está 'pendiente'
            'Solicitud aprobada.',
            AuditAction::APPROVED,
            "Firma {$level} autorizada por " . $userContext['nombre'] . '. Notas: ' . $request->input('comentario')
        );

        // --- INICIO LÓGICA DE COMPRA DIRECTA (STP) ---
        /**
         * Si la aprobación fue exitosa, el nuevo estado es 'aprobada' 
         * y la requisición es de tipo 'directa', disparamos la automatización.
         */
        if ($response->success && $config['to'] === 'aprobada' && $requisition['tipo_requisicion'] === 'spot_buy') {
            try {
                // Este método lo crearemos a continuación
                $this->purchaseOrderService->processSpotBuyAutomation($requisitionId, $userContext);
                
                $response->message .= " Se ha generado automáticamente la Orden de Compra Directa.";
            } catch (\Exception $e) {
                // Logueamos el error pero no revertimos la aprobación (la requisición ya está aprobada)
                $this->logMessage("Error en Compra Directa: " . $e->getMessage(), \LogLevel::ERROR);
                $response->message .= " Advertencia: No se pudo completar la compra automática.";
            }
        }
        // --- FIN LÓGICA DE COMPRA DIRECTA ---

        return $response;
    }

    /**
     * Cambia el estado de una requisición a 'rechazada'.
     * Solo es válido si el estado actual es 'pendiente'.
     *
     * @param int $requisitionId ID de la requisición a rechazar.
     * @param array $userContext ID del usuario que realiza el rechazo.
     * @return ServiceResponse
     */
    public function reject(int $requisitionId, array $userContext): ServiceResponse
    {
        $request = new \Requests\Requisition\ChangeStatusRequest();
        return $this->changeStatus(
            $requisitionId,
            $userContext,
            'rechazada',
            ['pendiente'], // Solo se puede rechazar si está 'pendiente'
            'Solicitud rechazada.',
            AuditAction::REJECTED,
            ucfirst(AuditAction::REJECTED->value) . ':' . $request->input('comentario')
        );
    }

    /**
     * Cambia el estado de una requisición a 'rechazada'.
     * Solo es válido si el estado actual es 'pendiente'.
     *
     * @param int $requisitionId ID de la requisición a rechazar.
     * @param array $userContext ID del usuario que realiza el rechazo.
     * @return ServiceResponse
     */
    public function cancel(int $requisitionId, array $userContext): ServiceResponse
    {
        $request = new \Requests\Requisition\ChangeStatusRequest();
        return $this->changeStatus(
            $requisitionId,
            $userContext,
            'cancelada',
            ['aprobada', 'en compra'], // Solo se puede cancelar si está en 'aprobada' o 'en_compra'
            'Solicitud cancelada.',
            AuditAction::CANCELED,
            ucfirst(AuditAction::CANCELED->value) . ':' . $request->input('comentario')
        );
    }

    /**
     * Devuelve una requisición 'pendiente' o 'rechazada' al estado de 'borrador' para su corrección.
     *
     * @param int $requisitionId ID de la requisición a devolver.
     * @param array $userContext ID del usuario que realiza la acción.
     * @return ServiceResponse
     */
    public function returnToDraft(int $requisitionId, array $userContext): ServiceResponse
    {
        $request = new \Requests\Requisition\ChangeStatusRequest();
        return $this->changeStatus(
            $requisitionId,
            $userContext,
            'borrador',
            ['pendiente', 'rechazada'], // Se puede devolver si está 'pendiente' o si fue 'rechazada'
            'Solicitud devuelta a borrador.',
            AuditAction::UPDATED,
            'Devuelta a borrador para corrección:' . $request->input('comentario', 'Devuelta a borrador para corrección.')
        );
    }
    
    /**
     * Realiza un borrado lógico (soft delete) de una requisición.
     * Solo se permite eliminar requisiciones en estado 'borrador' o 'pendiente'.
     *
     * @param int $requisitionId ID de la requisición a eliminar.
     * @param array $userContext        ID del usuario que realiza la eliminación.
     * @return ServiceResponse
     */
    public function destroy(int $requisitionId, array $userContext): ServiceResponse
    {
        try {
            $this->db->beginTransaction();

            // 1. VALIDAR EXISTENCIA
            $requisition = $this->requisicionModel->getRequisition($requisitionId);

            if (!$requisition) {
                throw new \Exception("La solicitud #{$requisitionId} no existe.", 404);
            }

            // 2. VALIDAR SEGURIDAD (IDOR)
            if ((int)$requisition['usuarioid'] !== $userContext['id']) {
                throw new \Exception("Security Error: No tienes permisos para eliminar esta solicitud.", 403);
            }

            // 3. VALIDAR MÁQUINA DE ESTADOS (Business Rules)
            // Una vez aprobada o en compras, no se puede borrar (afectaría auditorías contables).
            if (!in_array($requisition['estatus'], ['borrador', 'pendiente'])) {
                throw new \Exception("Acción denegada: No se puede eliminar una solicitud que ya se encuentra en estado '{$requisition['estatus']}'.", 403);
            }

            // 4. EJECUTAR SOFT DELETE
            $deleted = $this->requisicionModel->softDelete($requisitionId);

            if (!$deleted) {
                throw new \Exception("Error interno: No se pudo eliminar la solicitud de la base de datos.", 500);
            }

            // 5. REGISTRAR EN AUDITORÍA
            $this->requisicionModel->logAudit($requisitionId, AuditAction::DELETED, "Solicitud eliminada por el usuario.", $userContext['id']);

            $this->db->commit();

            return ServiceResponse::success(
                ['requisicion_id' => $requisitionId],
                'Solicitud eliminada correctamente.',
                200
            );

        } catch (\PDOException $p) {
            // Manejo de errores a nivel de Base de Datos
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'destroyRequisition',
                'requisition_id' => $requisitionId,
                'id_user' => $userContext['id']
            ]);

            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos al intentar eliminar la solicitud.");
            
        } catch (\Exception $e) {
            // Manejo de errores de Lógica de Negocio y Generales
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->logMessage($e, \LogLevel::WARNING, [
                'action' => 'destroyRequisition',
                'requisition_id' => $requisitionId,
                'id_user' => $userContext['id']
            ]);

            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return ServiceResponse::error(message: $e->getMessage(), code: $code);
        }
    }

    /**
     * Obtiene las partidas de una requisición que aún tienen saldo pendiente por comprar.
     *
     * @param int $requisitionId
     * @param array $userContext
     * @return ServiceResponse
     */
    public function getPendingItemsToPurchase(int $requisitionId, array $userContext): ServiceResponse
    {
        try {
            // 1. Validar existencia y seguridad (IDOR)
            $requisition = $this->requisicionModel->getRequisition($requisitionId);

            if (!$requisition) {
                throw new \Exception("La requisición #{$requisitionId} no existe.", 404);
            }

            // Opcional: Si solo ciertos roles (ej. Compradores) pueden ver esto, valídalo aquí.
            // if (!$this->userHasRole($userContext['id'], 'comprador')) throw new \Exception("No tienes permisos de compras.", 403);

            // 2. Validar Máquina de Estados
            // Solo se puede comprar si ya fue aprobada, o si ya está en proceso de compra (cumplimiento parcial)
            if (!in_array($requisition['estatus'], ['aprobada', 'en compra'])) {
                throw new \Exception("No se pueden generar órdenes de compra para una requisición en estado '{$requisition['estatus']}'.", 403);
            }

            // 3. Obtener los saldos pendientes desde la Base de Datos
            $pendingItems = $this->requisicionModel->getPendingItemsWithSourcing($requisitionId);

            // 4. Retornar la data
            return ServiceResponse::success(
                [
                    'requisicion' => $requisition, // Mandamos la cabecera por si el frontend la necesita
                    'items_pendientes' => $pendingItems
                ],
                "Partidas pendientes calculadas exitosamente.",
                200
            );

        } catch (\PDOException $p) {
            // Manejo de errores a nivel de Base de Datos
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'getPendingItemsToPurchase',
                'requisition_id' => $requisitionId,
                'id_user' => $userContext['id']
            ]);

            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos al intentar eliminar la solicitud.");
            
        } catch (\Exception $e) {
            $this->logMessage($e, \LogLevel::WARNING, [
                'action' => 'getPendingItemsToPurchase',
                'requisition_id' => $requisitionId,
                'id_user' => $userContext['id']
            ]);
            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return ServiceResponse::error(message: $e->getMessage(), code: $code);
        }
    }

    /**
     * 
     */
    public function getKpis(array $userContext): ServiceResponse
    {
        $filters = [];
        $role = RoleEnum::tryFrom((int)$userContext['rolid']);
        $scope = $role?->getScope() ?? 'propio';

        // APLICACIÓN DE LA MATRIZ DE VISIBILIDAD
        $scopeFilters = match($scope) {
            'propio' => ['usuarioid' => (int)$userContext['id']],
            'planta'  => ['plantaid' => (int)$userContext['plantaid']],
            'total'  => true,
            default  => false
        };

        if(!empty($scopeFilters) && is_array($scopeFilters)) {
            $filters = $scopeFilters;
        }
        
        return ServiceResponse::success(
            $this->requisicionModel->getKpi($filters),
            'Datos obtenidos correctamente.',
            200
        );
    }

    /**
     * Crea una nueva cabecera de requisición en estado DRAFT.
     * Es un método helper privado y no maneja su propia transacción.
     *
     * @param array $userContext El ID del usuario que crea el DRAFT.
     * @param string $description La descripción para el nuevo DRAFT.
     * @return int El ID de la nueva requisición creada.
     */
    private function createNewDraft(array $userContext, string $description): int {
        $headerData = [
            'user_id' => $userContext['id'],
            'titulo' => $description,
            'estatus' => 'borrador' // Forzamos el estado a DRAFT
        ];

        // Llamamos a un método del modelo que solo crea la cabecera
        $newRequisitionId = $this->requisicionModel->createHeader($headerData);

        if ($newRequisitionId <= 0) {
            // Esta excepción será capturada por el try/catch de moveItems()
            throw new \Exception('No se pudo crear el nuevo DRAFT de destino.', 500);
        }
        
        $this->requisicionModel->logAudit($newRequisitionId, AuditAction::CREATED, 'Creación automática por split.', $userContext['id']);
        
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

    /**
     * Motor central transaccional para cambiar el estado de una requisición.
     * Encapsula la validación de la máquina de estados, permisos y auditoría.
     *
     * @param int    $requisitionId       ID de la requisición a modificar.
     * @param array  $userContext         Contexto del usuario que ejecuta la acción.
     * @param string $newStatus           El nuevo estado al que se transicionará.
     * @param array  $allowedFromStatuses Lista de estados desde los cuales es válida la transición.
     * @param string $successMessage      Mensaje a devolver en caso de éxito.
     * @param AuditAction $auditAction         Constante de acción para el log de auditoría.
     * @param string $comment             Comentario asociado a la acción.
     * @return ServiceResponse
     */
    private function changeStatus(
        int $requisitionId,
        array $userContext,
        string $newStatus,
        array $allowedFromStatuses,
        string $successMessage,
        AuditAction $auditAction,
        string $comment
    ): ServiceResponse {
        try {
            $userId = (int)$userContext['id'];
            $plantaId = (int)$userContext['plantaid'];

            // Instanciamos el validador aquí adentro para asegurarnos de que el comentario
            // cumpla con las reglas antes de abrir la transacción.
            $request = new \Requests\Requisition\ChangeStatusRequest();
            $request->validate();

            $this->db->beginTransaction();

            // 1. OBTENER Y VALIDAR EXISTENCIA
            $requisition = $this->requisicionModel->getRequisition($requisitionId);

            if (!$requisition) {
                throw new \Exception("La requisición #{$requisitionId} no existe.", 404);
            }

            // 2. VALIDAR MÁQUINA DE ESTADOS (Business Rule)
            if (!in_array($requisition['estatus'], $allowedFromStatuses)) {
                throw new \Exception("Acción no permitida. La solicitud se encuentra en estado '{$requisition['estatus']}' y no puede ser transicionada a '{$newStatus}'.", 409);
            }

            $role = RoleEnum::tryFrom((int)$userContext['rolid']);
            $scope = $role?->getScope() ?? 'propio';
            
            // 3. VALIDAR PERMISOS (Segregación de Funciones)
            $isAllowed = match($scope) {
                'propio' => (int)$requisition['usuarioid'] === $userId,
                'planta'  => (int)$requisition['plantaid'] === $plantaId,
                'total'  => true,
                default  => false
            };

            // Validación de Seguridad
            if (!$isAllowed) {
                return ServiceResponse::error("Conflicto de intereses: No tienes permitido aprobar o rechazar tus propias solicitudes.", 403);
            }

            // 4. EJECUTAR EL CAMBIO DE ESTADO EN LA BD
            $updated = $this->requisicionModel->updateStatus($requisitionId, $newStatus, $userId);

            if (!$updated) {
                throw new \Exception("Error interno: No se pudo actualizar el estado de la solicitud en la base de datos.", 500);
            }

            // 5. REGISTRAR EN AUDITORÍA (Trazabilidad)
            $this->requisicionModel->logAudit($requisitionId, $auditAction, $comment, $userId);

            $this->db->commit();

            return ServiceResponse::success(
                ['new_status' => $newStatus, 'requisicion_id' => $requisitionId],
                $successMessage,
                200
            );

        } catch (\InvalidArgumentException $i) {
            // Errores del ChangeStatusRequest (Ej. Comentario muy corto o vacío)
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ServiceResponse::validation(errors: $i->getMessage());
            
        } catch (\PDOException $p) {
            // Errores a nivel de Base de Datos (Timeouts, Deadlocks, Constraints)
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'changeStatus',
                'requisition_id' => $requisitionId,
                'new_status' => $newStatus,
                'id_user' => $userId
            ]);
            
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos al intentar cambiar el estado.");
            
        } catch (\Exception $e) {
            // Errores de la Lógica de Negocio (403, 404) y Errores Generales (500)
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $this->logMessage($e, \LogLevel::WARNING, [
                'action' => 'changeStatus',
                'requisition_id' => $requisitionId,
                'new_status' => $newStatus,
                'comment' => $comment
            ]);
            
            // Si la excepción no tiene un código HTTP seteado (0), forzamos un 500 Internal Server Error
            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return ServiceResponse::error(message: $e->getMessage(), code: $code);
        }
    }

    /**
     * MÉTODO HIJO (Worker)
     * Solo se encarga de la persistencia. No maneja transacciones ni ServiceResponse.
     */
    private function persistSpecialSpecs(array $specData): void 
    {
        // Solo hacemos el insert/update en la tabla de sourcing
        $success = $this->requisicionModel->upsertItemSpecs($specData);
        
        if (!$success) {
            throw new \Exception("Error técnico al persistir la ficha de sourcing.");
        }
    }
}