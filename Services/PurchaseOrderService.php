<?php
//namespace Services;

use Requests\PurchaseOrder\StorePurchaseOrderRequest;

class PurchaseOrderService
{
    use \Loggable;

    protected Com_requisicionModel $requisicionModel;
    protected Com_ordenCompraModel $ordenCompraModel;
    protected object $db;

    public function __construct()
    {
        // Instanciamos ambos modelos para orquestar la transacción
        $this->requisicionModel = new Com_requisicionModel;
        $this->ordenCompraModel = new Com_ordenCompraModel;
        $this->db = $this->ordenCompraModel->getConexion();
    }

    public function index(array $filters, array $userContext): \ServiceResponse {
        try {
            $role = RoleEnum::tryFrom((int)$userContext['rolid']);
            $scope = $role?->getScope() ?? 'propio';

            $po = $this->ordenCompraModel->getAll($filters);

            // Validación de Seguridad (IDOR de Lectura), APLICACIÓN DE LA MATRIZ DE VISIBILIDAD
            if (
                !match($scope) {
                'propio' => (int)$po['created_by'] === (int)$userContext['id'],
                'planta'  => (int)$po['plantaid'] === (int)$userContext['plantaid'],
                'total'  => true,
                default  => false
            }
            ) {
                return ServiceResponse::error("Security Error: No tienes permisos para ver este módulo.", 403);
            }

            return \ServiceResponse::success($po, "Listado de Órdenes de Compra recuperado.");
        } catch (\Exception $e) {
            return \ServiceResponse::error("Error al obtener el listado: " . $e->getMessage());
        }
    }

    public function store(array $userContext): \ServiceResponse
    {
        $request = new StorePurchaseOrderRequest();

        try {
            $userId = $userContext['id'];
            $request->validate();
            $payload = $request->all();
            $reqId = (int)$payload['requisicionid'];

            $this->db->beginTransaction();

            // 1. VALIDAR REQUISICIÓN ORIGEN
            $requisition = $this->requisicionModel->getRequisition($reqId);
            if (!$requisition) {
                throw new \Exception("La requisición origen #{$reqId} no existe.", 404);
            }
            if (!in_array($requisition['estatus'], ['aprobada', 'en compra'])) {
                throw new \Exception("No se pueden generar compras para una requisición en estado '{$requisition['estatus']}'.", 403);
            }

            // 2. OBTENER SALDOS PENDIENTES (La fuente de la verdad)
            // Reutilizamos el método de la US 1 para saber exactamente cuánto podemos comprar
            $pendingItemsData = $this->requisicionModel->calculatePendingItems($reqId);
            
            // Convertimos el array a un diccionario [id_articulo => cantidad_pendiente] para búsqueda rápida O(1)
            $saldosPendientes = [];
            foreach ($pendingItemsData as $pItem) {
                $saldosPendientes[$pItem['idrequisicionarticulo']] = (float)$pItem['cantidad_pendiente'];
            }

            // 3. CREAR CABECERA DE LA ORDEN DE COMPRA
            $ocHeaderData = [
                'requisicionid' => $reqId,
                'proveedorid'   => $payload['proveedorid'],
                'almacenid'     => $payload['almacenid'],
                'estatus'       => 'emitida',
                'moneda'        => $payload['moneda'] ?? 'MXN',
                'tipo_cambio'   => $payload['tipo_cambio'] ?? 1.000000,
                'observaciones' => $payload['observaciones'] ?? '',
                'created_by'    => $userId // El comprador que genera la OC
            ];

            $ocId = $this->ordenCompraModel->createHeader($ocHeaderData);
            if ($ocId <= 0) throw new \Exception('Error al generar el folio de la Orden de Compra.', 500);

            // 4. PROCESAR PARTIDAS Y VALIDAR SALDOS (Anti-Fraude)
            $subtotalOC = 0;
            $ivaOC = 0;

            foreach ($payload['articulos'] as $item) {
                $idReqArticulo = (int)$item['idrequisicionarticulo'];
                $cantidadAComprar = (float)$item['cantidad'];
                $costoUnitario = (float)$item['costo_unitario'];
                $pct = (float)$item['porcentaje_descuento'];

                // A) Verificar que la partida exista en los saldos pendientes
                if (!isset($saldosPendientes[$idReqArticulo])) {
                    throw new \Exception("La partida #{$idReqArticulo} no pertenece a esta requisición o ya fue comprada en su totalidad.", 400);
                }

                // B) Verificar que no intente comprar más de lo permitido
                $saldoDisponible = $saldosPendientes[$idReqArticulo];
                if ($cantidadAComprar > $saldoDisponible) {
                    throw new \Exception("Inventario Error: Intentas comprar {$cantidadAComprar} unidades de la partida #{$idReqArticulo}, pero solo quedan {$saldoDisponible} pendientes por comprar.", 400);
                }

                // C) Cálculos Financieros por Partida
                $descuento = (float)($item['descuento_partida'] ?? 0);
                $subtotalPartida = ($cantidadAComprar * $costoUnitario) - $descuento;
                
                // Asumiendo un IVA estándar del 16% si no se especifica (Ajusta según tu regla de negocio)
                $impuestoPartida = $subtotalPartida * 0.16; 

                $ocDetailData = [
                    'compraid'              => $ocId,
                    'idrequisicionarticulo' => $idReqArticulo,
                    'inventarioid'          => $item['inventarioid'],
                    'cantidad'              => $cantidadAComprar,
                    'costo_unitario'        => $costoUnitario,
                    'porcentaje_descuento'  => $pct,
                    'descuento_partida'     => $descuento,
                    'impuesto_partida'      => $impuestoPartida,
                    'subtotal_partida'      => $subtotalPartida
                ];

                $this->ordenCompraModel->createDetail($ocId, $ocDetailData);

                // Acumular para la cabecera
                $subtotalOC += $subtotalPartida;
                $ivaOC += $impuestoPartida;
            }

            // 5. ACTUALIZAR TOTALES DE LA OC
            $totalOC = $subtotalOC + $ivaOC;
            $this->ordenCompraModel->updateTotals($ocId, $subtotalOC, $ivaOC, $totalOC);

            // 6. MÁQUINA DE ESTADOS: Actualización dinámica
            // Recalculamos los saldos pendientes DESPUÉS de haber insertado la OC actual
            $finalPendingBalance = $this->requisicionModel->calculatePendingItems($reqId);

            if (empty($finalPendingBalance)) {
                /**
                 * CASO A: Cumplimiento Total.
                 * Esta OC (o la suma de esta con anteriores) ya cubrió el 100% de lo solicitado.
                 */
                $this->requisicionModel->updateStatus($reqId, 'finalizada', $userId);
                
                // Registramos en auditoría el cierre automático
                $this->requisicionModel->logAudit(
                    $reqId, 
                    AuditAction::FINALIZED, 
                    "Requisición finalizada automáticamente por cumplimiento total de partidas (OC #{$ocId}).", 
                    $userId
                );
            } else {
                /**
                 * CASO B: Cumplimiento Parcial.
                 * Aún quedan saldos pendientes, por lo que solo nos aseguramos de que pase
                 * de 'aprobada' a 'en compra' si es la primera compra que se le hace.
                 */
                if ($requisition['estatus'] === 'aprobada') {
                    $this->requisicionModel->updateStatus($reqId, 'en compra', $userId);
                    
                    $this->requisicionModel->logAudit(
                        $reqId, 
                        AuditAction::UPDATED, 
                        "Estado cambiado a 'en compra' tras generación de OC #{$ocId} (Surtido Parcial).", 
                        $userId
                    );
                }
            }

            $this->db->commit();

            return \ServiceResponse::success(
                ['orden_compra_id' => $ocId],
                "Orden de Compra #{$ocId} generada exitosamente.",
                201
            );

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return \ServiceResponse::validation(errors: $i->getMessage());
        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logMessage($p, \LogLevel::CRITICAL, ['action' => 'storePurchaseOrder', 'id_user' => $userId]);
            return \ServiceResponse::error(message: "Error de integridad en la base de datos al generar la OC.");
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logMessage($e, \LogLevel::WARNING, ['action' => 'storePurchaseOrder', 'payload' => $payload ?? []]);
            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return \ServiceResponse::error(message: $e->getMessage(), code: $code);
        }
    }

    public function getWithDetails(int $ocId, array $userContext): \ServiceResponse {
        try {
            $role = RoleEnum::tryFrom((int)$userContext['rolid']);
            $scope = $role?->getScope() ?? 'propio';

            // 1. Obtener cabecera de la OC
            $oc = $this->ordenCompraModel->getById($ocId);
            if (!$oc) throw new \Exception("Orden de Compra no encontrada.", 404);

            // 2. Validación de Seguridad (Matriz de Visibilidad)
            $isAllowed = match($scope) {
                'propio' => (int)$oc['created_by'] === (int)$userContext['id'],
                'planta' => (int)$oc['plantaid'] === (int)$userContext['plantaid'],
                'total'  => true,
                default  => false
            };

            if (!$isAllowed) {
                return ServiceResponse::error("Security Error: No tienes permisos para ver esta orden.", 403);
            }

            // 3. Obtener partidas base de la OC
            $items = $this->ordenCompraModel->getDetails($ocId);

            /**
             * LÓGICA DE PROGRESO DE RECEPCIÓN (Consistencia 3-Way Match)
             * Consultamos a la base de datos cuánto se ha recibido físicamente de esta OC
             */
            $receptionBalances = $this->ordenCompraModel->getReceivedBalancesByOC($ocId);
            
            // Creamos un mapa [idrequisicionarticulo => cantidad_recibida] para cruce rápido
            $receivedMap = array_column($receptionBalances, 'total_recibido', 'idrequisicionarticulo');

            // 4. Enriquecer cada item con su estado de surtido real
            foreach ($items as &$item) {
                $idPartida = $item['idrequisicionarticulo'];
                
                // Cantidad que ya entró al almacén según las tablas de recepción
                $item['cantidad_recibida'] = (float)($receivedMap[$idPartida] ?? 0);
                
                // Cantidad total que se pidió en esta OC
                $qtyTotal = (float)$item['cantidad'];

                // Cálculo del porcentaje para la barra de progreso del Frontend
                $item['progreso_recepcion'] = ($qtyTotal > 0) 
                    ? round(($item['cantidad_recibida'] / $qtyTotal) * 100, 2) 
                    : 0;
            }

            // 5. Asignar datos enriquecidos y relacionados
            $oc['items'] = $items;
            $oc['related_pos'] = $this->ordenCompraModel->getRelatedPOs((int)$oc['requisicionid'], $ocId);

            return \ServiceResponse::success($oc);
        } catch (\Exception $e) {
            return \ServiceResponse::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Marca una Orden de Compra como 'en_transito'.
     */
    public function transit(int $id, array $userContext): ServiceResponse
    {
        $request = new \Requests\PurchaseOrder\ChangeStatusRequest();
        
        try {
            $request->validate();

            // 1. INICIAR TRANSACCIÓN (Obligatorio porque el motor ya no la tiene)
            $this->db->beginTransaction();

            // 2. LLAMAR AL MOTOR (Worker)
            $this->changeStatus(
                $id,
                $userContext,
                PurchaseOrderEnum::EN_TRANSITO,
                [PurchaseOrderEnum::EMITIDA->value],
                'La orden de compra ha sido marcada en tránsito.',
                AuditAction::SHIPPED,
                'TRANSIT:' . ($request->input('comentario') ?? 'Iniciando logística de entrega.')
            );

            // 3. CONFIRMAR CAMBIOS
            $this->db->commit();

            return ServiceResponse::success(
                ['idcompra' => $id, 'new_status' => PurchaseOrderEnum::EN_TRANSITO->value],
                'La orden de compra ha sido marcada en tránsito.'
            );

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ServiceResponse::validation(errors: $i->getMessage());
        } catch (\Exception $e) {
            // 4. DESHACER CAMBIOS EN CASO DE ERROR
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->logMessage($e, \LogLevel::ERROR, ['action' => 'transitPO', 'id' => $id]);
            return ServiceResponse::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Cancela una Orden de Compra y libera los saldos de la requisición.
     */
    public function cancel(int $id, array $userContext): ServiceResponse
    {
        $request = new \Requests\PurchaseOrder\ChangeStatusRequest();
        
        $userId = $userContext['id'];
        
        try {
            $request->validate();

            $this->db->beginTransaction();

            // 1. Ejecutar el cambio de estado usando nuestro motor central
            $this->changeStatus(
                $id,
                $userContext,
                PurchaseOrderEnum::CANCELADA,
                [PurchaseOrderEnum::EMITIDA->value, PurchaseOrderEnum::EN_TRANSITO->value],
                'Orden de Compra cancelada exitosamente.',
                AuditAction::CANCELED,
                'CANCELACIÓN: ' . ($request->input('comentario') ?? 'Cancelación manual por el comprador.')
            );

            // 2. LÓGICA DE REVERSIÓN DE REQUISICIÓN
            // Obtenemos la OC para saber a qué requisición pertenecía
            $po = $this->ordenCompraModel->getById($id);
            $reqId = (int)$po['requisicionid'];

            // 3. DETERMINAR NUEVO ESTADO DE LA REQUISICIÓN
            // Contamos cuántas OCs NO canceladas quedan para este folio
            $activeOCsCount = $this->ordenCompraModel->countActiveOrdersByRequisition($reqId);

            /**
             * Lógica de Reversión:
             * - Si ya no hay OCs activas -> El estado vuelve a 'aprobada'.
             * - Si aún hay otras OCs -> El estado debe ser 'en compra'.
             */
            $newReqStatus = ($activeOCsCount === 0) ? 'aprobada' : 'en compra';

            // 4. ACTUALIZAR REQUISICIÓN
            $this->requisicionModel->updateStatus($reqId, $newReqStatus, $userId);
            
            // Log en el historial de la Requisición
            $this->requisicionModel->logAudit(
                $reqId, 
                AuditAction::UPDATED, 
                "Estado actualizado a '{$newReqStatus}' por cancelación de OC #{$id}.", 
                $userId
            );

            // --- COMMIT GLOBAL: Todo se guarda o nada se guarda ---
            $this->db->commit();

            return ServiceResponse::success(['idcompra' => $id], 'Orden cancelada y saldos de requisición liberados.');

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ServiceResponse::validation(errors: $i->getMessage());
        } catch (\Exception $e) {
            // --- ROLLBACK: Si algo falló arriba, deshacemos todo ---
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $this->logMessage($e, \LogLevel::ERROR, ['action' => 'cancelPO', 'id' => $id]);
            return ServiceResponse::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Motor central para la máquina de estados de Órdenes de Compra.
     */
    private function changeStatus(
        int $id,
        array $userContext,
        PurchaseOrderEnum  $newStatus,
        array $allowedFromStatuses,
        string $successMessage,
        AuditAction $auditAction,
        string $comment
    ): void {

        // 1. OBTENER Y BLOQUEAR (Pessimistic Locking)
        $po = $this->ordenCompraModel->getPurchaseOrderForUpdate($id);
        if (!$po) throw new \Exception("La Orden de Compra #{$id} no existe.", 404);

        // 2. VALIDAR MÁQUINA DE ESTADOS
        if (!in_array($po['estatus'], $allowedFromStatuses)) {
            throw new \Exception("Cambio de estado no permitido desde '{$po['estatus']}' a '{$newStatus->value}'.", 403);
        }

        $role = RoleEnum::tryFrom((int)$userContext['rolid']);
        $scope = $role?->getScope() ?? 'propio';

        // APLICACIÓN DE LA MATRIZ DE VISIBILIDAD
        $isAllowed = match($scope) {
            'propio' => (int)$po['usuarioid'] === (int)$userContext['id'],
            'planta'  => (int)$po['plantaid'] === (int)$userContext['plantaid'],
            'total'  => true,
            default  => false
        };

        // 3. Validación de Seguridad (IDOR de Lectura)
        if (!$isAllowed) {
            throw new \Exception("Security Error: No tienes permisos sobre esta OC.", 403);
        }

        // 4. EJECUTAR ACTUALIZACIÓN
        // Nota: Asegúrate de tener updateStatus en tu Com_orden_compraModel
        $updated = $this->ordenCompraModel->updateStatus($id, $newStatus->value, $userContext['id']);
        if (!$updated) throw new \Exception("Error al actualizar el estado en la base de datos.", 500);

        // 5. REGISTRAR AUDITORÍA
        $this->ordenCompraModel->logAudit($id, $auditAction, $comment, $userContext['id']);
    }
}
?>