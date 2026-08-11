<?php

class Inv_traslados extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        getPermisos(MITRASLADOS);
    }

    public function Inv_traslados()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }

        $data['page_tag'] = "Traslados";
        $data['page_title'] = "Traslados";
        $data['page_name'] = "Traslados";
        $data['page_functions_js'] = "functions_inv_info_traslados.js";

        $this->views->getView($this, "inv_traslados", $data);
    }

    public function nuevo_traslado()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }

        $data['page_tag'] = "Nueva Solicitud";
        $data['page_title'] = "Nueva Solicitud";
        $data['page_name'] = "Nueva Solicitud";
        $data['page_functions_js'] = "functions_inv_traslados.js";

        $this->views->getView($this, "nuevo_traslado", $data);
    }

    /* =====================================================
       ALMACENES
    ===================================================== */

    public function getSelectAlmacenes()
    {
        if ($_SESSION['permisosMod']['r']) {

            $htmlOptions = '<option value="">-- Seleccione almacén --</option>';

            $arrData = $this->model->selectAlmacenes();

            foreach ($arrData as $row) {

                $htmlOptions .= '<option value="' . $row['idalmacen'] . '">'
                    . $row['descripcion']
                    . '</option>';
            }

            echo $htmlOptions;
        }

        die();
    }

    public function getUnidadesPorAlmacen($idalmacen)
    {

        try {


            $arrData =
                $this->model->selectUnidadesPorAlmacen(
                    intval($idalmacen)
                );


            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE
            );
        } catch (Exception $e) {


            http_response_code(500);


            echo json_encode([
                "error" => $e->getMessage()
            ]);
        }


        die();
    }

    /* =====================================================
       TRANSPORTISTAS
    ===================================================== */

    public function getTransportistas()
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->selectTransportistas();

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* =====================================================
       LISTADO
    ===================================================== */

    public function getTraslados()
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->selectTraslados();

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* =====================================================
       DETALLE
    ===================================================== */

    public function getDetalle($id)
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->getDetalleTraslado(
                intval($id)
            );

            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE
            );
        }

        die();
    }

    /* =====================================================
       GUARDAR
    ===================================================== */

    public function setTraslado()
    {
        if ($_POST) {

            $almacen_origenid  = intval($_POST['almacen_origenid']);
            $almacen_destinoid = intval($_POST['almacen_destinoid']);

            $tipo_traslado = strClean($_POST['tipo_traslado']);

            $proveedorid = intval($_POST['id_proveedor']);

            $fecha_programada = strClean(
                $_POST['fecha_programada']
            );

            $nombre_trasladista = strClean($_POST['nombre_trasladista']);
            $contacto_trasladista = strClean($_POST['correo_trasladista']);
            $numero_licencia = strClean($_POST['numero_licencia']);
            $vigencia_licencia = strClean($_POST['vigencia_licencia']);

            $observaciones = strClean(
                $_POST['observaciones']
            );

            $unidades = json_decode(
                $_POST['unidades'],
                true
            );

            $archivoLicencia = "";

            if (!empty($_FILES['archivo_licencia']['name'])) {

                $nombreArchivo =
                    time() . "_" .
                    basename($_FILES['archivo_licencia']['name']);

                $ruta =
                    "Assets/uploads/trasladistas/licencias_conducir/" .
                    $nombreArchivo;

                move_uploaded_file(
                    $_FILES['archivo_licencia']['tmp_name'],
                    $ruta
                );

                $archivoLicencia = $nombreArchivo;
            }


            if (empty($unidades)) {

                echo json_encode([
                    'status' => false,
                    'msg' => 'Debe agregar al menos una unidad'
                ]);

                die();
            }

            $request = $this->model->insertTraslado(
                $almacen_origenid,
                $almacen_destinoid,
                $tipo_traslado,
                $proveedorid,
                $fecha_programada,
                $observaciones,
                $_SESSION['idUser'],
                $unidades,

                $nombre_trasladista,
                $contacto_trasladista,
                $numero_licencia,
                $vigencia_licencia,
                $archivoLicencia
            );

            if ($request === true) {

                $arrResponse = [
                    'status' => true,
                    'msg' => 'Traslado registrado correctamente'
                ];
            } else if (is_array($request) && isset($request['error'])) {


                $arrResponse = [

                    'status' => false,

                    'msg' => $request['error']

                ];
            } else {

                $arrResponse = [
                    'status' => false,
                    'msg' => 'No fue posible registrar el traslado'
                ];
            }

            echo json_encode(
                $arrResponse,
                JSON_UNESCAPED_UNICODE
            );
        }

        die();
    }

    public function getUnidadesJson()
    {

        try {


            $arrData = $this->model->selectUnidades();


            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE
            );
        } catch (Exception $e) {


            http_response_code(500);

            echo json_encode([
                "error" => $e->getMessage()
            ]);
        }


        die();
    }

    public function validarUnidadPendiente($vinid)
    {

        if ($_SESSION['permisosMod']['r']) {


            $arrData =
                $this->model->validarUnidadPendiente(
                    intval($vinid)
                );


            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE
            );
        }


        die();
    }

    public function imprimirTraslado($idTraslado)
    {

        if (empty($_SESSION['permisosMod']['r'])) {

            header("Location:" . base_url() . '/dashboard');
            die();
        }


        $arrData = $this->model->getHojaTraslado(
            intval($idTraslado)
        );


        if (empty($arrData['traslado'])) {

            die("Traslado no encontrado");
        }


        $data = [

            'traslado' => $arrData['traslado'],

            'detalle' => $arrData['detalle'],

            'trasladista' => $arrData['trasladista']

        ];


        $this->views->getView(
            $this,
            "imprimirTraslado",
            $data
        );
    }

    public function verTraslado($idTraslado)
{

    $arrData = $this->model->getHojaTraslado(
        intval($idTraslado)
    );


    if(empty($arrData['traslado'])){

        die("Traslado no encontrado");

    }


    $data = [

        'traslado'=>$arrData['traslado'],
        'detalle'=>$arrData['detalle'],
        'trasladista'=>$arrData['trasladista']

    ];


    $this->views->getView(
        $this,
        "verTraslado",
        $data
    );

}

public function getTrasladoPdf($idTraslado)
{
    if (empty($_SESSION['permisosMod']['r'])) {
        echo json_encode([
            'status' => false,
            'msg' => 'No tiene permisos.'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idTraslado = intval($idTraslado);

    if ($idTraslado <= 0) {
        echo json_encode([
            'status' => false,
            'msg' => 'Traslado inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    $arrData = $this->model->getHojaTraslado($idTraslado);

    if (empty($arrData['traslado'])) {
        echo json_encode([
            'status' => false,
            'msg' => 'No se encontró información del traslado.'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    $folio = $arrData['traslado']['folio'];

    echo json_encode([
        'status'  => true,
        'data'    => $arrData,
        'url_qr' => base_url() . "/Inv_operaciones_traslados/escanear/" . urlencode($folio)
    ], JSON_UNESCAPED_UNICODE);
    die();
}
}
