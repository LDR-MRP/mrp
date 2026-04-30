<?php

class WarehouseService
{
    use Loggable;

    private Inv_almacenesModel $inventarioModel;

    public function __construct() {
        $this->inventarioModel = new Inv_almacenesModel();
    }

    public function getAll(): ServiceResponse
    {
        $warehouses = $this->inventarioModel->selectAlmacenes();
        return ServiceResponse::success($warehouses);
    }
}