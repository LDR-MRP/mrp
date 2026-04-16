<?php

class Com_requisicion extends Controllers
{
    use ApiResponser;

    protected $requisitionService;

    protected $usuariosModel;

    public function __construct()
    {
        parent::__construct();
        session_start();

        getPermisos(COM_COMPRAS);
        $this->usuariosModel = new UsuariosModel;
    }

    public function Com_requisicion() {
        $this->views->getView(
            $this,
            "../Com_requisiciones/index",
            [
                'page_tag' => "Bandeja de Requisiciones",
                'page_title' => "Bandeja de Requisiciones",
                'page_name' => "Bandeja de Requisiciones",
                'page_functions_js' => "functions_com_requisiciones_index.js",

            ]
        );
    }

    public function create() {
        $this->views->getView(
            $this,
            "../Com_requisiciones/create",
            [
                'page_tag' => "Nueva Requisición",
                'page_title' => "Nueva Requisición",
                'page_name' => "Nueva Requisición",
                'page_functions_js' => "functions_com_requisiciones_create.js",
            ]
        );
    }

    public function read() {
        $this->views->getView(
            $this,
            "../Com_requisiciones/read",
            [
                'page_tag' => "Requisición",
                'page_title' => "Requisición",
                'page_name' => "Requisición",
                'page_functions_js' => "functions_com_requisiciones_read.js",
            ]
        );
    }
}
