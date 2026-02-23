<?php

class Prv_proveedor  extends Controllers{

    use ApiResponser;

    protected $supplierService;

    public function __construct()
    {
        parent::__construct();

        session_start();

        getPermisos(PRV_PROVEEDORES);

        $this->supplierService = new Prv_proveedorService($this->model);
    }

    public function Prv_proveedor() {
        $this->views->getView(
            $this,
            "../Prv_proveedor/index",
            [
                'page_tag' => "Proveedores",
                'page_title' => "Proveedores",
                'page_name' => "Proveedores",
                'page_functions_js' => "functions_prv_proveedores_index.js",
            ]
        );
    }

    public function create() {
        $this->views->getView(
            $this,
            "../Prv_proveedor/create",
            [
                'page_tag' => "Nuevo",
                'page_title' => "Nuevo",
                'page_name' => "Nuevo",
                'page_icon' => "ri-user-add-line",
                'page_action' => "Alta de Registro",
                'page_action_type' => "Nuevo Proveedor",
                'page_description' => "Complete la información fiscal y comercial para dar de alta al socio.",
                'page_functions_js' => "functions_prv_proveedores_create.js",
            ]
        );
    }

    public function edit() {
        $this->views->getView(
            $this,
            "../Prv_proveedor/create",
            [
                'page_tag' => "Editar",
                'page_title' => "Editar",
                'page_name' => "Editar",
                'page_icon' => "ri-edit-2-line",
                'page_action' => "Edición de Registro",
                'page_action_type' => "Editar Proveedor",
                'page_description' => "Complete la información fiscal y comercial para editar al socio.",
                'page_functions_js' => "functions_prv_proveedores_create.js",
                'supplier' => current($this->supplierService->findByCriteria(['idproveedor' =>$_GET['id']])->data),
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

    public function getKpi()
    {
        return $this->apiResponse($this->supplierService->getKpi());
    }

    public function delete()
    {
        return $this->apiResponse($this->supplierService->delete($_POST));
    }
    
    public function suppliers()
    {
        return $this->apiResponse($this->supplierService->suppliers($filters = sanitizeGet()));
    }
}