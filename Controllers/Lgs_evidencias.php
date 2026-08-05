<?php

class Lgs_evidencias extends Controllers
{
    use ApiResponser;

    private Lgs_evidenciasService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();
        $this->service = new Lgs_evidenciasService();
    }

    /**
     * Renderiza la vista principal del Módulo de Evidencias y Cierre
     * URL: {{base_url}}/Lgs_evidencias
     */
    public function Lgs_evidencias(): void
    {
        $this->views->getView(
            $this,
            "../Lgs_evidencias/index",
            [
                'page_tag' => "Evidencias Multimedia",
                'page_title' => "Evidencias y Cierre de Entrega",
                'page_name' => "lgs_evidencias",
                'page_functions_js' => "functions_lgs_evidencias.js",
            ]
        );
    }

    /**
     * Devuelve el JSON para el DataTable de Evidencias
     * URL: {{base_url}}/Lgs_evidencias/getEnviosEvidencias
     */
    public function getEnviosEvidencias(): void
    {
        try {
            $data = $this->service->getEnviosEvidencias();
            echo $this->successResponse($data, "Listado de envíos obtenido");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve la lista de evidencias asociadas a un envío
     * URL: {{base_url}}/Lgs_evidencias/getEvidenciasEnvio/12
     */
    public function getEvidenciasEnvio(int $idEnvio): void
    {
        try {
            $data = $this->service->getEvidenciasEnvio($idEnvio);
            echo $this->successResponse($data, "Evidencias del envío obtenidas");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Registra/Sube una nueva evidencia multimedia
     * URL: {{base_url}}/Lgs_evidencias/store
     */
    public function store(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $tipoEvidencia = intval($_POST['tipo_evidencia'] ?? 1); // 1: Salida, 2: Llegada
            $rutaArchivo = $_POST['ruta_archivo'] ?? '';
            $observaciones = $_POST['observaciones'] ?? '';

            if ($idEnvio === 0 || empty($rutaArchivo)) {
                throw new Exception("Debe proporcionar una URL/Ruta de archivo válida.");
            }

            $idEvidencia = $this->service->guardarEvidencia([
                'id_envio' => $idEnvio,
                'tipo_evidencia' => $tipoEvidencia,
                'ruta_archivo' => $rutaArchivo,
                'observaciones' => $observaciones
            ], $userId);

            echo $this->successResponse(['id_evidencia' => $idEvidencia], "Evidencia registrada correctamente.");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Elimina una evidencia por ID
     * URL: {{base_url}}/Lgs_evidencias/delete/5
     */
    public function delete(int $idEvidencia): void
    {
        try {
            $this->service->borrarEvidencia($idEvidencia);
            echo $this->successResponse(null, "Evidencia eliminada correctamente.");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Confirma la entrega final en destino (Estado 7)
     * URL: {{base_url}}/Lgs_evidencias/confirmarEntrega
     */
    public function confirmarEntrega(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $fechaLlegada = $_POST['fecha_llegada_real'] ?? date('Y-m-d H:i:s');

            if ($idEnvio === 0) {
                throw new Exception("El ID de envío es requerido.");
            }

            $this->service->confirmarLlegadaDestino($idEnvio, $fechaLlegada, $userId);

            echo $this->successResponse(null, "¡Entrega confirmada! El envío ha sido completado exitosamente (Estado: Entregado).");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}
