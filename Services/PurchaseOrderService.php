<?php
//namespace Services;

use Requests\PurchaseOrder\StorePurchaseOrderRequest;

class PurchaseOrderService
{
    use \Loggable;

    protected Com_requisicionModel $requisicionModel;
    protected Com_ordenCompraModel $ordenCompraModel;
    protected InventoryReceptionService $inventoryReceptionService;
    protected object $db;

    public function __construct()
    {
        // Instanciamos ambos modelos para orquestar la transacción
        $this->requisicionModel = new Com_requisicionModel;
        $this->ordenCompraModel = new Com_ordenCompraModel;
        $this->inventoryReceptionService = new InventoryReceptionService;
        $this->db = $this->ordenCompraModel->getConexion();
    }

    /**
     * Recupera las POs aplicando filtros seguros de Query-Level Security.
     * Soporta de forma híbrida e integrada tanto a Empleados (ERP) como a Proveedores (SRM).
     */
    public function index(array $filters, array $userContext): ServiceResponse
    {
        try {
            // 1. Identificar si el usuario autenticado es un Proveedor Externo (SRM)
            $isVendor = ($userContext['rol'] ?? '') === 'VENDOR' || !empty($userContext['vendor_id']);

            if ($isVendor) {
                // SEGURIDAD IMPLACABLE (Anti-IDOR): Forzamos que el filtro de proveedor 
                // sea estrictamente el ID de su JWT, ignorando cualquier cosa enviada por GET.
                $filters['proveedorid'] = (int) $userContext['vendor_id'];
            } else {
                // 2. Si es Empleado Interno, aplicamos la Matriz de Visibilidad (RBAC)
                $role = RoleEnum::tryFrom((int) $userContext['rolid']);
                $scope = $role?->getScope() ?? 'propio';

                // Inyectamos el alcance de seguridad como un filtro directo para la consulta SQL
                switch ($scope) {
                    case 'propio':
                        $filters['created_by'] = (int) $userContext['id'];
                        break;
                    case 'planta':
                        $filters['plantaid'] = (int) $userContext['plantaid'];
                        break;
                    case 'total':
                        // Sin restricciones adicionales en la consulta SQL (Ver todo)
                        break;
                    default:
                        return ServiceResponse::error("Security Error: Alcance de visibilidad no configurado.", 403);
                }
            }

            // 3. El modelo ejecuta la consulta SQL filtrando de origen en BD de forma ultra rápida
            $poList = $this->ordenCompraModel->getAll($filters);

            return ServiceResponse::success($poList, "Listado de Órdenes de Compra recuperado.");

        } catch (\Exception $e) {
            return ServiceResponse::error("Error al obtener el listado: " . $e->getMessage(), 500);
        }
    }

    public function store(array $userContext, ?array $manualPayload = null): \ServiceResponse
    {
        $request = new StorePurchaseOrderRequest();

        try {
            $userId = $userContext['id'];
            $plantaId = $userContext['plantaid'];
            // AJUSTE 1: Priorizar payload manual si existe (para automatización)
            if (!$manualPayload) $request->validate();
            $payload = $manualPayload ?? $request->all();
            
            $reqId = (int)$payload['requisicionid'];

            $isInternalCall = $this->db->inTransaction(); 
            if (!$isInternalCall) {
                $this->db->beginTransaction();
            }

            // 1. VALIDAR REQUISICIÓN ORIGEN
            $requisition = $this->requisicionModel->getRequisition($reqId);
            if (!$requisition) {
                throw new \Exception("La requisición origen #{$reqId} no existe.", 404);
            }

            // --- INICIO AJUSTE 2: Identificar flujo Directo ---
            $isSpotBuy = ($requisition['tipo_requisicion'] === 'spot_buy');
            $ocStatus = $isSpotBuy ? PurchaseOrderEnum::CERRADA->value : PurchaseOrderEnum::EMITIDA->value;
            $receptionItems = []; // Para WMS automático
            // --- FIN AJUSTE 2 ---

            if (!in_array($requisition['estatus'], ['aprobada', 'en compra'])) {
                throw new \Exception("No se pueden generar compras para una requisición en estado '{$requisition['estatus']}'.", 403);
            }            

            // 2. OBTENER SALDOS PENDIENTES
            $pendingItemsData = $this->requisicionModel->calculatePendingItems($reqId);
            $saldosPendientes = array_column($pendingItemsData, 'cantidad_pendiente', 'idrequisicionarticulo');

            // 3. CREAR CABECERA DE LA ORDEN DE COMPRA
            $ocHeaderData = [
                'requisicionid' => $reqId,
                'proveedorid'   => $payload['proveedorid'],
                'plantaid'      => $plantaId,
                'almacenid'     => $payload['almacenid'],
                'estatus'       => $ocStatus,
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

                // AJUSTE 3: Recolectar para auto-recepción si es directa
                if ($isSpotBuy) {
                    $receptionItems[] = [
                        'idrequisicionarticulo' => $idReqArticulo,
                        'inventarioid' => $item['inventarioid'],
                        'cantidad_recibida' => $cantidadAComprar
                    ];
                }

                // Acumular para la cabecera
                $subtotalOC += $subtotalPartida;
                $ivaOC += $impuestoPartida;
            }

            // 5. ACTUALIZAR TOTALES DE LA OC
            $this->ordenCompraModel->updateTotals($ocId, $subtotalOC, $ivaOC, $subtotalOC + $ivaOC);

            // --- INICIO AJUSTE 3: Disparar Recepción Automática ---
            if ($isSpotBuy) {
                $receptionRes = $this->inventoryReceptionService->store($userContext, [
                    'idcompra' => $ocId,
                    'num_remision' => 'STP-SPOT-BUY',
                    'observaciones' => 'Entrada generada automáticamente por flujo de Pago Inmediato.',
                    'articulos' => $receptionItems
                ]);

                if (!$receptionRes->success) throw new \Exception("Error en auto-recepción: " . $receptionRes->message);
            }
            // --- FIN AJUSTE 3 ---

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

            if (!$isInternalCall) {
                $this->db->commit();
            }

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
            // 1. Obtener cabecera de la OC
            $oc = $this->ordenCompraModel->getById($ocId);
            if (!$oc) throw new \Exception("Orden de Compra no encontrada.", 404);

            // =================================================================
            // 2. CIRUGÍA DE MÍNIMA INVASIÓN: Soporte de Seguridad Híbrido
            // =================================================================
            $isVendor = ($userContext['role'] ?? '') === 'VENDOR' || !empty($userContext['vendor_id']);

            if ($isVendor) {
                // Seguridad SRM (Anti-IDOR): Validamos que la OC pertenezca a este proveedor
                $isAllowed = (int)($oc['proveedorid'] ?? $oc['proveedor_id'] ?? 0) === (int)$userContext['vendor_id'];
            } else {
                // Seguridad ERP Interno: Matriz de Visibilidad para Empleados
                $role = RoleEnum::tryFrom((int)$userContext['rolid']);
                $scope = $role?->getScope() ?? 'propio';

                $isAllowed = match($scope) {
                    'propio' => (int)$oc['created_by'] === (int)$userContext['id'],
                    'planta' => (int)$oc['plantaid'] === (int)$userContext['plantaid'],
                    'total'  => true,
                    default  => false
                };
            }

            if (!$isAllowed) {
                return ServiceResponse::error("Security Error: No tienes permisos para ver esta orden.", 403);
            }
            // =================================================================
            // FIN DE LA CIRUGÍA (El resto del código se mantiene intacto)
            // =================================================================

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

    /**
     * Método puente para procesar requisiciones de Pago Inmediato.
     */
    public function processSpotBuyAutomation(int $reqId, array $userContext): void
    {
        // 1. Obtener la requisición con sus saldos (usando el query que refactorizamos)
        $items = $this->requisicionModel->calculatePendingItems($reqId);
        
        if (empty($items)) return;

        // 2. Preparar el payload para el método 'store'
        // Como es directa, asumimos el proveedor sugerido y almacén por defecto
        $payload = [
            'requisicionid' => $reqId,
            'proveedorid'   => 1, // Ejemplo: ID de Amazon/Clara
            'almacenid'     => 1, // Almacén general o de oficina
            'moneda'        => 'MXN',
            'tipo_cambio'   => 1.0,
            'observaciones' => "Generado automáticamente por flujo de Compra Directa.",
            'articulos'     => []
        ];

        foreach ($items as $item) {
            $payload['articulos'][] = [
                'idrequisicionarticulo' => $item['idrequisicionarticulo'],
                'inventarioid'          => $item['inventarioid'],
                'cantidad'              => $item['cantidad_pendiente'],
                'costo_unitario'        => $item['precio_unitario_estimado'],
                'porcentaje_descuento'  => 0,
                'descuento_partida'     => 0
            ];
        }

        /**
         * IMPORTANTE: Llamamos al método store() que ya tiene la lógica de:
         * - Crear OC (Cerrada)
         * - Crear Recepción (WMS)
         * - Actualizar Kardex
         */
        $res = $this->store($userContext, $payload); // Asegúrate de que store acepte el payload opcional

        if (!$res->success) {
            throw new \Exception("Fallo en la creación de OC Directa: " . $res->message);
        }
    }
}
?>