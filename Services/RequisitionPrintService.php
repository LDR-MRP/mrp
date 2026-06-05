<?php
//namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Helpers\NumberToLetter;

class RequisitionPrintService
{
    use \Loggable;

    private readonly Com_requisicionModel $requisicionModel;

    protected object $db;

    public function __construct()
    {
        $this->requisicionModel = new Com_requisicionModel();
        $this->db = $this->requisicionModel->getConexion();
    }

    public function generatePdf(int $id, array $userContext): ServiceResponse
    {
        try{
            $role = RoleEnum::tryFrom((int)$userContext['rolid']);
            $scope = $role->getScope();

            // 1. SEGURIDAD: Validar IDOR y permisos (reutilizamos la lógica de Matrix)
            $requisition = $this->requisicionModel->getRequisitionForPrint($id);
            if (!$requisition) throw new \Exception("No existe.", 404);

            // APLICACIÓN DE LA MATRIZ DE VISIBILIDAD
            $isAllowed = match($scope) {
                'propio' => (int)$requisition['usuarioid'] === (int)$userContext['id'],
                'planta'  => (int)$requisition['plantaid'] === (int)$userContext['plantaid'],
                'total'  => true,
                default  => false
            };

            // 2. Validación de Seguridad (IDOR de Lectura)
            if (!$isAllowed) {
                return ServiceResponse::error("Security Error: No tienes permisos para ver esta requisición.", 403);
            }

            // Calculamos el total con IVA para la letra
            $totalConIva = (float)$requisition['monto_estimado'] * 1.16;

            // Inyectamos la conversión a letras
            $requisition['monto_letras'] = NumberToLetter::convert($totalConIva);

            // . CONFIGURAR MARCA DE AGUA SEGÚN STATE MACHINE
            $watermark = $this->getWatermarkConfig($requisition['estatus']);

            // . RENDERIZAR HTML (Usando un buffer de salida)
            ob_start();
            $data = $requisition; // Datos para la vista
            require __DIR__ . '/../Views/Com_requisiciones/requisition_template.php';
            $html = ob_get_clean();

            // 5. CONFIGURAR DOMPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true); // Para cargar logos

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return ServiceResponse::success([
                'content' => $dompdf->output(),
                'filename' => "REQ_{$id}_" . date('Ymd') . ".pdf"
            ]);

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

    private function getWatermarkConfig(string $status): ?array
    {
        return match ($status) {
            'borrador'      => ['text' => 'BORRADOR', 'color' => 'rgba(200, 200, 200, 0.4)'],
            'pendiente', 
            'pendiente_l2'  => ['text' => 'PENDIENTE', 'color' => 'rgba(255, 165, 0, 0.3)'],
            'rechazada', 
            'cancelada'     => ['text' => 'ANULADA', 'color' => 'rgba(255, 0, 0, 0.2)'],
            'aprobada'      => null, // Sin marca de agua si ya está autorizada
            default         => ['text' => 'DOCUMENTO NO VÁLIDO', 'color' => 'rgba(0, 0, 0, 0.1)']
        };
    }
}