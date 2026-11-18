<?php

class Lgs_aprobaciones extends Controllers
{
    use ApiResponser;

    private Lgs_aprobacionesService $service;

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
        $this->service = new Lgs_aprobacionesService();
    }

    /**
     * Renderiza la vista principal del Panel de Aprobaciones
     * URL: {{base_url}}/Lgs_aprobaciones
     */
    public function Lgs_aprobaciones(): void
    {
        require_once("Services/Lgs_gastosadicionalesService.php");
        $gastosService = new Lgs_gastosadicionalesService();
        $catalogosGastos = $gastosService->getCatalogos();

        $userId = intval($_SESSION['idUser'] ?? ($_SESSION['userData']['idusuario'] ?? 1));
        $userRole = intval($_SESSION['userData']['idrol'] ?? ($_SESSION['rolid'] ?? 0));
        $esAprobador = $this->service->tienePermisoAprobacion($userId);

        $this->views->getView(
            $this,
            "../Lgs_aprobaciones/index",
            [
                'page_tag'          => "Aprobaciones Logística",
                'page_title'        => "Panel de Aprobación",
                'page_name'         => "lgs_aprobaciones",
                'page_functions_js' => [
                    "modulos/functions_lgs_buscador_envios.js",
                    "modulos/functions_lgs_buscador_incidencias.js",
                    "modulos/functions_lgs_gastosadicionales.js",
                    "modulos/functions_lgs_aprobaciones.js"
                ],
                'catalogos'         => $catalogosGastos,
                'es_aprobador'      => $esAprobador,
                'id_user'           => $userId,
                'id_rol'            => $userRole,
            ]
        );
    }

    /**
     * Obtiene el listado de planeaciones pendientes y revisadas
     * URL: {{base_url}}/Lgs_aprobaciones/getPlaneacionesAprobacion
     */
    public function getPlaneacionesAprobacion(): void
    {
        try {
            $data = $this->service->getAllParaAprobacion();
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    /**
     * Obtiene las rutas/envíos dentro de un plan para evaluarlas
     * URL: {{base_url}}/Lgs_aprobaciones/getDetallePlan/1
     */
    public function getDetallePlan($idPlaneacion = 0): void
    {
        try {
            $idPlaneacion = intval($idPlaneacion);
            if ($idPlaneacion <= 0) {
                throw new Exception("ID de planeación no válido.");
            }
            $data = $this->service->getDetallePlan($idPlaneacion);
            echo $this->successResponse($data, "Detalle del plan obtenido");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Ejecuta la acción de aprobar o rechazar
     * URL: {{base_url}}/Lgs_aprobaciones/resolver
     */
    public function resolver(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            
            $idPlaneacion = intval($_POST['id_planeacion'] ?? 0);
            $decision     = $_POST['decision'] ?? ''; // 'aprobar' o 'rechazar'
            $observaciones= $_POST['obs_aprobador'] ?? '';
            
            if ($idPlaneacion === 0 || empty($decision)) {
                throw new Exception("Datos incompletos para resolver la planeación.");
            }

            $this->service->resolverPlaneacion($idPlaneacion, $decision, $observaciones, $userId);
            
            if ($decision === 'aprobar_reapertura') {
                $msg = "Reapertura de planeación APROBADA con éxito.";
            } elseif ($decision === 'denegar_reapertura') {
                $msg = "Solicitud de reapertura DENEGADA.";
            } elseif ($decision === 'aprobar') {
                $msg = "Planeación APROBADA con éxito.";
            } else {
                $msg = "Planeación RECHAZADA.";
            }
            echo json_encode([
                'status' => true,
                'msg'    => $msg
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            echo json_encode([
                'status' => false,
                'msg'    => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * GET: Listado de solicitudes excepcionales (PE- -> EN- sin factura/pago/aut. movimiento)
     * URL: {{base_url}}/Lgs_aprobaciones/getSolicitudesPreEnvio
     */
    public function getSolicitudesPreEnvio(): void
    {
        if (ob_get_length()) {
            ob_clean();
        }
        try {
            $data = $this->service->getSolicitudesPreEnvio();
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    /**
     * GET: Detalle completo de una solicitud excepcional
     * URL: {{base_url}}/Lgs_aprobaciones/getDetalleSolicitudPreEnvio/1
     */
    public function getDetalleSolicitudPreEnvio($idSolicitud = 0): void
    {
        if (ob_get_length()) {
            ob_clean();
        }
        try {
            $idSolicitud = intval($idSolicitud);
            if ($idSolicitud <= 0) {
                throw new Exception("ID de solicitud no válido.");
            }
            $data = $this->service->getDetalleSolicitudPreEnvio($idSolicitud);
            echo $this->successResponse($data, "Detalle de solicitud obtenido");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * POST: Aprueba o rechaza una solicitud excepcional (PE- -> EN-)
     * URL: {{base_url}}/Lgs_aprobaciones/resolverSolicitudPreEnvio
     */
    public function resolverSolicitudPreEnvio(): void
    {
        if (ob_get_length()) {
            ob_clean();
        }
        try {
            $userId = intval($_SESSION['idUser'] ?? 1);
            $raw = file_get_contents('php://input');
            $input = $raw ? json_decode($raw, true) : null;
            if (!is_array($input)) {
                $input = $_POST;
            }
            $idSolicitud   = intval($input['id_solicitud'] ?? 0);
            $decision      = trim((string)($input['decision'] ?? ''));
            $observaciones = trim((string)($input['obs_aprobador'] ?? ''));

            if ($idSolicitud <= 0 || $decision === '') {
                throw new Exception("Datos incompletos para dictaminar la solicitud.");
            }

            $res = $this->service->resolverSolicitudPreEnvio($idSolicitud, $decision, $observaciones, $userId);
            $msg = ($res['estatus'] === 'APROBADA')
                ? "Solicitud {$res['folio_solicitud']} APROBADA. Envío oficial generado: {$res['folio_envio_generado']}."
                : "Solicitud {$res['folio_solicitud']} RECHAZADA.";

            echo $this->successResponse($res, $msg);
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 400);
        }
    }
}
