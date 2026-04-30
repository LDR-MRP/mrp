<?php
namespace Controllers\Api\V1;

class WarehouseController
{
    use \ApiResponser;

    protected \WarehouseService $warehouseService;

    public function __construct()
    {
        $this->warehouseService = new \WarehouseService;
    }

    public function index()
    {
        return $this->apiResponse($this->warehouseService->getAll());
    }
}