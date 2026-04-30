<?php

class SupplierService
{
    use Loggable;

    private Prv_proveedorModel $prvProveedorModel;

    protected $userId;

    public function __construct() {
        $this->prvProveedorModel = new Prv_proveedorModel();
    }

    public function findByCriteria(array $filters = []): ServiceResponse
    {
        $suppliers = $this->prvProveedorModel->findByCriteria($filters);
        return ServiceResponse::success($suppliers);
    }
}