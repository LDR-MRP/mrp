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
}
?>