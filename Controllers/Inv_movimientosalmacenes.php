<?php
class Inv_movimientosalmacenes extends Controllers
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
        getPermisos(MIMOVIMIENTOSALMACEN);
    }

    public function Inv_movimientosalmacenes()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Traspaso entre almacenes";
        $data['page_title'] = "Traspaso entre almacenes";
        $data['page_name'] = "Traspaso entre almacenes";
        $data['page_functions_js'] = "functions_inv_movimientosalmacenes.js";
        $this->views->getView($this, "inv_movimientosalmacenes", $data);
    }


    //CAPTURAR UNA NUEVO MOVIMIENTO
    public function setMovimiento()
    {
        if ($_POST) {

            $almacen_origenid  = intval($_POST['almacen_origenid']);
            $almacen_destinoid = intval($_POST['almacen_destinoid']);
            $referencia        = strClean($_POST['referencia']);

            $inventarios = $_POST['inventarioid'];
            $cantidades  = $_POST['cantidad'];
            $costos      = $_POST['costo_cantidad'];

            $request = $this->model->insertTransferencia(
                $almacen_origenid,
                $almacen_destinoid,
                $referencia,
                $inventarios,
                $cantidades,
                $costos
            );

            if ($request) {
                $arrResponse = [
                    'status' => true,
                    'msg' => 'Transferencia realizada correctamente',
                    'folio' => $request,
                    'almacenid' => $almacen_origenid   // 🔥 AGREGA ESTO
                ];
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

            $almacen     = isset($_GET['almacen']) ? intval($_GET['almacen']) : 0;
            $concepto    = isset($_GET['concepto']) ? intval($_GET['concepto']) : 0;
            $fechaInicio = $_GET['fechaInicio'] ?? '';
            $fechaFin    = $_GET['fechaFin'] ?? '';

            $arrData = $this->model->selectMovimientos($almacen, $concepto, $fechaInicio, $fechaFin);

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

    public function reporte($params)
    {
        ob_start();

        $arr = explode(',', $params);

        $numero    = $arr[0] ?? '';
        $almacenid = intval($arr[1] ?? 0);

        if (empty($numero) || !$almacenid) {
            exit;
        }

        $data['movimiento'] = $this->model->getMovimientoReporte($numero, $almacenid);
        $data['detalle']    = $this->model->getDetalleMovimientoReporte($numero, $almacenid);

        // Generar HTML
        ob_start();
        $this->views->getView($this, "reporte_movimiento", $data);
        $html = ob_get_clean();

        // Limpiar cualquier salida previa
        if (ob_get_length()) {
            ob_end_clean();
        }

        require_once("Libraries/html2pdf/vendor/autoload.php");

        $pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'A4', 'es', 'true', 'UTF-8');
        $pdf->writeHTML($html);
        $pdf->output("Movimiento_$numero.pdf", "I");

        exit;
    }
}
