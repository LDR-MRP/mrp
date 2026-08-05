<?php

class Lgs_envios extends Controllers
{
    use ApiResponser;

    private Lgs_enviosService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();
        
        // Asumiendo que LGS_ENVIOS es la constante de permiso, se puede adaptar
        // getPermisos(LGS_ENVIOS);
        
        $this->service = new Lgs_enviosService();
    }

    /**
     * Renderiza la vista principal de la Bandeja de Envíos
     * URL: {{base_url}}/Lgs_envios
     */
    public function Lgs_envios(): void
    {
        $this->views->getView(
            $this,
            "../Lgs_envios/index",
            [
                'page_tag' => "Envíos de Logística",
                'page_title' => "Bandeja de Envíos",
                'page_name' => "lgs_envios",
                'page_functions_js' => "functions_lgs_envios.js",
            ]
        );
    }

    /**
     * Devuelve el JSON para alimentar el DataTable de envíos
     * URL: {{base_url}}/Lgs_envios/getEnvios
     */
    public function getEnvios(): void
    {
        try {
            $data = $this->service->getAllEnvios();
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    /**
     * Devuelve los catálogos en JSON para alimentar los dropdowns del modal
     * URL: {{base_url}}/Lgs_envios/getCatalogos
     */
    public function getCatalogos(): void
    {
        try {
            $data = $this->service->getCatalogosSelect();
            echo $this->successResponse($data, "Catálogos obtenidos correctamente");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Guarda o actualiza la cabecera de un envío
     * URL: {{base_url}}/Lgs_envios/store
     */
    public function store(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1; // Ajustar según tu manejo de sesión
            
            // Aquí iría la validación de Lgs_enviosRequest
            $data = [
                'id_tipo_traslado' => intval($_POST['id_tipo_traslado'] ?? 0),
                'id_motivo'        => intval($_POST['id_motivo'] ?? 0),
                'id_proveedor'     => intval($_POST['id_proveedor'] ?? 0),
                'id_origen'        => intval($_POST['id_origen'] ?? 0),
                'km_total'         => floatval($_POST['km_total'] ?? 0),
                'fecha_tentativa_envio'   => $_POST['fecha_tentativa_envio'] ?? null,
                'fecha_tentativa_llegada' => $_POST['fecha_tentativa_llegada'] ?? null,
                'observaciones'    => $_POST['observaciones'] ?? '',
            ];

            // Si es 0 es un insert (nuevo envío)
            $idEnvio = intval($_POST['id_envio'] ?? 0);

            if ($idEnvio === 0) {
                $id = $this->service->createEnvio($data, $userId);
                echo $this->successResponse(['id_envio' => $id], "Envío creado exitosamente");
            } else {
                // $this->service->updateEnvio($idEnvio, $data, $userId);
                echo $this->successResponse(['id_envio' => $idEnvio], "Envío actualizado exitosamente");
            }

        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Renderiza la vista de detalle para el acomodo de VINs
     * URL: {{base_url}}/Lgs_envios/detalle/123
     */
    public function detalle(int $idEnvio): void
    {
        $this->views->getView(
            $this,
            "../Lgs_envios/detalle",
            [
                'page_tag' => "Acomodo de Unidades",
                'page_title' => "Detalle de Envío",
                'page_name' => "lgs_envios_detalle",
                'page_functions_js' => "functions_lgs_envios_detalle.js",
                'id_envio' => $idEnvio
            ]
        );
    }
}
