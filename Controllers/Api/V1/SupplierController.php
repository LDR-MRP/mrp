<?php
namespace Controllers\Api\V1;

class SupplierController
{
    use \ApiResponser;

    protected \Prv_proveedorService $prvProveedorService;

    public function __construct()
    {
        $this->prvProveedorService = new \Prv_proveedorService;
    }

    public function index()
    {
        return $this->apiResponse($this->prvProveedorService->findByCriteria());
    }

    public function show(string $id)
    {
        return $this->apiResponse($this->prvProveedorService->findByCriteria(['id_proveedor' => $id]));
    }
}