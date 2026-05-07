<?php

class Inv_productossustitutos extends Controllers
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        getPermisos(MIPRODUCTOSSUSTITUTOS);
        $this->service = new Inv_productossustitutosService();
    }

    public function Inv_productossustitutos()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
            die();
        }

        $data['page_tag'] = "Productos Sustitutos";
        $data['page_title'] = "Bandeja de Productos Sustitutos";
        $data['page_name'] = "Productos Sustitutos";
        $data['page_functions_js'] = "functions_inv_productossustitutos.js";

        $this->views->getView($this, "inv_productossustitutos", $data);
    }

    public function getListas()
    {
        echo json_encode($this->service->getListas());
        die();
    }

    public function setLista()
    {
        echo json_encode($this->service->setLista($_POST));
        die();
    }

    public function getProductosLista($id)
    {
        echo json_encode($this->service->getProductosLista((int)$id));
        die();
    }

    public function setProductoLista()
    {
        echo json_encode($this->service->setProductoLista($_POST));
        die();
    }

    public function getInventario()
    {
        $search = strClean($_GET['search'] ?? '');
        $tipo   = strClean($_GET['tipo'] ?? '');

        echo json_encode($this->service->getInventario($search, $tipo));
        die();
    }

    public function getLista($id)
    {
        echo json_encode($this->service->getLista((int)$id));
        die();
    }

    public function updateLista()
    {
        echo json_encode($this->service->updateLista($_POST));
        die();
    }

    public function deleteProductoLista()
    {
        echo json_encode($this->service->deleteProductoLista($_POST));
        die();
    }

    //movimientos entre listas: pestaña 3
    public function moverProductosLista()
    {
        echo json_encode($this->service->moverProductosLista($_POST));
        die();
    }
}
