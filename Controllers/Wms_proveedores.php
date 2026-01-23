<?php

class Wms_proveedores  extends Controllers{

    use ApiResponser;

    protected $proveedorService;

    public function __construct()
    {
        parent::__construct();

        $this->proveedorService = new Wms_proveedorService;

        $this->proveedorService->model = $this->model;
    }

    public function showAll()
    {
        return $this->apiResponse($this->proveedorService->showAll());
    }
}