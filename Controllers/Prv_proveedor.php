<?php

class Prv_proveedor  extends Controllers{

    use ApiResponser;

    public function __construct()
    {
        parent::__construct();

        session_start();

        getPermisos(PRV_PROVEEDORES);
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
            ]
        );
    }
}