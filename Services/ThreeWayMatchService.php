<?php

declare(strict_types=1);

readonly class ThreeWayMatchService
{
    private const TOLERANCIA_CENTAVOS = 0.10;

    public function __construct(
        private AccountsPayableInvoiceModel $facturaModel,
        private Com_ordenCompraModel $ordenCompraModel
    ) {}

    /**
     * Ejecuta el Cotejo de 3 Vías para una factura específica y decide si autoriza o congela el pago.
     * 
     * @param int $invoiceId ID de la factura en cxp_tra_facturas
     * @return ServiceResponse
     */
    public function reconcile(int $invoiceId): ServiceResponse
    {
        try {
            // 1. Obtener la factura a conciliar
            $factura = $this->facturaModel->getById($invoiceId);
            if (!$factura) {
                return ServiceResponse::error("Factura no encontrada.", 404);
            }

            $idOc = (int)$factura['id_compra'];
            $montoFacturaActual = (float)$factura['monto_total'];

            // 2. VÍA 1: Obtener la cabecera de la OC usando tu getById() nativo
            $oc = $this->ordenCompraModel->getById($idOc);
            if (!$oc) {
                return ServiceResponse::error("La Orden de Compra asociada no existe.", 404);
            }
            $totalOC = (float)$oc['total'];

            // 3. VÍA 2: Calcular el valor de lo que FÍSICAMENTE ya entró al almacén (WMS)
            // Consultamos las partidas y los balances recibidos directamente de tus modelos
            $items = $this->ordenCompraModel->getDetails($idOc);
            $receptionBalances = $this->ordenCompraModel->getReceivedBalancesByOC($idOc);
            
            // Creamos un mapa [idrequisicionarticulo => cantidad_recibida] para cruce rápido
            $receivedMap = array_column($receptionBalances, 'total_recibido', 'idrequisicionarticulo');

            $totalRecibidoValor = 0.00;
            foreach ($items as $item) {
                $idPartida = $item['idrequisicionarticulo'];
                $cantRecibida = (float)($receivedMap[$idPartida] ?? 0);
                $costoPactado = (float)$item['costo_unitario'];
                
                // Valuamos la mercancía física que entró al almacén al costo pactado en la OC
                $totalRecibidoValor += ($cantRecibida * $costoPactado);
            }
            
            // Sumamos el IVA correspondiente (16%)
            $totalRecibidoValorConIva = $totalRecibidoValor * 1.16;

            // 4. VÍA 3: Obtener el acumulado de facturas previamente APROBADAS para esta OC
            $facturasPrevias = $this->facturaModel->getApprovedSumByOC($idOc, $invoiceId);
            $totalFacturadoPrevio = (float)($facturasPrevias['total_aprobado'] ?? 0);

            // El acumulado total que estaríamos pagando con esta factura
            $totalAcumuladoFacturado = $totalFacturadoPrevio + $montoFacturaActual;

            // =================================================================
            // EVALUACIÓN DE LAS 3 VÍAS (Cotejo de 3 Vías)
            // =================================================================
            $nuevoEstatus = 1; // 1 = Validada / Aprobada para Programación de Pago
            $comentarios = "Cotejo de 3 Vías Exitoso.";

            // Evaluación A: Contra la Orden de Compra (Sobreprecio)
            if ($totalAcumuladoFacturado > ($totalOC + self::TOLERANCIA_CENTAVOS)) {
                $nuevoEstatus = 2; // 2 = Rechazada (Excede el contrato)
                $comentarios = "Falla de Cotejo: El acumulado facturado (" . number_format($totalAcumuladoFacturado, 2) . ") excede el total pactado en la OC (" . number_format($totalOC, 2) . ").";
                return $this->updateInvoiceStatus($invoiceId, $nuevoEstatus, $comentarios);
            }

            // Evaluación B: Contra la Recepción Física (Almacén / WMS)
            if ($totalAcumuladoFacturado > ($totalRecibidoValorConIva + self::TOLERANCIA_CENTAVOS)) {
                // Bloqueo de Pago (Hold) por falta de entrada física en almacén
                $nuevoEstatus = 0; 
                $comentarios = "Congelada por 3-Way Match: El acumulado facturado (" . number_format($totalAcumuladoFacturado, 2) . ") excede el valor de la mercancía física recibida en almacén (" . number_format($totalRecibidoValorConIva, 2) . ").";
            }

            return $this->updateInvoiceStatus($invoiceId, $nuevoEstatus, $comentarios);

        } catch (\Exception $e) {
            return ServiceResponse::error("Error en el Motor de Conciliación: " . $e->getMessage(), 500);
        }
    }

    /**
     * Helper para actualizar el estatus de la conciliación
     */
    private function updateInvoiceStatus(int $invoiceId, int $estatus, string $comentarios): ServiceResponse
    {
        $this->facturaModel->updateValidationStatus($invoiceId, $estatus, $comentarios);
        
        $msgType = match($estatus) {
            1 => 'success',
            0 => 'warning',
            2 => 'error'
        };

        return ServiceResponse::success([
            'estatus_final' => $estatus,
            'comentarios'   => $comentarios
        ], "Conciliación completada con resultado: " . strtoupper($msgType));
    }
}