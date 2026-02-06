<?php
class Inv_movimientosinventario extends Controllers
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
        getPermisos(MIMOVIMIENTOS);
    }

    public function Inv_movimientosinventario()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Movimientos Inventario";
        $data['page_title'] = "Movimientos Inventario";
        $data['page_name'] = "Movimientos Inventario";
        $data['page_functions_js'] = "functions_inv_movimientosinventario.js";
        $this->views->getView($this, "inv_movimientosinventario", $data);
    }

    //CAPTURAR UNA NUEVO MOVIMIENTO
    public function setMovimiento()
    {
        if ($_POST) {

            if (
                empty($_POST['inventarioid'][0]) ||
                empty($_POST['almacenid']) ||
                empty($_POST['concepmovid']) ||
                empty($_POST['cantidad'][0]) ||
                empty($_POST['costo_cantidad'])
            ) {
                echo json_encode(['status' => false, 'msg' => 'Datos incompletos'], JSON_UNESCAPED_UNICODE);
                die();
            }

            $almacenid   = intval($_POST['almacenid']);
            $concepmovid = intval($_POST['concepmovid']);
            $referencia  = strClean($_POST['referencia']);

            $inventarios = $_POST['inventarioid'];
            $cantidades  = $_POST['cantidad'];
            $costos      = $_POST['costo_cantidad'];

            $request = $this->model->insertMovimientoMasivo(
                $almacenid,
                $concepmovid,
                $referencia,
                $inventarios,
                $cantidades,
                $costos
            );

            if ($request === true) {
                $arrResponse = ['status' => true, 'msg' => 'Movimiento registrado correctamente'];
            } else {
                $arrResponse = ['status' => false, 'msg' => $request];
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }
    }


    public function getSelectAlmacenes()
    {
        if ($_SESSION['permisosMod']['r']) {
            $htmlOptions = '<option value="">-- Seleccione almacén --</option>';
            $arrData = $this->model->selectAlmacenes();

            if (count($arrData) > 0) {
                for ($i = 0; $i < count($arrData); $i++) {
                    if ($arrData[$i]['estado'] == 2) {
                        $htmlOptions .= '<option value="' . $arrData[$i]['idalmacen'] . '">'
                            . $arrData[$i]['descripcion'] .
                            '</option>';
                    }
                }
            }
            echo $htmlOptions;
        }
        die();
    }

    public function getSelectInventario()
    {
        $htmlOptions = '<option value="">-- Seleccione producto --</option>';
        $arrData = $this->model->selectInventario();

        foreach ($arrData as $row) {
            $htmlOptions .= '<option value="' . $row['idinventario'] . '">'
                . $row['descripcion'] .
                '</option>';
        }
        echo $htmlOptions;
        die();
    }


    public function getSelectConceptos()
    {
        $htmlOptions = '<option value="">-- Seleccione concepto --</option>';
        $arrData = $this->model->selectConceptos();

        foreach ($arrData as $row) {
            $htmlOptions .= '<option value="' . $row['idconcepmov'] . '">'
                . $row['descripcion'] .
                '</option>';
        }
        echo $htmlOptions;
        die();
    }

    public function getMovimientos()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectMovimientos();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getSelectInventarioJson()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectInventarioPredictivo();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getConceptoInfo($id)
{
    $arrData = $this->model->selectConceptoInfo($id);
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();
}

}
