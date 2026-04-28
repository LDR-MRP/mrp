<?php
namespace Controllers\Api\V1;

use Services\PurchaseOrderService;

class PurchaseOrderController {
    
    use \ApiResponser; // Asumiendo que usas este trait para las respuestas

    protected \PurchaseOrderService $purchaseOrderService;

    public array $request = [];

    public function __construct() {
        $this->purchaseOrderService = new \PurchaseOrderService();
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

        $res = $this->purchaseOrderService->index($filters, $this->request);
        return $this->apiResponse($res);
    }
}
?>