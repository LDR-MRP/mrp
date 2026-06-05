<?php
namespace Controllers\Api\V1;

class InventoryReceptionController
{
    use \ApiResponser; // Asumiendo que usas este trait para las respuestas

    protected \InventoryReceptionService $inventoryReceptionService;

    public array $request = [];

    public function __construct() {
        $this->inventoryReceptionService = new \InventoryReceptionService();
    }

    public function getPendingItems(int $id)
    {
        $response = $this->inventoryReceptionService->getCotejoData($id, $this->request['auth_user']);
        return $this->apiResponse($response);
    }

    /**
     * Registra la entrada física de mercancía (Three-Way Match).
     * POST /api/v1/inventory-receptions
     */
    public function store(): string
    {
        // Pasamos el payload completo y el contexto del usuario autenticado
        $response = $this->inventoryReceptionService->store($this->request['auth_user']);
        return $this->apiResponse($response);
    }
}
