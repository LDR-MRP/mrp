<?php

use Requests\InventoryReception\StoreInventoryReceptionRequest;

class InventoryReceptionService
{
    use \Loggable;

    private readonly Com_ordenCompraModel $ordenCompraModel;
    private readonly Inv_recepcionModel $recepcionModel;
    private readonly Inv_inventarioModel $inventarioModel;
    protected object $db;

    public function __construct()
    {
        $this->ordenCompraModel = new Com_ordenCompraModel();
        $this->recepcionModel = new Inv_recepcionModel();
        $this->inventarioModel = new Inv_inventarioModel();
        $this->db = $this->ordenCompraModel->getConexion();
    }

    /**
     * Obtiene los datos para la vista de cotejo (HU #72)
     */
    public function getCotejoData(int $idcompra, array $userContext): ServiceResponse
    {
        try {
            $oc = $this->ordenCompraModel->getById($idcompra);
            if (!$oc) throw new \Exception("La Orden de Compra #{$idcompra} no existe.", 404);
            
            // Seguridad IDOR: Validar planta
            if ((int)$userContext['rolid'] !== 1 && (int)$oc['plantaid'] !== (int)$userContext['plantaid']) {
                throw new \Exception("No tienes permiso para recibir mercancía de esta planta.", 403);
            }

            $items = $this->ordenCompraModel->getPendingReceptionItems($idcompra);

            return ServiceResponse::success([
                'orden' => $oc,
                'items_pendientes' => $items
            ]);

        } catch (\Exception $e) {
            return ServiceResponse::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Procesa la entrada de almacén y gestiona la máquina de estados de la OC.
     * Criterio HU #71 y HU #70.
     */
    public function store(array $userContext): ServiceResponse
    {
        $storeInventoryReceptionRequest = new StoreInventoryReceptionRequest;

        try {
            $payload = $storeInventoryReceptionRequest->all(); 

            $this->db->beginTransaction();

            $idCompra = (int)$payload['idcompra'];
            $userId   = (int)$userContext['id'];
            $plantaId   = (int)$userContext['plantaid'];

            // 1. BLOQUEO DE INTEGRIDAD (Pessimistic Locking)
            // Bloqueamos la OC para que nadie más procese una recepción simultánea
            $oc = $this->ordenCompraModel->getPurchaseOrderForUpdate($idCompra);
            if (!$oc) throw new \Exception("La Orden de Compra no existe.", 404);

            // 2. INSERTAR CABECERA DE RECEPCIÓN
            $recepcionId = $this->recepcionModel->insertHeader([
                'idcompra'     => $idCompra,
                'plantaid'    => $plantaId,
                'usuarioid'    => $userId,
                'num_remision' => $payload['num_remision'],
                'observaciones' => $payload['observaciones'] ?? '',
                'created_by'    => $userId
            ]);

            // 3. PROCESAR PARTIDAS Y VALIDAR EXCESOS (HU #70)
            $pendingItems = $this->ordenCompraModel->getPendingReceptionItems($idCompra);
            $saldosMap = array_column($pendingItems, null, 'idrequisicionarticulo');

            foreach ($payload['articulos'] as $item) {
                $idReqArt = (int)$item['idrequisicionarticulo'];
                $qtyRecibida = (float)$item['cantidad_recibida'];

                if (!isset($saldosMap[$idReqArt])) {
                    throw new \Exception("La partida #{$idReqArt} no pertenece a esta OC o ya fue completada.");
                }

                // DEFENSA EN PROFUNDIDAD: Validación de excesos en servidor
                $saldoMaximo = (float)$saldosMap[$idReqArt]['saldo_pendiente'];
                if ($qtyRecibida > $saldoMaximo) {
                    throw new \Exception("ERROR: Intento de recibir {$qtyRecibida} unidades, pero el saldo máximo es {$saldoMaximo}.");
                }

                // --- INICIO AFECTACIÓN DE INVENTARIO ---

                // A. Consultar info del maestro para validar tipo (Producto vs Servicio)
                // Se asume que tienes acceso a este modelo
                $productoMaestro = $this->inventarioModel->selectInventario($item['inventarioid']);

                if ($productoMaestro && $productoMaestro['tipo_elemento'] !== 'S') {
                    
                    // B. Actualizar Último Costo en el maestro (wms_inventario)
                    // Tomamos el costo pactado en la OC que viene en la consulta de saldos
                    $costoCompra = (float)$saldosMap[$idReqArt]['precio_unitario_estimado'];
                    $this->inventarioModel->updateLastCost($item['inventarioid'], $costoCompra);

                    // C. Calcular existencia acumulada para el Kardex
                    // Se debe sumar la existencia actual en el almacén destino + lo que está entrando
                    $stockActual = $this->inventarioModel->getCurrentStock($item['inventarioid'], (int)$oc['almacenid']);
                    $nuevaExistencia = $stockActual + $qtyRecibida;

                    // D. Registrar movimiento en Kardex (wms_movimientos_inventario)
                    $this->inventarioModel->addMovement([
                        'inventarioid'      => $item['inventarioid'],
                        'almacenid'         => (int)$oc['almacenid'],
                        'numero_movimiento' => $recepcionId,
                        'concepmovid'       => 1, // ID concepto: Entrada por compra
                        'referencia'        => $payload['num_remision'],
                        'cantidad'          => $qtyRecibida,
                        'costo'             => $costoCompra,
                        'existencia'        => $nuevaExistencia,
                        'signo'             => 1  // 1 para entrada
                    ]);
                }

                // --- FIN AFECTACIÓN DE INVENTARIO ---

                // Insertar detalle de entrada
                $this->recepcionModel->insertDetail($recepcionId, [
                    'idrequisicionarticulo' => $idReqArt,
                    'inventarioid'          => $item['inventarioid'],
                    'cantidad_recibida'     => $qtyRecibida
                ]);
            }

            // 4. MÁQUINA DE ESTADOS: CIERRE AUTOMÁTICO (HU #71)
            // Recalculamos saldos DESPUÉS de los inserts actuales (dentro de la transacción)
            $finalPending = $this->ordenCompraModel->getPendingReceptionItems($idCompra);
            
            /**
             * Determinamos el nuevo estado:
             * - Si no quedan pendientes -> CERRADA (Completada)
             * - Si aún quedan pendientes -> RECIBIDA_PARCIAL
             */
            $newStatus = empty($finalPending) 
                ? PurchaseOrderEnum::CERRADA 
                : PurchaseOrderEnum::RECIBIDA_PARCIAL;

            // Actualizamos la OC
            $this->ordenCompraModel->updateStatus($idCompra, $newStatus->value, $userId);

            // 5. AUDITORÍA
            $this->ordenCompraModel->logAudit(
                $idCompra, 
                AuditAction::RECEIVED, 
                "Recepción registrada. Documento: {$payload['num_remision']}. Estado final: {$newStatus->value}", 
                $userId
            );

            $this->db->commit();

            return ServiceResponse::success(
                ['idrecepcion' => $recepcionId], 
                "Entrada registrada exitosamente como " . strtoupper($newStatus->value)
            );

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logMessage($e, \LogLevel::CRITICAL, ['payload' => $payload]);
            return ServiceResponse::error($e->getMessage(), (int)$e->getCode() ?: 500);
        }
    }
}
