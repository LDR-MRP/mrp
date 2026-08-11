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

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        getPermisos(MIRECEPCION);
    }

    public function Inv_recepcion()
    {
        $data['page_tag'] = "Recepción";
        $data['page_title'] = "Recepción";
        $data['page_name'] = "Recepción";
        $data['page_functions_js'] = "functions_inv_recepcion.js";

        $this->views->getView($this, "inv_recepcion", $data);
    }

    public function getOrdenesCompraPendientes()
    {
        echo json_encode($this->model->selectOrdenesCompraPendientes(), JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getDetalleOC($idCompra)
    {
        echo json_encode($this->model->selectDetalleOC($idCompra), JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getHeaderOC($idCompra)
    {
        echo json_encode($this->model->selectHeaderOC($idCompra), JSON_UNESCAPED_UNICODE);
        die();
    }


    public function setRecepcion()
    {
        $idCompra = intval($_POST['compraid']);

        $recepcion = $this->model->getRecepcionByCompra($idCompra);

        if (!empty($recepcion) && $recepcion['estatus'] == 'cerrada') {

            echo json_encode([
                "status" => false,
                "msg" => "La recepción ya se encuentra cerrada y no puede modificarse."
            ]);
            die();
        }

        $detalle = json_decode($_POST['detalle'], true);
        $observaciones = $_POST['observaciones'] ?? '';
        $usuarioId = $_SESSION['idUser'];

        $almacenCompra =
            $this->model->getAlmacenCompra($idCompra);

        if (empty($almacenCompra)) {

            echo json_encode([
                "status" => false,
                "msg" => "No se encontró almacén para la orden de compra."
            ]);
            die();
        }

        $almacenid = intval(
            $almacenCompra['almacenid']
        );

        // =====================================
        // VALIDAR QUE EXISTA MATERIAL RECIBIDO
        // =====================================

        $tieneCantidades = false;

        foreach ($detalle as $item) {

            if (floatval($item['cantidad_recibida']) > 0) {
                $tieneCantidades = true;
                break;
            }
        }

        if (!$tieneCantidades) {

            echo json_encode([
                "status" => false,
                "msg" => "Debes capturar al menos una cantidad recibida."
            ]);
            die();
        }

        $recepcion = $this->model->getRecepcionByCompra($idCompra);

        if (empty($recepcion)) {
            $recepcionid = $this->model->insertRecepcion($idCompra, $observaciones, $usuarioId);
        } else {
            $recepcionid = $recepcion['idrecepcion'];
            $this->model->updateRecepcionHeader($recepcionid, $observaciones);
        }

        // =========================
        // DETALLE
        // =========================

        $almacenCompra = $this->model->getAlmacenCompra($idCompra);

        if (empty($almacenCompra)) {
            echo json_encode([
                "status" => false,
                "msg" => "La OC no tiene almacén asignado."
            ]);
            die();
        }

        $almacenid = intval($almacenCompra['almacenid']);
        foreach ($detalle as $item) {

            if ($item['cantidad_recibida'] <= 0) continue;

            $this->model->insertDetalleRecepcion(
                $recepcionid,
                $item['inventarioid'],
                $item['codigo'],
                $item['lote'],
                $item['cantidad_solicitada'],
                $item['cantidad_recibida'],
                $item['observaciones']
            );

            $cantidadRecibida = floatval(
                $item['cantidad_recibida']
            );

            $detalleCompra =
                $this->model->getDetalleCompraProducto(
                    $idCompra,
                    $item['inventarioid']
                );

            $costoUnitario =
                floatval($detalleCompra['costo_unitario']);

            $multi =
                $this->model->getMultiAlmacen(
                    $item['inventarioid'],
                    $almacenid
                );

            if ($multi) {

                $this->model->updateExistenciaMultiAlmacen(
                    $multi['idmultialmacen'],
                    $cantidadRecibida
                );
            } else {

                $this->model->insertMultiAlmacen(
                    $item['inventarioid'],
                    $almacenid,
                    $cantidadRecibida
                );
            }

            $existencia =
                $this->model->getExistenciaActual(
                    $item['inventarioid'],
                    $almacenid
                );

            $existenciaFinal = 0;

            if ($existencia) {
                $existenciaFinal =
                    floatval($existencia['existencia']);
            }

            $this->model->insertMovimientoInventario(
                $item['inventarioid'],
                $almacenid,
                'RC-' . str_pad(
                    $recepcionid,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
                'OC-' . $idCompra,
                $cantidadRecibida,
                $costoUnitario,
                $existenciaFinal
            );
        }

        // =========================
        // EVIDENCIAS / DOCUMENTOS
        // =========================
        if (!empty($_FILES['documentos']['name'][0])) {

            $rutaBase = __DIR__ . "/../Assets/uploads/recepciones/documentos/";

            if (!file_exists($rutaBase)) {
                mkdir($rutaBase, 0777, true);
            }

            foreach ($_FILES['documentos']['tmp_name'] as $key => $tmp) {

                $nombreOriginal = $_FILES['documentos']['name'][$key];

                $extension = strtolower(
                    pathinfo($nombreOriginal, PATHINFO_EXTENSION)
                );

                $nombreFinal =
                    uniqid('doc_') .
                    "_" .
                    time() .
                    "." .
                    $extension;

                $rutaFisica = $rutaBase . $nombreFinal;

                if (move_uploaded_file($tmp, $rutaFisica)) {

                    $this->model->insertDocumentoRecepcion(
                        $recepcionid,
                        $nombreFinal,
                        $nombreFinal
                    );
                }
            }
        }

        if ($this->model->recepcionCompleta($idCompra)) {

            $this->model->updateRecepcionStatus(
                $recepcionid,
                'cerrada'
            );
        } else {

            $this->model->updateRecepcionStatus(
                $recepcionid,
                'parcial'
            );
        }

        // =========================
        // EVIDENCIAS POR PRODUCTO
        // =========================
        if (!empty($_FILES['evidencias'])) {

            $rutaBase = __DIR__ . "/../Assets/uploads/recepciones/evidencias/";

            if (!file_exists($rutaBase)) {
                mkdir($rutaBase, 0777, true);
            }

            foreach ($_FILES['evidencias']['tmp_name'] as $detalleid => $files) {

                foreach ($files as $key => $tmp) {

                    if (empty($tmp)) {
                        continue;
                    }

                    $inventarioid = $_POST['evidencias_meta'][$detalleid]['inventarioid'] ?? null;

                    $nombreOriginal = $_FILES['evidencias']['name'][$detalleid][$key];

                    $extension = strtolower(
                        pathinfo($nombreOriginal, PATHINFO_EXTENSION)
                    );

                    $nombreArchivo =
                        uniqid('evi_') .
                        "_" .
                        time() .
                        "." .
                        $extension;

                    $rutaFinal = $rutaBase . $nombreArchivo;

                    if (move_uploaded_file($tmp, $rutaFinal)) {

                        $this->model->insertEvidenciaRecepcion(
                            $recepcionid,
                            $inventarioid,
                            $detalleid,
                            $nombreArchivo,
                            'foto',
                            $nombreArchivo
                        );
                    }
                }
            }
        }

        echo json_encode([
            "status" => true,
            "msg" => "Recepción registrada con evidencias"
        ]);
    }

    public function getOrdenesActivas()
    {
        echo json_encode($this->model->selectOrdenesActivas(), JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getOrdenesCerradas()
    {
        echo json_encode($this->model->selectOrdenesCerradas(), JSON_UNESCAPED_UNICODE);
        die();
    }
    public function getOrdenesAbiertas()
    {
        echo json_encode($this->model->selectOrdenesAbiertas(), JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getOrdenesParciales()
    {
        echo json_encode($this->model->selectOrdenesParciales(), JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getEvidenciasProducto()
    {
        $recepcionid = intval($_GET['recepcionid']);
        $inventarioid = intval($_GET['inventarioid']);

        echo json_encode(
            $this->model->getEvidenciasProducto(
                $recepcionid,
                $inventarioid
            ),
            JSON_UNESCAPED_UNICODE
        );
        die();
    }

    public function getDocumentosRecepcion()
    {
        $recepcionid = intval($_GET['recepcionid']);

        echo json_encode(
            $this->model->getDocumentosRecepcion(
                $recepcionid
            ),
            JSON_UNESCAPED_UNICODE
        );
        die();
    }
    public function getRecepcionCompra($idCompra)
    {
        echo json_encode(
            $this->model->getRecepcionByCompra($idCompra),
            JSON_UNESCAPED_UNICODE
        );
        die();
    }
}
