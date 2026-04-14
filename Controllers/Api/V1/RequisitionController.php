<?php
namespace Controllers\Api\V1;

use ServiceResponse;
use Services\RequisitionService;

class RequisitionController
{
    use \ApiResponser;

    protected \RequisitionService $requisitionService;

    public function __construct()
    {
        $this->requisitionService = new \RequisitionService;
    }

    public function index()
    {
        return $this->apiResponse($this->requisitionService->index());
    }

    public function show(int $id)
    {
        // TODO: En un futuro, esto vendrá del AuthMiddleware
        $userId = 19;
        return $this->apiResponse($this->requisitionService->getRequisitionWithDetails((int)$id, $userId));
    }

    public function moveItems(int $id)
    {
        // TODO: En un futuro, esto vendrá del AuthMiddleware
        $userId = 19;
        return $this->apiResponse($this->requisitionService->moveItems((int)$id, $userId));
    }

    public function deleteItem(int $id, int $itemId)
    {
        // TODO: En un futuro, esto vendrá del AuthMiddleware
        $userId = 19;
        return $this->apiResponse($this->requisitionService->deleteItem((int)$id, (int)$itemId, (int)$userId));
    }
}