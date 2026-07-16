<?php

class Com_orden extends Controllers
{
    use ApiResponser;

    public function __construct()
    {
        parent::__construct();
        getPermisos(COM_ORDENES);
    }

    public function create()
    {
        $this->views->getView(
            $this,
            "../Com_ordenes/create",
            [
                'page_tag' => "Generar Órden de Compra",
                'page_title' => "Generar Órden de Compra",
                'page_name' => "Generar Órden de Compra",
                'page_functions_js' => "functions_com_ordenes_create.js",

            ]
        );
    }

    public function read()
    {
        $this->views->getView(
            $this,
            "../Com_ordenes/read",
            [
                'page_tag' => "Órden de Compra",
                'page_title' => "Órden de Compra",
                'page_name' => "Órden de Compra",
                'page_functions_js' => "functions_com_ordenes_read.js",

            ]
        );
    }

    public function Com_orden()
    {
        $this->views->getView(
            $this,
            "../Com_ordenes/index",
            [
                'page_tag' => "Órdenes de Compra",
                'page_title' => "Órdenes de Compra",
                'page_name' => "Órdenes de Compra",
                'page_functions_js' => "functions_com_ordenes_index.js",

            ]
        );
    }
}
?>