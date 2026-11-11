<?php

require_once "Models/Lgs_preplaneacionesModel.php";

class Lgs_preplaneaciones extends Controllers
{
    use ApiResponser;

    /** @var Lgs_preplaneacionesModel */
    public $model;

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
        $this->model = new Lgs_preplaneacionesModel();
    }

    /**
     * Renderiza la vista principal del submódulo de Preplaneaciones
     * URL: {{base_url}}/Lgs_preplaneaciones
     */
    public function Lgs_preplaneaciones(): void
    {
        $plantas = $this->model->getPlantas();

        $this->views->getView(
            $this,
            "../Lgs_preplaneaciones/index",
            [
                'page_tag'          => "Preplaneación para Planta",
                'page_title'        => "Avisos de Preplaneación y Confirmación de Planta",
                'page_name'         => "lgs_preplaneaciones",
                'page_functions_js' => "functions_lgs_preplaneaciones.js",
                'plantas'           => $plantas,
            ]
        );
    }

    /**
     * Devuelve la lista en JSON para el DataTable
     * URL: {{base_url}}/Lgs_preplaneaciones/getPreplaneaciones
     */
    public function getPreplaneaciones(): void
    {
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = $this->model->getPreplaneaciones();
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    /**
     * Devuelve el detalle completo de una preplaneación (cabecera + VINs)
     * URL: {{base_url}}/Lgs_preplaneaciones/getDetalle/{id}
     */
    public function getDetalle($idPreplaneacion = 0): void
    {
        try {
            $id = intval($idPreplaneacion);
            if ($id <= 0) {
                echo $this->errorResponse("ID de preplaneación inválido", 400);
                return;
            }

            $cabecera = $this->model->getPreplaneacion($id);
            if (empty($cabecera)) {
                echo $this->errorResponse("Preplaneación no encontrada", 404);
                return;
            }

            $vins = $this->model->getVinsPreplaneacion($id);

            echo $this->successResponse([
                'preplaneacion' => $cabecera,
                'vins' => $vins
            ], "Detalle obtenido");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve unidades disponibles para seleccionar en el formulario de creación
     * URL: {{base_url}}/Lgs_preplaneaciones/getVinsDisponibles
     */
    public function getVinsDisponibles(): void
    {
        try {
            $idPlanta = intval($_GET['id_planta'] ?? 0);
            $vins = $this->model->getVinsDisponibles($idPlanta);
            echo $this->successResponse($vins, "Unidades obtenidas");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Guarda una nueva preplaneación
     * URL: {{base_url}}/Lgs_preplaneaciones/guardar
     */
    public function guardar(): void
    {
        try {
            $idPlanta = intval($_POST['id_planta'] ?? 0);
            $fechaTentativa = trim($_POST['fecha_tentativa_recoleccion'] ?? '');
            $vinsJson = $_POST['vins_json'] ?? '[]';
            $vins = json_decode($vinsJson, true);
            $userId = intval($_SESSION['idUser'] ?? 1);

            if ($idPlanta <= 0) {
                echo $this->errorResponse("Debe seleccionar una Planta de origen.", 400);
                return;
            }

            if (empty($fechaTentativa)) {
                echo $this->errorResponse("Debe indicar la Fecha Tentativa de Recolección.", 400);
                return;
            }

            if (!is_array($vins) || count($vins) === 0) {
                echo $this->errorResponse("Debe agregar al menos una unidad / VIN a la preplaneación.", 400);
                return;
            }

            $res = $this->model->insertPreplaneacion([
                'id_planta' => $idPlanta,
                'fecha_tentativa_recoleccion' => str_replace('T', ' ', $fechaTentativa),
                'created_by' => $userId
            ], $vins);

            echo $this->successResponse($res, "Preplaneación {$res['folio']} registrada exitosamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Registra la confirmación de Planta capturada por Logística
     * URL: {{base_url}}/Lgs_preplaneaciones/registrarConfirmacion
     */
    public function registrarConfirmacion(): void
    {
        try {
            $idPreplaneacion = intval($_POST['id_preplaneacion'] ?? 0);
            $contacto = trim($_POST['contacto_planta'] ?? '');
            $notas = trim($_POST['notas_comunicacion'] ?? '');
            $unidadesJson = $_POST['unidades_json'] ?? '[]';
            $unidades = json_decode($unidadesJson, true);
            $userId = intval($_SESSION['idUser'] ?? 1);

            if ($idPreplaneacion <= 0) {
                echo $this->errorResponse("ID de preplaneación inválido.", 400);
                return;
            }

            if (empty($contacto)) {
                echo $this->errorResponse("Debe ingresar el nombre del contacto en Planta con quien validó la disponibilidad.", 400);
                return;
            }

            if (!is_array($unidades) || count($unidades) === 0) {
                echo $this->errorResponse("No se recibieron datos de disponibilidad de las unidades.", 400);
                return;
            }

            $this->model->registrarConfirmacionPlanta($idPreplaneacion, $contacto, $notas, $unidades, $userId);

            echo $this->successResponse(null, "Confirmación de disponibilidad guardada exitosamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Endpoint API para que Planta/WMS pueda responder directamente si lo desea
     * URL: {{base_url}}/Lgs_preplaneaciones/confirmarDisponibilidad (POST JSON)
     */
    public function confirmarDisponibilidad(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

            $idPreplaneacion = intval($input['id_preplaneacion'] ?? 0);
            $contacto = trim($input['contacto_planta'] ?? ($input['comentarios_planta'] ?? 'Confirmación API WMS'));
            $notas = trim($input['notas_comunicacion'] ?? ($input['comentarios_planta'] ?? ''));
            $unidades = $input['unidades'] ?? [];
            $userId = intval($input['usuario_id'] ?? ($_SESSION['idUser'] ?? 1));

            if ($idPreplaneacion <= 0 || empty($unidades)) {
                echo $this->errorResponse("Parámetros incompletos en la solicitud.", 400);
                return;
            }

            $this->model->registrarConfirmacionPlanta($idPreplaneacion, $contacto, $notas, $unidades, $userId);

            echo $this->successResponse(null, "Disponibilidad confirmada correctamente. Logística ha sido notificada.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Convierte los VINs confirmados de una preplaneación en una Planeación Definitiva
     * URL: {{base_url}}/Lgs_preplaneaciones/convertirAPlaneacion
     */
    public function convertirAPlaneacion(): void
    {
        try {
            $idPreplaneacion = intval($_POST['id_preplaneacion'] ?? 0);
            $userId = intval($_SESSION['idUser'] ?? 1);

            if ($idPreplaneacion <= 0) {
                echo $this->errorResponse("ID de preplaneación inválido.", 400);
                return;
            }

            $res = $this->model->convertirAPlaneacionDefinitiva($idPreplaneacion, $userId);

            echo $this->successResponse($res, "Planeación Definitiva {$res['folio_planeacion']} generada exitosamente con {$res['total_vins_convertidos']} unidad(es).");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}

