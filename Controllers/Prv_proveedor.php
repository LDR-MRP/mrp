<?php

class Prv_proveedor  extends Controllers{

    use ApiResponser;

    protected $supplierService;

    public function __construct()
    {
        parent::__construct();

        session_start();

        getPermisos(COM_PROVEEDORES);

        $this->supplierService = new Prv_proveedorService;

        $this->supplierService->model = $this->model;
    }

    public function Prv_proveedor() {
        $this->views->getView(
            $this,
            "../Prv_proveedor/prv_proveedor",
            [
                'page_tag' => "Proveedores",
                'page_title' => "Proveedores",
                'page_name' => "Proveedores",
                'page_functions_js' => "functions_prv_proveedores.js",
            ]
        );
    }

    /* ======================================================
     * API (JSON): listar, mostrar, crear, eliminar
     * ====================================================== */
    public function index()
    {
        return $this->apiResponse($this->supplierService->index($filters = sanitizeGet()));
    }

    public function show()
    {
        return $this->apiResponse($this->supplierService->index($filters = sanitizeGet()));
    }
}