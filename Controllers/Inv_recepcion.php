<?php
class Inv_recepcion extends Controllers
{
    /** Extensiones permitidas para fotos por producto y documentos generales */
    private const EXT_FOTOS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic'];
    private const EXT_DOCS  = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'xml', 'xls', 'xlsx', 'doc', 'docx', 'csv', 'txt'];

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
        $data['page_title'] = "Recepción de materiales";
        $data['page_name'] = "Recepción";
        $data['page_functions_js'] = "functions_inv_recepcion.js";

        $this->views->getView($this, "inv_recepcion", $data);
    }

    /* =========================================================
       CONSULTAS
    ========================================================= */
    public function getOrdenesRecepcion()
    {
        $this->json($this->model->selectOrdenesRecepcion());
    }

    public function getOrdenesCompraPendientes() { $this->json($this->model->selectOrdenesCompraPendientes()); }
    public function getOrdenesActivas()          { $this->json($this->model->selectOrdenesActivas()); }
    public function getOrdenesAbiertas()         { $this->json($this->model->selectOrdenesAbiertas()); }
    public function getOrdenesParciales()        { $this->json($this->model->selectOrdenesParciales()); }
    public function getOrdenesCerradas()         { $this->json($this->model->selectOrdenesCerradas()); }

    public function getDetalleOC($idCompra)
    {
        $this->json($this->model->selectDetalleOC(intval($idCompra)));
    }

    public function getHeaderOC($idCompra)
    {
        $this->json($this->model->selectHeaderOC(intval($idCompra)));
    }

    public function getHistorialRecepcion($idCompra)
    {
        $this->json($this->model->selectHistorialRecepcion(intval($idCompra)));
    }

    public function getRecepcionCompra($idCompra)
    {
        $this->json($this->model->getRecepcionByCompra(intval($idCompra)));
    }

    public function getEvidenciasProducto()
    {
        $this->json($this->model->getEvidenciasProducto(
            intval($_GET['recepcionid'] ?? 0),
            intval($_GET['inventarioid'] ?? 0)
        ));
    }

    public function getDocumentosRecepcion()
    {
        $this->json($this->model->getDocumentosRecepcion(intval($_GET['recepcionid'] ?? 0)));
    }

    /* =========================================================
       REGISTRAR RECEPCIÓN (parcial o total)
       - Acepta OCs en_transito / recibida_parcial (y 'cerrada' legado)
       - Al terminar actualiza la OC: recibida_parcial o cerrada
    ========================================================= */
    public function setRecepcion()
    {
        $idCompra  = intval($_POST['compraid'] ?? 0);
        $usuarioId = intval($_SESSION['idUser'] ?? 0);
        $folioOC   = 'OC-' . $idCompra;

        if ($idCompra <= 0) {
            $this->responder(false, "Selecciona una orden de compra válida.");
        }

        $oc = $this->model->getOrdenCompra($idCompra);
        if (empty($oc)) {
            $this->responder(false, "La orden de compra {$folioOC} no existe.");
        }

        if (!in_array($oc['estatus'], Inv_recepcionModel::OC_RECIBIBLES, true)) {
            $msg = $oc['estatus'] === 'emitida'
                ? "La {$folioOC} aún no está en tránsito. Compras debe marcarla como \"En tránsito\" para poder recibirla."
                : "La {$folioOC} está en estatus \"{$oc['estatus']}\" y no puede recibirse.";
            $this->responder(false, $msg);
        }

        $recepcion = $this->model->getRecepcionByCompra($idCompra);
        if (!empty($recepcion) && $recepcion['estatus'] === 'cerrada') {
            $this->responder(false, "La recepción de la {$folioOC} ya está cerrada y no puede modificarse.");
        }

        $almacenid = intval($oc['almacenid'] ?? 0);
        if ($almacenid <= 0) {
            $this->responder(false, "La {$folioOC} no tiene almacén destino asignado.");
        }

        $detalle = json_decode($_POST['detalle'] ?? '[]', true);
        if (!is_array($detalle)) {
            $this->responder(false, "El detalle de la recepción no es válido.");
        }
        $observaciones = trim($_POST['observaciones'] ?? '');

        // ---------- Validación en servidor contra lo pendiente ----------
        $pendientes = [];
        $solicitado = [];
        foreach ($this->model->selectDetalleOC($idCompra) as $row) {
            $pendientes[intval($row['inventarioid'])] = floatval($row['cantidad_pendiente']);
            $solicitado[intval($row['inventarioid'])] = floatval($row['cantidad_solicitada']);
        }

        $partidas = [];
        $totalPiezas = 0;
        foreach ($detalle as $item) {
            $cantidad = round(floatval($item['cantidad_recibida'] ?? 0), 4);
            if ($cantidad <= 0) continue;

            $inv    = intval($item['inventarioid'] ?? 0);
            $codigo = trim($item['codigo'] ?? '');

            if (!isset($pendientes[$inv])) {
                $this->responder(false, "El producto {$codigo} no pertenece a la {$folioOC}.");
            }
            if ($cantidad > $pendientes[$inv] + 0.0001) {
                $this->responder(false, "La cantidad de {$codigo} ({$cantidad}) excede lo pendiente por recibir ({$pendientes[$inv]}).");
            }

            $partidas[] = [
                'inventarioid'        => $inv,
                'codigo'              => $codigo,
                'lote'                => trim($item['lote'] ?? ''),
                'cantidad_solicitada' => $solicitado[$inv],
                'cantidad_recibida'   => $cantidad,
                'observaciones'       => trim($item['observaciones'] ?? ''),
            ];
            $totalPiezas += $cantidad;
        }

        if (empty($partidas)) {
            $this->responder(false, "Debes capturar al menos una cantidad recibida.");
        }

        // ---------- Transacción: recepción + inventario + estatus OC ----------
        $pdo = $this->model->getConexion();
        $completa = false;
        $recepcionid = 0;

        try {
            $pdo->beginTransaction();

            if (empty($recepcion)) {
                $recepcionid = $this->model->insertRecepcion($idCompra, $observaciones, $usuarioId);
            } else {
                $recepcionid = $recepcion['idrecepcion'];
                $this->model->updateRecepcionHeader($recepcionid, $observaciones);
            }

            $numeroMovimiento = 'RC-' . str_pad($recepcionid, 6, '0', STR_PAD_LEFT);

            foreach ($partidas as $item) {
                $this->model->insertDetalleRecepcion(
                    $recepcionid,
                    $item['inventarioid'],
                    $item['codigo'],
                    $item['lote'],
                    $item['cantidad_solicitada'],
                    $item['cantidad_recibida'],
                    $item['observaciones']
                );

                $detalleCompra = $this->model->getDetalleCompraProducto($idCompra, $item['inventarioid']);
                $costoUnitario = floatval($detalleCompra['costo_unitario'] ?? 0);

                $multi = $this->model->getMultiAlmacen($item['inventarioid'], $almacenid);
                if ($multi) {
                    $this->model->updateExistenciaMultiAlmacen($multi['idmultialmacen'], $item['cantidad_recibida']);
                } else {
                    $this->model->insertMultiAlmacen($item['inventarioid'], $almacenid, $item['cantidad_recibida']);
                }

                $existencia = $this->model->getExistenciaActual($item['inventarioid'], $almacenid);

                $this->model->insertMovimientoInventario(
                    $item['inventarioid'],
                    $almacenid,
                    $numeroMovimiento,
                    $folioOC,
                    $item['cantidad_recibida'],
                    $costoUnitario,
                    floatval($existencia['existencia'] ?? 0)
                );
            }

            $completa = $this->model->recepcionCompleta($idCompra);

            $this->model->updateRecepcionStatus($recepcionid, $completa ? 'cerrada' : 'parcial');
            $this->model->updateOrdenCompraEstatus($idCompra, $completa ? 'cerrada' : 'recibida_parcial', $usuarioId);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Inv_recepcion::setRecepcion {$folioOC}: " . $e->getMessage());
            $this->responder(false, "No se pudo registrar la recepción. No se afectó el inventario. Detalle: " . $e->getMessage());
        }

        // ---------- Archivos (después del commit) ----------
        $this->guardarDocumentos($recepcionid);
        $this->guardarEvidencias($recepcionid);

        // ---------- Bitácora de la OC ----------
        $estatusOC = $completa ? 'cerrada' : 'recibida_parcial';
        try {
            $ocModel = new Com_ordenCompraModel();
            $ocModel->logAudit(
                $idCompra,
                AuditAction::RECEIVED,
                "Recepción en almacén " . 'RC-' . str_pad($recepcionid, 6, '0', STR_PAD_LEFT)
                    . ". Piezas recibidas: {$totalPiezas}. Estatus OC: {$estatusOC}",
                $usuarioId
            );
        } catch (Throwable $e) {
            error_log("Inv_recepcion auditoría {$folioOC}: " . $e->getMessage());
        }

        $this->json([
            "status"            => true,
            "msg"               => $completa
                ? "Recepción completa. La {$folioOC} quedó CERRADA."
                : "Recepción parcial registrada. La {$folioOC} queda como RECIBIDA PARCIAL.",
            "idcompra"          => $idCompra,
            "recepcionid"       => $recepcionid,
            "estatus_recepcion" => $completa ? 'cerrada' : 'parcial',
            "estatus_oc"        => $estatusOC,
        ]);
    }

    /* =========================================================
       HELPERS
    ========================================================= */
    private function guardarDocumentos($recepcionid)
    {
        if (empty($_FILES['documentos']['name'][0])) return;

        $rutaBase = __DIR__ . "/../Assets/uploads/recepciones/documentos/";
        if (!file_exists($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }

        foreach ($_FILES['documentos']['tmp_name'] as $key => $tmp) {
            if (empty($tmp)) continue;

            $nombreOriginal = basename($_FILES['documentos']['name'][$key]);
            $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            if (!in_array($extension, self::EXT_DOCS, true)) continue;

            $nombreFinal = uniqid('doc_') . "_" . time() . "." . $extension;

            if (move_uploaded_file($tmp, $rutaBase . $nombreFinal)) {
                $this->model->insertDocumentoRecepcion($recepcionid, $nombreOriginal, $nombreFinal);
            }
        }
    }

    private function guardarEvidencias($recepcionid)
    {
        if (empty($_FILES['evidencias']['tmp_name']) || !is_array($_FILES['evidencias']['tmp_name'])) return;

        $rutaBase = __DIR__ . "/../Assets/uploads/recepciones/evidencias/";
        if (!file_exists($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }

        foreach ($_FILES['evidencias']['tmp_name'] as $detalleid => $files) {
            $inventarioid = intval($_POST['evidencias_meta'][$detalleid]['inventarioid'] ?? 0);
            if ($inventarioid <= 0) continue;

            foreach (array_slice((array)$files, 0, 5) as $key => $tmp) {
                if (empty($tmp)) continue;

                $nombreOriginal = $_FILES['evidencias']['name'][$detalleid][$key];
                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                if (!in_array($extension, self::EXT_FOTOS, true)) continue;

                $nombreArchivo = uniqid('evi_') . "_" . time() . "." . $extension;

                if (move_uploaded_file($tmp, $rutaBase . $nombreArchivo)) {
                    $this->model->insertEvidenciaRecepcion(
                        $recepcionid,
                        $inventarioid,
                        intval($detalleid),
                        $nombreArchivo,
                        'foto',
                        $nombreArchivo
                    );
                }
            }
        }
    }

    private function responder(bool $status, string $msg)
    {
        $this->json(["status" => $status, "msg" => $msg]);
    }

    private function json($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
}
