<?php


class Inv_operaciones_traslados extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        getPermisos(MIOPTRASLADOS);
    }

    public function Inv_operaciones_traslados($folio = null)
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }

        $data['page_tag'] = "Traslados operaciones";
        $data['page_title'] = "Traslados operaciones";
        $data['page_name'] = "Traslados operaciones";
        $data['page_functions_js'] = "functions_inv_operaciones_traslados.js";
        $data['folio_inicial'] = $folio ? strClean($folio) : '';

        $this->views->getView($this, "inv_operaciones_traslados", $data);
    }

    // Nuevo método exclusivo para el QR
    public function escanear($folio = null)
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }

        $data['page_tag'] = "Traslados operaciones";
        $data['page_title'] = "Traslados operaciones";
        $data['page_name'] = "Traslados operaciones";
        $data['page_functions_js'] = "functions_inv_operaciones_traslados.js";
        $data['folio_inicial'] = $folio ? strClean(urldecode($folio)) : '';

        $this->views->getView($this, "inv_operaciones_traslados", $data);
    }

    public function getTrasladoOperacion($folio)
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->getTrasladoOperacion(
                strClean($folio)
            );

            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE
            );
        }

        die();
    }

    public function registrarSalida()
    {

        try {

            if ($_SESSION['permisosMod']['r']) {


                $data = json_decode(
                    file_get_contents("php://input"),
                    true
                );


                $folio = $data['folio'];


                try {

                    $arrData = $this->model->registrarSalida(
                        $folio,
                        $_SESSION['idUser']
                    );
                } catch (Throwable $e) {

                    echo json_encode([
                        "status" => false,
                        "error" => $e->getMessage(),
                        "linea" => $e->getLine(),
                        "archivo" => $e->getFile()
                    ]);

                    die();
                }


                echo json_encode($arrData);
            }
        } catch (Throwable $e) {

            echo json_encode([
                "status" => false,
                "error_general" => $e->getMessage(),
                "linea" => $e->getLine()
            ]);
        }


        die();
    }

    /* =====================================================
       REGISTRO DE INGRESO (Seguridad / caseta)
       Confirma que la unidad llegó físicamente, sin tocar
       inventario ni llaves. La recepción interna (con llave)
       se hace después, por separado, en registrarRecepcion().
    ===================================================== */
    public function registrarIngreso()
    {
        try {

            if ($_SESSION['permisosMod']['r']) {

                $data = json_decode(
                    file_get_contents("php://input"),
                    true
                );

                $folio = $data['folio'];

                try {

                    $arrData = $this->model->registrarIngresoUnidad(
                        $folio,
                        $_SESSION['idUser']
                    );
                } catch (Throwable $e) {

                    echo json_encode([
                        "status" => false,
                        "error" => $e->getMessage(),
                        "linea" => $e->getLine(),
                        "archivo" => $e->getFile()
                    ]);

                    die();
                }

                echo json_encode($arrData);
            }
        } catch (Throwable $e) {

            echo json_encode([
                "status" => false,
                "error_general" => $e->getMessage(),
                "linea" => $e->getLine()
            ]);
        }

        die();
    }

    public function registrarRecepcion()
{
    try {
        if ($_SESSION['permisosMod']['r']) {

            $data = json_decode(file_get_contents("php://input"), true);

            $folio = $data['folio'];
            $unidades = $data['unidades'] ?? [];

            $arrData = $this->model->registrarRecepcion(
                $folio,
                $_SESSION['idUser'],
                $unidades
            );

            echo json_encode($arrData);
        }
    } catch (Throwable $e) {
        echo json_encode(["status" => false, "error" => $e->getMessage(), "linea" => $e->getLine()]);
    }

    die();
}

    public function registrarUnidadAnomala()
    {
        try {
            if ($_SESSION['permisosMod']['r']) {

                $data = json_decode(file_get_contents("php://input"), true);

                $folio = strClean($data['folio'] ?? '');
                $vin   = strClean($data['vin'] ?? '');

                if ($folio === '' || $vin === '') {
                    echo json_encode([
                        "status" => false,
                        "msg" => "Datos incompletos"
                    ]);
                    die();
                }

                $arrData = $this->model->registrarUnidadAnomala(
                    $folio,
                    $vin,
                    $_SESSION['idUser']
                );

                echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            }
        } catch (Throwable $e) {
            echo json_encode([
                "status" => false,
                "msg" => $e->getMessage()
            ]);
        }

        die();
    }

    /* =====================================================
       EVIDENCIAS (fotos) POR UNIDAD — 19-nov-2026
       etapa = 'salida'    -> solo con el traslado en estado 1
       etapa = 'recepcion' -> solo con el traslado en estado 2 o 3
    ===================================================== */

    private const EVID_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    private const EVID_MAX_FOTOS = 10;           // por envío
    private const EVID_MAX_BYTES = 10485760;     // 10 MB por foto

    private function etapaPermitida(string $etapa, int $estadoTraslado): bool
    {
        if ($etapa === 'salida') {
            return $estadoTraslado === 1;
        }
        if ($etapa === 'recepcion') {
            return in_array($estadoTraslado, [2, 3], true);
        }
        return false;
    }

    private function urlEvidencias(array $lista): array
    {
        foreach ($lista as &$e) {
            $e['url'] = base_url() . '/Assets/uploads/traslados_evidencias/' . $e['archivo'];
        }
        return $lista;
    }

    private function jsonEvid(array $data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function subirEvidencia()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            $this->jsonEvid(['status' => false, 'msg' => 'Sin permisos']);
        }

        $folio = strClean($_POST['folio'] ?? '');
        $vin = strtoupper(strClean($_POST['vin'] ?? ''));
        $etapa = strClean($_POST['etapa'] ?? '');
        $observaciones = mb_substr(strClean($_POST['observaciones'] ?? ''), 0, 255);

        if ($folio === '' || $vin === '' || !in_array($etapa, ['salida', 'recepcion'], true)) {
            $this->jsonEvid(['status' => false, 'msg' => 'Datos incompletos']);
        }

        $info = $this->model->getUnidadDeTraslado($folio, $vin);
        if (empty($info)) {
            $this->jsonEvid(['status' => false, 'msg' => 'Traslado no encontrado']);
        }
        if (empty($info['detalle'])) {
            $this->jsonEvid(['status' => false, 'msg' => "El VIN {$vin} no pertenece a este traslado"]);
        }
        if (!$this->etapaPermitida($etapa, (int)$info['traslado']['estado'])) {
            $this->jsonEvid(['status' => false, 'msg' => 'El traslado ya no está en la etapa de ' . ($etapa === 'salida' ? 'salida' : 'recepción')]);
        }

        $archivos = $_FILES['fotos'] ?? null;
        if (empty($archivos) || empty($archivos['tmp_name']) || !is_array($archivos['tmp_name'])) {
            $this->jsonEvid(['status' => false, 'msg' => 'Selecciona al menos una foto']);
        }

        // Carpeta por folio (solo caracteres seguros en el nombre)
        $carpeta = preg_replace('/[^A-Za-z0-9_-]/', '_', $info['traslado']['folio']);
        $rutaBase = __DIR__ . '/../Assets/uploads/traslados_evidencias/' . $carpeta . '/';
        if (!is_dir($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }

        $guardadas = 0;
        $rechazadas = [];
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

        foreach (array_slice(array_keys($archivos['tmp_name']), 0, self::EVID_MAX_FOTOS) as $i) {
            $tmp = $archivos['tmp_name'][$i];
            $nombre = (string)$archivos['name'][$i];

            if (($archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
                $rechazadas[] = $nombre . ' (no se pudo subir)';
                continue;
            }
            if ((int)$archivos['size'][$i] > self::EVID_MAX_BYTES) {
                $rechazadas[] = $nombre . ' (pesa más de 10 MB)';
                continue;
            }

            $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            $mime = $finfo ? finfo_file($finfo, $tmp) : '';
            if (!in_array($ext, self::EVID_EXT, true) || ($finfo && strpos((string)$mime, 'image/') !== 0)) {
                $rechazadas[] = $nombre . ' (no es una imagen JPG/PNG/WEBP)';
                continue;
            }

            $archivo = $etapa . '_' . preg_replace('/[^A-Za-z0-9]/', '', $vin) . '_' . uniqid() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);

            if (move_uploaded_file($tmp, $rutaBase . $archivo)) {
                $this->model->insertEvidencia(
                    $info['traslado']['idtraslado'],
                    $info['detalle']['iddetalle'],
                    $info['detalle']['vin'],
                    $etapa,
                    $carpeta . '/' . $archivo,
                    $observaciones,
                    $_SESSION['idUser']
                );
                $guardadas++;
            } else {
                $rechazadas[] = $nombre . ' (no se pudo guardar)';
            }
        }

        if ($finfo) {
            finfo_close($finfo);
        }

        $lista = $this->urlEvidencias($this->model->selectEvidenciasUnidad($info['detalle']['iddetalle'], $etapa));

        $msg = $guardadas . ' foto(s) guardada(s)';
        if (!empty($rechazadas)) {
            $msg .= '. No se guardaron: ' . implode(', ', $rechazadas);
        }

        $this->jsonEvid([
            'status' => $guardadas > 0,
            'msg' => $msg,
            'evidencias' => $lista,
        ]);
    }

    public function getEvidencias()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            $this->jsonEvid(['status' => false, 'msg' => 'Sin permisos']);
        }

        $folio = strClean($_GET['folio'] ?? '');
        $vin = strtoupper(strClean($_GET['vin'] ?? ''));
        $etapa = strClean($_GET['etapa'] ?? '');

        $info = $this->model->getUnidadDeTraslado($folio, $vin);
        if (empty($info) || empty($info['detalle']) || !in_array($etapa, ['salida', 'recepcion'], true)) {
            $this->jsonEvid(['status' => false, 'msg' => 'Unidad no encontrada en el traslado']);
        }

        $this->jsonEvid([
            'status' => true,
            'evidencias' => $this->urlEvidencias($this->model->selectEvidenciasUnidad($info['detalle']['iddetalle'], $etapa)),
        ]);
    }

    public function eliminarEvidencia()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            $this->jsonEvid(['status' => false, 'msg' => 'Sin permisos']);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $idevidencia = intval($data['idevidencia'] ?? 0);

        $evid = $this->model->selectEvidencia($idevidencia);
        if (empty($evid) || (int)$evid['estado_evidencia'] !== 1) {
            $this->jsonEvid(['status' => false, 'msg' => 'La evidencia no existe']);
        }

        // Solo se puede quitar mientras el traslado sigue en esa etapa:
        // una vez registrada la salida/recepción, las fotos quedan fijas.
        if (!$this->etapaPermitida($evid['etapa'], (int)$evid['estado_traslado'])) {
            $this->jsonEvid(['status' => false, 'msg' => 'Ya no se puede eliminar: la ' . ($evid['etapa'] === 'salida' ? 'salida' : 'recepción') . ' ya fue registrada']);
        }

        $this->model->desactivarEvidencia($idevidencia);

        $this->jsonEvid([
            'status' => true,
            'msg' => 'Evidencia eliminada',
            'evidencias' => $this->urlEvidencias($this->model->selectEvidenciasUnidad($evid['iddetalle'], $evid['etapa'])),
        ]);
    }
}
