<?php
class Inv_kardex extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        //session_regenerate_id(true);
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MIKARDEX);
    }

    public function Inv_kardex()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Kardex";
        $data['page_title'] = "Kardex";
        $data['page_name'] = "Kardex";
        $data['page_functions_js'] = "functions_inv_kardex.js";
        $this->views->getView($this, "inv_kardex", $data);
    }

    public function getProductos()
    {
        $data = $this->model->selectProductos();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getKardex(int $inventarioid)
    {
        if ($inventarioid <= 0) {
            echo json_encode([]);
            die();
        }

        $data = $this->model->selectKardex($inventarioid);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getInfoProducto(int $inventarioid)
    {
        $producto = $this->model->selectProductoKardex($inventarioid);
        $resumen  = $this->model->selectResumenKardex($inventarioid);
        $totales  = $this->model->selectTotalesKardex($inventarioid);

        echo json_encode([
            'producto' => $producto,
            'resumen'  => $resumen,
            'totales'  => $totales
        ], JSON_UNESCAPED_UNICODE);

        die();
    }
}
