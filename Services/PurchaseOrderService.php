<?php
//namespace Services;

use Requests\PurchaseOrder\StorePurchaseOrderRequest;

class PurchaseOrderService
{
    use \Loggable;

    protected Com_requisicionModel $requisicionModel;
    protected $ordenCompraModel;
    protected $db;

    public function __construct()
    {
        // Instanciamos ambos modelos para orquestar la transacción
        $this->requisicionModel = new Com_requisicionModel;
        $this->ordenCompraModel = new Com_ordenCompraModel;
        $this->db = $this->ordenCompraModel->getConexion();
    }

    public function index(array $filters): \ServiceResponse {
        try {
            $data = $this->ordenCompraModel->getAll($filters);
            return \ServiceResponse::success($data, "Listado de Órdenes de Compra recuperado.");
        } catch (\Exception $e) {
            return \ServiceResponse::error("Error al obtener el listado: " . $e->getMessage());
        }
    }

    public function store(int $userId): \ServiceResponse
    {
        $request = new StorePurchaseOrderRequest();

        try {
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

            // 6. MÁQUINA DE ESTADOS (US 3): Actualizar Requisición a 'en compra'
            if ($requisition['estatus'] === 'aprobada') {
                $this->requisicionModel->updateStatus($reqId, 'en compra', $userId);
            }

            // Opcional: Si quieres ser ultra-senior, aquí podrías volver a llamar a calculatePendingItems($reqId).
            // Si devuelve un array vacío, significa que esta OC satisfizo el 100% de la requisición, 
            // y podrías cambiar el estatus de la requisición a 'finalizada'.

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

    public function getWithDetails(int $ocId, int $userId): \ServiceResponse {
        try {
            $oc = $this->ordenCompraModel->getById($ocId);
            if (!$oc) throw new \Exception("Orden de Compra no encontrada.", 404);

            $oc['items'] = $this->ordenCompraModel->getDetails($ocId);
            $oc['related_pos'] = $this->ordenCompraModel->getRelatedPOs((int)$oc['requisicionid'], $ocId);

            return \ServiceResponse::success($oc);
        } catch (\Exception $e) {
            return \ServiceResponse::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
?>