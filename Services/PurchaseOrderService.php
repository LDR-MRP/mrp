<?php
//namespace Services;

use Requests\PurchaseOrder\StorePurchaseOrderRequest;

class PurchaseOrderService
{
    use \Loggable;

    protected Com_requisicionModel $requisicionModel;
    protected Com_ordenCompraModel $ordenCompraModel;
    protected InventoryReceptionService $inventoryReceptionService;
    protected Com_requisicionCotizacionModel $requisicionCotizacionModel;
    protected object $db;

    public function __construct()
    {
        // Instanciamos ambos modelos para orquestar la transacción
        $this->requisicionModel = new Com_requisicionModel;
        $this->ordenCompraModel = new Com_ordenCompraModel;
        $this->inventoryReceptionService = new InventoryReceptionService;
        $this->requisicionCotizacionModel = new Com_requisicionCotizacionModel;
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
        $request = new \Requests\PurchaseOrder\StorePurchaseOrderRequest();

        try {
            $userId = (int)$userContext['id'];
            $plantaId = (int)$userContext['plantaid'];

            if (!$manualPayload) $request->validate();
            $payload = $manualPayload ?? $request->all();
            $reqId = (int)$payload['requisicionid'];

            // 1. VALIDACIÓN DE INTEGRIDAD DE LA REQUISICIÓN
            $requisition = $this->requisicionModel->getRequisition($reqId);
            if (!$requisition) throw new \Exception("La requisición origen #{$reqId} no existe.", 404);
            if (!in_array($requisition['estatus'], ['aprobada', 'en compra'])) {
                throw new \Exception("Estado de requisición inválido para compra.", 409);
            }

            // --- INICIO LÓGICA DE SPLITTING (Proveedor + Naturaleza) ---
            $articulosAgrupados = array_reduce($payload['articulos'], function($carry, $item) {
                $tipo = $item['tipo_elemento'] ?? 'P'; 
                $key = $item['proveedorid'] . '_' . $tipo;
                $carry[$key][] = $item;
                return $carry;
            }, []);

            $isInternalCall = $this->db->inTransaction(); 
            if (!$isInternalCall) $this->db->beginTransaction();

            $generatedOcIds = [];
            $freshBalances = $this->requisicionModel->getPendingItemsWithSourcing($reqId);
            $dbMap = array_column($freshBalances, null, 'idrequisicionarticulo');

            // 2. ITERACIÓN MAESTRA DE SPLITTING
            foreach ($articulosAgrupados as $splitKey => $partidas) {
                
                $idProveedor = (int) explode('_', $splitKey)[0];
                $tipoGrupo = $partidas[0]['tipo_elemento'] ?? 'P';
                $isSpotBuy = ($requisition['tipo_requisicion'] === 'spot_buy' || $idProveedor === 999);
                
                // TODAS las OCs nacen como EMITIDA para permitir Three-Way Match (Factura/Ticket)
                $ocStatus = PurchaseOrderEnum::EMITIDA->value;

                // 3. CREAR CABECERA (Transaccional)
                $labelTipo = ($tipoGrupo === 'S') ? '[SERVICIOS]' : '[PRODUCTOS]';
                $ocId = $this->ordenCompraModel->createHeader([
                    'requisicionid' => $reqId,
                    'proveedorid'   => $idProveedor,
                    'plantaid'      => $plantaId,
                    'almacenid'     => $payload['almacenid'],
                    'estatus'       => $ocStatus,
                    'moneda'        => $payload['moneda'] ?? 'MXN',
                    'tipo_cambio'   => $payload['tipo_cambio'] ?? 1.0,
                    'observaciones' => trim($labelTipo . ' ' . ($payload['observaciones'] ?? '')),
                    'created_by'    => $userId
                ]);

                if ($ocId <= 0) throw new \Exception("Error al generar cabecera OC.");

                $subtotalOC = 0;
                $ivaOC = 0;
                $autoProcessItems = [];

                // 4. INSERTAR DETALLES Y VALIDAR COMPLIANCE
                foreach ($partidas as $item) {
                    $idReqArt = (int)$item['idrequisicionarticulo'];
                    $dbItem = $dbMap[$idReqArt] ?? null;

                    // VALIDACIÓN RED TEAM: Semáforo y Precios
                    if (!$dbItem || $dbItem['operation_status'] !== 'READY') {
                        throw new \Exception("Partida #{$idReqArt} bloqueada por integridad (Onboarding/SKU).", 403);
                    }
                    if ($dbItem['is_price_locked'] == 1 && (float)$item['costo_unitario'] > (float)$dbItem['costo_base_pactado']) {
                        throw new \Exception("Bypass de precio detectado en partida #{$idReqArt}.", 403);
                    }

                    $subtotalPartida = ((float)$item['cantidad'] * (float)$item['costo_unitario']) - (float)($item['descuento_partida'] ?? 0);
                    $impuestoPartida = $subtotalPartida * 0.16; // TODO: Dinámico por SKU

                    $this->ordenCompraModel->createDetail($ocId, [
                        'compraid'              => $ocId,
                        'idrequisicionarticulo' => $idReqArt,
                        'inventarioid'          => $item['inventarioid'],
                        'tipo_elemento'         => $item['tipo_elemento'] ?? 'P',
                        'cantidad'              => $item['cantidad'],
                        'costo_unitario'        => $item['costo_unitario'],
                        'porcentaje_descuento'  => $item['porcentaje_descuento'] ?? 0,
                        'descuento_partida'     => $item['descuento_partida'] ?? 0,
                        'impuesto_partida'      => $impuestoPartida,
                        'subtotal_partida'      => $subtotalPartida,
                        'created_by'            => $userId
                    ]);

                    // Coleccionamos para auto-recepción/aceptación si es Spot Buy (Casos 2 y 4)
                    if ($isSpotBuy) {
                        $autoProcessItems[] = [
                            'idrequisicionarticulo' => $idReqArt,
                            'inventarioid' => $item['inventarioid'],
                            'cantidad_recibida' => $item['cantidad']
                        ];
                    }

                    $subtotalOC += $subtotalPartida;
                    $ivaOC += $impuestoPartida;

                    // --- CIERRE DE PINZA SOURCING ---
                    // Vinculamos la oferta ganadora con la OC generada para reporte de ahorros
                    $this->requisicionCotizacionModel->linkPurchaseOrder($idReqArt, $ocId);
                }

                $this->ordenCompraModel->updateTotals($ocId, $subtotalOC, $ivaOC, $subtotalOC + $ivaOC);

                // 5. PROCESAMIENTO OPERATIVO (Casos 2 y 4)
                if ($isSpotBuy && !empty($autoProcessItems)) {
                    if ($tipoGrupo === 'P') {
                        // CASO 2: Producto + Ticket -> Auto-WMS
                        $res = $this->inventoryReceptionService->store($userContext, [
                            'idcompra' => $ocId,
                            'num_remision' => 'AUTO-SPOT-BUY',
                            'articulos' => $autoProcessItems
                        ]);
                        if (!$res->success) throw new \Exception("Falla en Auto-WMS: " . $res->message);
                    } else {
                        // CASO 4: Servicio + Ticket -> Auto-Aceptación (Virtual)
                        // Este método marca la OC como RECIBIDA/CUMPLIDA sin afectar stock
                        $this->ordenCompraModel->markAsReceived($ocId, $userId, "Aceptación automática por flujo Spot Buy.");
                    }
                }

                $generatedOcIds[] = $ocId;
            }

            // 6. CIERRE DE REQUISICIÓN PADRE
            $this->updateParentRequisitionStatus($reqId, $userId, $generatedOcIds);

            if (!$isInternalCall) $this->db->commit();

            return \ServiceResponse::success(
                ['ordenes_generadas' => $generatedOcIds],
                count($generatedOcIds) . " Orden(es) generada(s) exitosamente.",
                201
            );

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return \ServiceResponse::validation($i->getMessage());
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logMessage($e, \LogLevel::WARNING, ['payload' => $payload ?? []]);
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            return \ServiceResponse::error($e->getMessage(), (int)$code);
        } catch (\PDOException | \Error $p) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logMessage($p, \LogLevel::CRITICAL, ['action' => 'splitting_po_critical']);
            return \ServiceResponse::error("Error de integridad en el Splitting.", 500);
        }
    }

    /**
     * Método auxiliar para gestionar la máquina de estados de la Requisición original.
     */
    private function updateParentRequisitionStatus(int $reqId, int $userId, array $ocIds): void
    {
        $finalBalance = $this->requisicionModel->calculatePendingItems($reqId);
        $status = empty($finalBalance) ? 'finalizada' : 'en compra';
        $action = empty($finalBalance) ? \AuditAction::FINALIZED : \AuditAction::UPDATED;
        
        $this->requisicionModel->updateStatus($reqId, $status, $userId);
        $this->requisicionModel->logAudit(
            $reqId, 
            $action, 
            "Se generaron las siguientes OCs: #" . implode(', #', $ocIds), 
            $userId
        );
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
                $qtyTotal = (float)$item['cantidad'];
                $tipo = $item['tipo_elemento'] ?? 'P'; // P=Producto, S=Servicio

                if ($tipo === 'S') {
                    /**
                     * LÓGICA PARA SERVICIOS:
                     * Si la OC ya fue aceptada administrativamente (estatus 'recibida' o 'cerrada'),
                     * reflejamos el cumplimiento total en la UI.
                     */
                    $isAccepted = in_array($oc['estatus'], ['recibida', 'cerrada']);
                    $item['cantidad_recibida'] = $isAccepted ? $qtyTotal : 0.00;
                } else {
                    /**
                     * LÓGICA PARA PRODUCTOS:
                     * Mantenemos la consulta estricta al WMS (recepciones físicas).
                     */
                    $item['cantidad_recibida'] = (float)($receivedMap[$idPartida] ?? 0);
                }

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

    /**
     * Calcula los KPIs para el dashboard administrativo o SRM, aplicando 
     * de forma híbrida la matriz de visibilidad de datos.
     */
    public function getKpiSummary(array $userContext): ServiceResponse
    {
        try {
            $isVendor = ($userContext['role'] ?? '') === 'VENDOR' || !empty($userContext['vendor_id']);
            $filters = [];

            if ($isVendor) {
                // Proveedores externos (SRM) solo ven sus propios números
                $filters['proveedorid'] = (int) $userContext['vendor_id'];
            } else {
                // Empleados internos (ERP) aplican su alcance por rol
                $role = RoleEnum::tryFrom((int)$userContext['rolid']);
                $scope = $role?->getScope() ?? 'propio';

                switch ($scope) {
                    case 'propio':
                        $filters['created_by'] = (int)$userContext['id'];
                        break;
                    case 'planta':
                        $filters['plantaid'] = (int)$userContext['plantaid'];
                        break;
                    case 'total':
                        // Ver todo
                        break;
                    default:
                        return ServiceResponse::error("Security Error: Alcance de visibilidad no configurado.", 403);
                }
            }

            $kpis = $this->ordenCompraModel->getDashboardKpis($filters);

            // Casteo de tipos estricto para PHP 8.3 / Laravel 13 Ready
            foreach ($kpis as &$kpi) {
                $kpi['cantidad'] = (int)$kpi['cantidad'];
                $kpi['estatus']  = (string)$kpi['estatus'];
            }

            return ServiceResponse::success($kpis, "KPIs calculados de forma segura.");

        } catch (\Exception $e) {
            return ServiceResponse::error("Error al calcular KPIs: " . $e->getMessage(), 500);
        }
    }
}
?>