<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use Helpers\NumberToLetter;

class PurchaseOrderPrintService
{
    use \Loggable;

    private readonly Com_ordenCompraModel $ordenCompraModel;
    protected object $db;

    public function __construct()
    {
        $this->ordenCompraModel = new Com_ordenCompraModel();
        $this->db = $this->ordenCompraModel->getConexion();
    }

    public function generatePdf(int $id, array $userContext): ServiceResponse
    {
        try {
            // 1. OBTENER DATA (Requiere un método específico en el modelo de OC)
            $poData = $this->ordenCompraModel->getPurchaseOrderForPrint($id);
            
            if (!$poData) {
                throw new \Exception("La Orden de Compra #{$id} no existe.", 404);
            }

            // 2. SEGURIDAD: Validar que el usuario tenga acceso a la planta de la OC
            $userPlantaId = (int)$userContext['plantaid'];
            $poPlantaId   = (int)$poData['plantaid'];
            $role         = RoleEnum::tryFrom((int)$userContext['rolid']);

            // Si no es admin y la planta no coincide, bloqueamos (IDOR)
            if ($role !== RoleEnum::ADMINISTRADOR && $userPlantaId !== $poPlantaId) {
                return ServiceResponse::error("Security Error: No tienes permisos para imprimir esta Orden de Compra.", 403);
            }

            // 3. PREPARACIÓN DE MONTOS
            // Usamos el TOTAL final de la OC para la letra
            $totalPO = (float)$poData['total'];
            $poData['monto_letras'] = NumberToLetter::convert($totalPO);

            // 4. CONFIGURAR MARCA DE AGUA (State Machine de la OC)
            $watermark = $this->getWatermarkConfig($poData['estatus']);

            // 5. RENDERIZAR HTML
            ob_start();
            $data = $poData; 
            // Apuntamos al template de OC que creamos antes
            require __DIR__ . '/../Views/Com_ordenes/purchase_order_template.php';
            $html = ob_get_clean();

            // 6. EJECUTAR DOMPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true); 

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return ServiceResponse::success([
                'content' => $dompdf->output(),
                'filename' => "ORDEN_COMPRA_{$id}_" . date('Ymd') . ".pdf"
            ]);

        } catch (\Exception $e) {
            $this->logMessage($e, \LogLevel::ERROR, ['action' => 'generatePoPdf', 'id' => $id]);
            return ServiceResponse::error($e->getMessage(), (int)$e->getCode() ?: 500);
        }
    }

    private function getWatermarkConfig(string $status): ?array
    {
        return match ($status) {
            'cancelada' => ['text' => 'ORDEN CANCELADA', 'color' => 'rgba(255, 0, 0, 0.2)'],
            'emitida'   => ['text' => 'NO ENVIADA', 'color' => 'rgba(41, 156, 219, 0.1)'],
            default     => null, // Limpio para órdenes activas/en tránsito/recibidas
        };
    }
}