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

            $id_proveedor = !empty($_POST['id_proveedor']) ? intval($_POST['id_proveedor']) : null;
            $lote = !empty($_POST['lote']) ? strClean($_POST['lote']) : null;
            $id_cliente = !empty($_POST['id_cliente']) ? intval($_POST['id_cliente']) : null;

            // VINs por partida: un JSON por renglón, mismo índice que inventarioid[]
            $series = [];
            foreach ($inventarios as $i => $inv) {
                $json = $_POST['series_json'][$i] ?? '[]';
                $ids = json_decode($json, true);
                $series[$i] = is_array($ids) ? array_map('intval', $ids) : [];
            }
            $usuarioid = !empty($_SESSION['idUser']) ? intval($_SESSION['idUser']) : null;

            $request = $this->model->insertMovimientoMasivo(
                $almacenid,
                $concepmovid,
                $referencia,
                $inventarios,
                $cantidades,
                $costos,
                $id_proveedor,
                $lote,
                $id_cliente,
                $series,
                $usuarioid
            );

            if (is_array($request)) {
                $arrResponse = [
                    'status' => true,
                    'msg' => 'Movimiento registrado correctamente',
                    'numero_movimiento' => $request['numero_movimiento'],
                    'almacenid' => $request['almacenid']
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

    public function getSelectProveedores()
    {
        if ($_SESSION['permisosMod']['r']) {
            $htmlOptions = '<option value="">-- Seleccione proveedor --</option>';
            $arrData = $this->model->selectProveedores();

            foreach ($arrData as $row) {
                $htmlOptions .= '<option value="' . $row['id_proveedor'] . '">'
                    . $row['nombre'] .
                    '</option>';
            }
            echo $htmlOptions;
        }
        die();
    }

    public function getSelectClientes()
    {
        if ($_SESSION['permisosMod']['r']) {
            $htmlOptions = '<option value="">-- Seleccione cliente --</option>';
            $arrData = $this->model->selectClientes();

            foreach ($arrData as $row) {
                $texto = $row['codigo_cliente'] !== '' && $row['codigo_cliente'] !== null
                    ? $row['codigo_cliente'] . ' - ' . $row['nombre']
                    : $row['nombre'];
                $htmlOptions .= '<option value="' . $row['idcliente'] . '">'
                    . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') .
                    '</option>';
            }
            echo $htmlOptions;
        }
        die();
    }

    public function getVinesDisponibles()
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $inventarioid = intval($_GET['inventarioid'] ?? 0);
            $almacenid = intval($_GET['almacenid'] ?? 0);
            $arrData = ($inventarioid && $almacenid)
                ? $this->model->selectVinesDisponibles($inventarioid, $almacenid)
                : [];
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
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
