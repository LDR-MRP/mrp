<?php

class Lgs_ejecucion extends Controllers
{
    use ApiResponser;

    private Lgs_ejecucionService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();
        $this->service = new Lgs_ejecucionService();
    }

    /**
     * Renderiza la vista principal de la Mesa de Despacho y Salidas
     * URL: {{base_url}}/Lgs_ejecucion
     */
    public function Lgs_ejecucion(): void
    {
        $this->views->getView(
            $this,
            "../Lgs_ejecucion/index",
            [
                'page_tag' => "Despacho de Envíos",
                'page_title' => "Mesa de Ejecución y Salidas",
                'page_name' => "lgs_ejecucion",
                'page_functions_js' => "functions_lgs_ejecucion.js",
            ]
        );
    }

    /**
     * Devuelve el JSON para alimentar el DataTable de Envíos Aprobados/En Tránsito
     * URL: {{base_url}}/Lgs_ejecucion/getEnviosDespacho
     */
    public function getEnviosDespacho(): void
    {
        try {
            $data = $this->service->getEnviosDespacho();
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    /**
     * Obtiene el detalle de VINs y su orden de acomodo para el área de entregas
     * URL: {{base_url}}/Lgs_ejecucion/getDetalleDespacho/12
     */
    public function getDetalleDespacho(int $idEnvio): void
    {
        try {
            $data = $this->service->getDetalleDespacho($idEnvio);
            echo $this->successResponse($data, "Detalle de VINs obtenido");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Registra el despacho real del envío
     * URL: {{base_url}}/Lgs_ejecucion/registrarDespacho
     */
    public function registrarDespacho(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $fechaSalida = $_POST['fecha_salida_real'] ?? date('Y-m-d H:i:s');
            $evidenciasJson = $_POST['evidencias_json'] ?? null;

            if ($idEnvio === 0) {
                throw new Exception("El ID de envío es requerido.");
            }

            $this->service->registrarDespacho($idEnvio, $fechaSalida, $evidenciasJson, $userId);
            
            echo $this->successResponse(null, "Despacho de envío registrado correctamente. Solicitud enviada a entregas.");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Confirma la entrega física de un VIN individual en planta
     * URL: {{base_url}}/Lgs_ejecucion/confirmarVin
     */
    public function confirmarVin(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $idUnidad = intval($_POST['id_unidad'] ?? 0);

            if ($idEnvio === 0 || $idUnidad === 0) {
                throw new Exception("Parámetros incompletos para confirmar el VIN.");
            }

            $enTransito = $this->service->confirmarSalidaVin($idEnvio, $idUnidad, $userId);
            
            $msg = $enTransito 
                ? "¡Todos los VINs fueron entregados! El envío ahora se encuentra EN TRÁNSITO." 
                : "VIN confirmado e entregado al trasladista exitosamente.";

            echo $this->successResponse(['en_transito' => $enTransito], $msg);
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}
