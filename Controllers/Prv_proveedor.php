<?php

class Prv_proveedor  extends Controllers{

    use ApiResponser;

    protected $supplierService;

    public function __construct()
    {
        parent::__construct();

        session_start();

        getPermisos(COM_PROVEEDORES);

        $this->supplierService = new Prv_proveedorService($this->model);
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

    public function create() {
        $this->views->getView(
            $this,
            "../Prv_proveedor/prv_proveedor_create",
            [
                'page_tag' => "Nuevo",
                'page_title' => "Nuevo",
                'page_name' => "Nuevo",
                'page_functions_js' => "functions_prv_proveedores_create.js",
            ]
        );
    }

    /* ======================================================
     * API (JSON): listar, mostrar, crear, eliminar
     * ====================================================== */
    public function index()
    {
        return $this->apiResponse($this->supplierService->findByCriteria($filters = sanitizeGet()));
    }

    public function store()
    {
        return $this->apiResponse($this->supplierService->store($_POST));
    }
}