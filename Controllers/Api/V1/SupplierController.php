<?php
namespace Controllers\Api\V1;

class SupplierController
{
    use \ApiResponser;

    protected \SupplierService $supplierService;

    public function __construct()
    {
        $this->supplierService = new \SupplierService;
    }

    public function index()
    {
        return $this->apiResponse($this->supplierService->findByCriteria());
    }

    public function show(string $id)
    {
        return $this->apiResponse($this->supplierService->findByCriteria(['id_proveedor' => $id]));
    }
}