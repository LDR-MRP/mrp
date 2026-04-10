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

    public function getKardex()
    {
        $inventarioid = intval($_GET['inventarioid'] ?? 0);
        $almacen      = intval($_GET['almacen'] ?? 0);
        $concepto     = intval($_GET['concepto'] ?? 0);
        $fechaInicio  = $_GET['fechaInicio'] ?? '';
        $fechaFin     = $_GET['fechaFin'] ?? '';

        if ($inventarioid <= 0) {
            echo json_encode([]);
            die();
        }

        $data = $this->model->selectKardex(
            $inventarioid,
            $almacen,
            $concepto,
            $fechaInicio,
            $fechaFin
        );

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getSelectAlmacenes()
    {
        $html = '<option value="">Todos</option>';
        $data = $this->model->selectAlmacenes();

        foreach ($data as $row) {
            $html .= '<option value="' . $row['idalmacen'] . '">' . $row['descripcion'] . '</option>';
        }

        echo $html;
        die();
    }

    public function getSelectConceptos()
    {
        $html = '<option value="">Todos</option>';
        $data = $this->model->selectConceptos();

        foreach ($data as $row) {
            $html .= '<option value="' . $row['idconcepmov'] . '">' . $row['descripcion'] . '</option>';
        }

        echo $html;
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
