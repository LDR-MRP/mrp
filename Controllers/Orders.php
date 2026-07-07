<?php

class Orders extends Controllers
{
    public function __construct()
    {

        parent::__construct();
    }

    public function home()
    {
        // Data inyectada a la vista
        $data['page_tag'] = "Portal de Pedidos - LDR Solutions";
        $data['page_functions_js'] = "orders/home.js"; 
        $this->views->getView($this,"../Orders/home",$data); 
    }   

}