<?php
namespace Controllers\Api\V1;

use Services\PurchaseOrderService;

class PurchaseOrderController {
    
    use \ApiResponser; // Asumiendo que usas este trait para las respuestas

    protected \PurchaseOrderService $purchaseOrderService;

    public function __construct() {
        $this->purchaseOrderService = new \PurchaseOrderService();
    }

    /**
     * POST /api/v1/purchase-orders
     */
    public function store() {
        // TODO: Extraer del Middleware JWT cuando esté implementado
        $authenticatedUserId = 1; 

        $serviceResponse = $this->purchaseOrderService->store($authenticatedUserId);
        
        return $this->apiResponse($serviceResponse);
    }

    /**
     * GET /api/v1/purchase-orders/{id}
     */
    public function show($id) {
        // TODO: Extraer del Middleware JWT cuando esté implementado
        $authenticatedUserId = 1; 

        $serviceResponse = $this->purchaseOrderService->getWithDetails($id, $authenticatedUserId);
        
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

        $res = $this->purchaseOrderService->index($filters);
        return $this->apiResponse($res);
    }
}
?>