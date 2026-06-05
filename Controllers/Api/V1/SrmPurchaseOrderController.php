<?php

namespace Controllers\Api\V1;

class SrmPurchaseOrderController
{
    // Inyectamos el trait nativo para estandarizar respuestas
    use \ApiResponser; 

    private \PurchaseOrderService $purchaseOrderService;
    private \PurchaseOrderPrintService $purchaseOrderPrintService;

    public array $request = [];

    public function __construct()
    {
        $this->purchaseOrderService = new \PurchaseOrderService();
        $this->purchaseOrderPrintService = new \PurchaseOrderPrintService();
    }

    /**
     * GET /api/v1/srm/dashboard/summary
     */
    public function index()
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext || $userContext['rol'] !== 'VENDOR') {
            return $this->errorResponse('Acceso denegado. Credenciales insuficientes.', 403);
        }

        $vendorId = (int) ($userContext['vendor_id'] ?? 0);
        if ($vendorId <= 0) {
            return $this->errorResponse('Perfil de proveedor no asociado.', 400);
        }

        $filters = [
            'proveedorid' => $vendorId,
            'estatus'     => $_GET['estatus'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];

        // El servicio retorna un ServiceResponse...
        $response = $this->purchaseOrderService->index($filters, $userContext);

        // ...y el Trait se encarga de formatear, poner los headers, el código HTTP y hacer el exit;
        return $this->apiResponse($response); 
    }

    /**
     * Endpoint para obtener el estatus del expediente.
     * GET /api/v1/srm/dossier
     */
    public function show(string $id): string
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext || $userContext['is_vendor'] === false) {
            return $this->errorResponse('Acceso denegado. Credenciales insuficientes.', 403);
        }
        
        $vendorId = (int) ($userContext['vendor_id'] ?? 0);
        if ($vendorId <= 0) {
            return $this->errorResponse('Perfil de proveedor no asociado.', 400);
        }

        $response = $this->purchaseOrderService->getWithDetails($id, $userContext);

        return $this->apiResponse($response);
    }

    public function generatePdf(int $id)
    {
        // 1. Llamamos al service pasándole el contexto del usuario (JWT)
        $serviceResponse = $this->purchaseOrderPrintService->generatePdf((int)$id, $this->request['auth_user']);

        // 2. Si el Service falló (IDOR, 403, 404), devolvemos JSON estándar
        if (!$serviceResponse->success) {
            return $this->apiResponse($serviceResponse);
        }

        // 3. Si tuvo éxito, extraemos el binario y los headers
        $pdfData = $serviceResponse->data;

        // Limpiar buffers para evitar basura en el PDF
        if (ob_get_length()) ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"{$pdfData['filename']}\"");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        echo $pdfData['content'];
        exit;
    }
}