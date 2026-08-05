<?php

class Lgs_aprobaciones extends Controllers
{
    use ApiResponser;

    private Lgs_aprobacionesService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();
        $this->service = new Lgs_aprobacionesService();
    }

    /**
     * Renderiza la vista principal del Panel de Aprobaciones
     * URL: {{base_url}}/Lgs_aprobaciones
     */
    public function Lgs_aprobaciones(): void
    {
        $this->views->getView(
            $this,
            "../Lgs_aprobaciones/index",
            [
                'page_tag' => "Aprobaciones Logística",
                'page_title' => "Panel de Aprobación",
                'page_name' => "lgs_aprobaciones",
                'page_functions_js' => "functions_lgs_aprobaciones.js",
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
            echo $this->successResponse($data, "Listado de aprobaciones obtenido");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Obtiene las rutas/envíos dentro de un plan para evaluarlas
     * URL: {{base_url}}/Lgs_aprobaciones/getDetallePlan/EX-000001
     */
    public function getDetallePlan(int $idPlaneacion): void
    {
        try {
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
            
            $msg = ($decision === 'aprobar') ? "Planeación APROBADA con éxito." : "Planeación RECHAZADA.";
            echo $this->successResponse(null, $msg);

        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}
