<?php

class Lgs_planeaciones extends Controllers
{
    use ApiResponser;

    private Lgs_planeacionesService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();
        $this->service = new Lgs_planeacionesService();
    }

    /**
     * Renderiza la vista principal de la Bandeja de Planeaciones
     * URL: {{base_url}}/Lgs_planeaciones
     */
    public function Lgs_planeaciones(): void
    {
        $this->views->getView(
            $this,
            "../Lgs_planeaciones/index",
            [
                'page_tag' => "Planeación de Logística",
                'page_title' => "Mis Planeaciones",
                'page_name' => "lgs_planeaciones",
                'page_functions_js' => "functions_lgs_planeaciones.js",
            ]
        );
    }

    /**
     * Devuelve el JSON para alimentar el DataTable de planeaciones
     * URL: {{base_url}}/Lgs_planeaciones/getPlaneaciones
     */
    public function getPlaneaciones(): void
    {
        try {
            $data = $this->service->getAllPlaneaciones();
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    /**
     * Devuelve los envíos que están listos para ser agregados a un plan
     * URL: {{base_url}}/Lgs_planeaciones/getEnviosDisponibles
     */
    public function getEnviosDisponibles(): void
    {
        try {
            $data = $this->service->getEnviosDisponibles();
            echo $this->successResponse($data, "Listado de envíos disponibles obtenido");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Agrupa envíos y crea la Planeación
     * URL: {{base_url}}/Lgs_planeaciones/store
     */
    public function store(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            
            $descripcion = $_POST['descripcion'] ?? '';
            // envios_ids debería venir como un array desde el frontend
            $enviosIdsStr = $_POST['envios_ids'] ?? ''; 
            $enviosIds = !empty($enviosIdsStr) ? explode(',', $enviosIdsStr) : [];
            
            // Limpiar y castear a enteros
            $enviosIds = array_map('intval', array_filter($enviosIds));

            $data = [
                'descripcion' => $descripcion,
                'obs_operador' => $_POST['obs_operador'] ?? ''
            ];

            $idPlan = $this->service->createPlaneacion($data, $enviosIds, $userId);
            
            echo $this->successResponse(['id_planeacion' => $idPlan], "Planeación enviada a aprobación exitosamente.");

        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}
