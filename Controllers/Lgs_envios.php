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
        $catalogos = [];
        try {
            $catalogos = $this->service->getCatalogosSelect();
        } catch (Throwable $e) {}

        $this->views->getView(
            $this,
            "../Lgs_envios/index",
            [
                'page_tag' => "Envíos de Logística",
                'page_title' => "Bandeja de Envíos",
                'page_name' => "lgs_envios",
                'page_functions_js' => "functions_lgs_envios.js",
                'catalogos' => $catalogos,
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
                'id_destino'       => intval($_POST['id_destino'] ?? 0),
                'destino_nombre_libre' => $_POST['destino_nombre_libre'] ?? '',
                'km_total'         => floatval($_POST['km_total'] ?? 0),
                'fecha_tentativa_envio'   => !empty($_POST['fecha_tentativa_envio']) ? $_POST['fecha_tentativa_envio'] : null,
                'fecha_tentativa_llegada' => !empty($_POST['fecha_tentativa_llegada']) ? $_POST['fecha_tentativa_llegada'] : null,
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

        } catch (Throwable $e) {
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

    /**
     * Devuelve los datos necesarios para la vista de Acomodo de VINs en JSON:
     * - Datos de la cabecera del envío
     * - Madrinas del trasladista asignado
     * - Choferes del trasladista asignado
     * - VINs disponibles en el origen
     * - Asignaciones existentes
     * URL: {{base_url}}/Lgs_envios/getDetalleEnvioData/123
     */
    public function getDetalleEnvioData(int $idEnvio): void
    {
        try {
            $model = new Lgs_enviosModel();
            $envio = $model->getEnvioCabecera($idEnvio);
            if (empty($envio)) {
                echo $this->errorResponse("El envío especificado no existe.", 404);
                return;
            }

            $idProveedor = intval($envio['id_proveedor'] ?? 0);
            $idOrigen    = intval($envio['id_origen'] ?? 0);

            $madrinas  = $model->getMadrinasPorProveedor($idProveedor);
            $choferes  = $model->getChoferesPorProveedor($idProveedor);
            $vins      = $model->getVinsDisponiblesOrigen($idOrigen, $idEnvio);
            $existentes= $model->getAcomodoExistenteEnvio($idEnvio);

            $data = [
                'envio'      => $envio,
                'madrinas'   => $madrinas,
                'choferes'   => $choferes,
                'vins'       => $vins,
                'existentes' => $existentes
            ];

            echo $this->successResponse($data, "Datos de acomodo obtenidos correctamente");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Guarda la distribución/acomodo de VINs asignados a Madrinas/Choferes
     * URL: {{base_url}}/Lgs_envios/storeAcomodo
     */
    public function storeAcomodo(): void
    {
        try {
            $rawInput = file_get_contents('php://input');
            $dataJson = json_decode($rawInput, true);

            $idEnvio     = intval($dataJson['id_envio'] ?? $_POST['id_envio'] ?? 0);
            $asignaciones= $dataJson['asignaciones'] ?? [];

            if ($idEnvio <= 0) {
                echo $this->errorResponse("ID de envío no válido.", 400);
                return;
            }

            $model = new Lgs_enviosModel();
            $db = $model->getConexion();
            $db->beginTransaction();

            // 1. Limpiar acomodo previo de este envío
            $model->deleteAcomodoEnvio($db, $idEnvio);

            // 2. Insertar las nuevas asignaciones
            foreach ($asignaciones as $asig) {
                $model->insertVin($db, [
                    'id_envio'         => $idEnvio,
                    'id_unidad'        => intval($asig['id_unidad']),
                    'id_madrina'       => !empty($asig['id_madrina']) ? intval($asig['id_madrina']) : null,
                    'id_chofer'        => !empty($asig['id_chofer']) ? intval($asig['id_chofer']) : null,
                    'posicion_acomodo' => !empty($asig['posicion_acomodo']) ? intval($asig['posicion_acomodo']) : null,
                ]);
            }

            $db->commit();

            // 3. Recalcular costos
            $service = new Lgs_enviosService();
            $costoTotal = $service->recalcularCostoTotal($idEnvio);

            echo $this->successResponse([
                'id_envio' => $idEnvio,
                'costo_total' => $costoTotal
            ], "Acomodo de unidades guardado exitosamente");

        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}
