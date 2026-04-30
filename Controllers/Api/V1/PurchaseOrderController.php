<?php
namespace Controllers\Api\V1;

class PurchaseOrderController {
    
    use \ApiResponser; // Asumiendo que usas este trait para las respuestas

    protected \PurchaseOrderService $purchaseOrderService;

    protected \PurchaseOrderPrintService $purchaseOrderPrintService;

    public array $request = [];

    public function __construct() {
        $this->purchaseOrderService = new \PurchaseOrderService();
        $this->purchaseOrderPrintService = new \PurchaseOrderPrintService();
    }

    /**
     * POST /api/v1/purchase-orders
     */
    public function store() {

        $serviceResponse = $this->purchaseOrderService->store($this->request['auth_user']);
        
        return $this->apiResponse($serviceResponse);
    }

    /**
     * GET /api/v1/purchase-orders/{id}
     */
    public function show(int $id) {
        
        $serviceResponse = $this->purchaseOrderService->getWithDetails($id, $this->request['auth_user']);
        
        return $this->apiResponse($serviceResponse);
    }

    // GET /api/v1/purchase-orders
    public function index() {
        // Capturamos los filtros de la URL (Query Params)
        $filters = [
            'proveedorid' => $_GET['proveedorid'] ?? null,
            'estatus'     => $_GET['estatus'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];

        $res = $this->purchaseOrderService->index($filters, $this->request['auth_user']);
        return $this->apiResponse($res);
    }

    /**
     * Genera y descarga el PDF de la requisición.
     * GET /api/v1/requisitions/{id}/pdf
     */
    public function generatePdf(int $id)
    {
        // 1. Llamamos al service pasándole el contexto del usuario (JWT)
        $serviceResponse = $this->purchaseOrderPrintService->generatePdf((int)$id, $this->request['auth_user']);

        // 2. Si el Service falló (IDOR, 403, 404), devolvemos JSON estándar
        if (!$serviceResponse) {
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
?>