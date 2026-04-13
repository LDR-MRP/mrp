<?php
namespace Controllers\Api\V1;

use Services\RequisitionService;

class RequisitionController
{
    use \ApiResponser;

    protected \RequisitionService $requisitionService;

    public function __construct()
    {
        $this->requisitionService = new \RequisitionService;
    }

    public function moveItems(int $id)
    {
        return $this->apiResponse($this->requisitionService->moveItems($id, $userId = 19));
    }
}