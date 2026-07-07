<?php

class Orders extends Controllers
{
    public function __construct()
    {

        parent::__construct();
    }

public function home()
{
    $data['page_tag'] = "Portal de Pedidos - LDR Solutions";
    $data['page_name'] = "home";
    // $data['page_functions_js'] = ["orders/home.js"];
    $this->views->getView($this, "../Orders/home", $data);
}

public function login()
{
    $data['page_tag'] = "Iniciar sesión - LDR Solutions";
    $data['page_name'] = "login";
    $data['page_functions_js'] = ["orders/login.js"];
    $this->views->getView($this, "../Orders/login", $data);
}

public function micuenta()
{
    $data['page_tag'] = "Mi cuenta - LDR Solutions";
    $data['page_name'] = "micuenta";
    $data['page_functions_js'] = ["orders/micuenta.js"];
    $this->views->getView($this, "../Orders/micuenta", $data);
}

public function carrito()
{
    $data['page_tag'] = "Carrito - LDR Solutions";
    $data['page_name'] = "carrito";
    $data['page_functions_js'] = ["orders/carrito.js"];
    $this->views->getView($this, "../Orders/carrito", $data);
}

}