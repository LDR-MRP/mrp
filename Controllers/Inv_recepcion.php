<?php

class Inv_recepcion extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        getPermisos(COM_COMPRAS);
    }

    public function create()
    {
        $this->views->getView(
            $this,
            "../Inv_recepcion/create",
            [
                'page_tag' => "Recepción",
                'page_title' => "Recepción",
                'page_name' => "Recepción",
                'page_functions_js' => "functions_inv_recepcion_create.js",

            ]
        );
    }
}
