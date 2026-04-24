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
            $scope = $role->getScope();

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
                    $itemData = [
                        'inventarioid'             => $item['inventarioid'],
                        'cantidad'                 => (float)($item['cantidad'] ?? 0),
                        'precio_unitario_estimado' => (float)($item['precio_unitario_estimado'] ?? 0),
                        'notas'                    => $item['notas'] ?? ''
                    ];
                    
                    $this->requisicionModel->createDetail($requisitionId, $itemData);
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
            $request->validate();
            $payload = $request->all();

            $this->db->beginTransaction();

            // 1. VALIDACIONES DE SEGURIDAD Y COMPLIANCE
            $existingReq = $this->requisicionModel->getRequisition($requisitionId);
            
            if (!$existingReq) {
                throw new \Exception("La requisición #{$requisitionId} no existe.", 404);
            }
            
            // IMPORTANTE: Solo se puede editar si está en borrador. 
            // Si ya fue enviada a aprobación, está bloqueada.
            if ($existingReq['estatus'] !== 'borrador') {
                throw new \Exception("Compliance Error: No se puede editar una requisición que ya no es un borrador.", 403);
            }
            if ($existingReq['usuarioid'] !== $userContext['id']) {
                throw new \Exception("Security Error: No tienes permisos para editar esta requisición.", 403);
            }

            // 2. DETERMINAR EL NUEVO ESTATUS FINAL
            $isSubmit = ($payload['action'] === 'submit_approval');
            $estatusFinal = $isSubmit ? 'pendiente' : 'borrador';

            // 3. ACTUALIZAR CABECERA
            $headerData = [
                'estatus'         => $estatusFinal,
                'titulo'          => $payload['titulo'],
                'departamentoid'  => !empty($payload['departamentoid']) ? $payload['departamentoid'] : null,
                'fecha_requerida' => !empty($payload['fecha_requerida']) ? $payload['fecha_requerida'] : null,
                'prioridad'       => $payload['prioridad'] ?? 'media',
                'justificacion'   => $payload['justificacion'] ?? ''
            ];

            $headerUpdated = $this->requisicionModel->updateHeader($requisitionId, $headerData);

            // 4. PROCESAR PARTIDAS (UPSERT Lógico)
            if (!empty($payload['articulos'])) {
                
                // Recolectamos los IDs que nos mandó el Frontend para saber cuáles NO borrar
                $incomingItemIds = [];

                foreach ($payload['articulos'] as $item) {
                    $itemId = $item['idrequisicionarticulo'] ?? null;
                    
                    $itemData = [
                        'inventarioid'             => $item['inventarioid'],
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
                        }
                    } else {
                        // B. INSERTAR NUEVA PARTIDA
                        $this->requisicionModel->createDetail($requisitionId, $itemData);
                        // El nuevo ID lo podríamos obtener si createDetail lo retorna, pero no es estrictamente necesario aquí
                    }
                }

            } else {
                // Si el frontend manda un array vacío de artículos, borramos todo el detalle
                $this->requisicionModel->deleteAllItems($requisitionId);
            }

            // 5. RECALCULAR MONTO ESTIMADO
            $this->requisicionModel->updateEstimatedAmount($requisitionId);

            // 6. LOG DE AUDITORÍA
            $auditMsg = $isSubmit ? 'Editada y enviada a aprobación.' : 'Borrador actualizado.';
            $this->requisicionModel->logAudit($requisitionId, AuditAction::UPDATED, $auditMsg, $userContext['id']);

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
            $this->logMessage($p, \LogLevel::CRITICAL, ['action' => 'updateRequisition', 'id_user' => $userContext['id']]);
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
            if ($sourceReq['usuarioid'] !== $userContext['id']) {
                throw new \Exception("Security Error: No tienes permisos sobre este DRAFT.", 403); // Prevención IDOR
            }

            // 2. RESOLVER EL DESTINO (Split o Merge)
            $targetReqId = null;
            if ($payload['create_new'] === true) {
                // Caso: Urgencia o Capex/Opex (Crear nuevo DRAFT)
                $targetReqId = $this->createNewDraft($userContext['id'], "Split de Requisición #{$sourceRequisitionId}");
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

                // A) Leer TODO el registro a la memoria RAM primero (y validamos IDOR de la partida)
                $sourceItemDetails = $this->requisicionModel->getItemDetailsForUpdate($sourceRequisitionId, $item['requisition_item_id']);
                
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
        
        return $this->changeStatus(
            $requisitionId,
            $userContext['id'],
            'aprobada',
            ['pendiente'], // Solo se puede aprobar si está 'pendiente'
            'Solicitud aprobada.',
            AuditAction::APPROVED,
            "Firma {$level} autorizada por " . $userContext['nombre'] . '. Notas: ' . $request->input('comentario')
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
    public function reject(int $requisitionId, array $userContext): ServiceResponse
    {
        $request = new \Requests\Requisition\ChangeStatusRequest();
        return $this->changeStatus(
            $requisitionId,
            $userContext['id'],
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
            $userContext['id'],
            'rechazada',
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
            $userContext['id'],
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
            $pendingItems = $this->requisicionModel->calculatePendingItems($requisitionId);

            // 4. Retornar la data
            return ServiceResponse::success(
                [
                    'requisicion' => $requisition, // Mandamos la cabecera por si el frontend la necesita
                    'items_pendientes' => $pendingItems
                ],
                "Partidas pendientes calculadas exitosamente.",
                200
            );

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
        return ServiceResponse::success(
            $this->requisicionModel->getKpi(),
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
     * @param string $auditAction         Constante de acción para el log de auditoría.
     * @param string $comment             Comentario asociado a la acción.
     * @return ServiceResponse
     */
    private function changeStatus(
        int $requisitionId,
        int $userId,
        string $newStatus,
        array $allowedFromStatuses,
        string $successMessage,
        AuditAction $auditAction,
        string $comment
    ): ServiceResponse {
        try {
            // Instanciamos el validador aquí adentro para asegurarnos de que el comentario
            // cumpla con las reglas antes de abrir la transacción.
            $request = new \Requests\Requisition\ChangeStatusRequest(['comentario' => $comment]);
            $request->validate();

            $this->db->beginTransaction();

            // 1. OBTENER Y VALIDAR EXISTENCIA
            $requisition = $this->requisicionModel->getRequisition($requisitionId);

            if (!$requisition) {
                throw new \Exception("La requisición #{$requisitionId} no existe.", 404);
            }

            // 2. VALIDAR MÁQUINA DE ESTADOS (Business Rule)
            if (!in_array($requisition['estatus'], $allowedFromStatuses)) {
                throw new \Exception("Acción no permitida. La solicitud se encuentra en estado '{$requisition['estatus']}' y no puede ser transicionada a '{$newStatus}'.", 403);
            }

            // 3. VALIDAR PERMISOS (Segregación de Funciones)
            // Previene que un usuario apruebe sus propias solicitudes (a menos que las reglas de negocio lo permitan explícitamente en el futuro).
            if ($requisition['usuarioid'] === $userId && in_array($newStatus, ['aprobada', 'rechazada'])) {
                throw new \Exception("Conflicto de intereses: No tienes permitido aprobar o rechazar tus propias solicitudes.", 403);
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
}