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
            $userId = $_SESSION['idUser'] ?? 1;
            
            $data = [
                'id_tipo_traslado' => intval($_POST['id_tipo_traslado'] ?? 0),
                'id_motivo'        => intval($_POST['id_motivo'] ?? 0),
                'id_proveedor'     => intval($_POST['id_proveedor'] ?? 0),
                'id_origen'        => intval($_POST['id_origen'] ?? 0),
                'id_destino'       => intval($_POST['id_destino'] ?? 0),
                'destino_nombre_libre' => $_POST['destino_nombre_libre'] ?? '',
                'km_total'         => 0, // Se calculará desde paradas
                'fecha_tentativa_envio'   => !empty($_POST['fecha_tentativa_envio']) ? $_POST['fecha_tentativa_envio'] : null,
                'fecha_tentativa_llegada' => !empty($_POST['fecha_tentativa_llegada']) ? $_POST['fecha_tentativa_llegada'] : null,
                'observaciones'    => $_POST['observaciones'] ?? '',
            ];

            // Paradas recibidas del formulario como JSON string
            $paradasRaw = $_POST['paradas'] ?? '[]';
            $paradas = json_decode($paradasRaw, true) ?: [];

            $idEnvio = intval($_POST['id_envio'] ?? 0);

            $model = new Lgs_enviosModel();
            $db    = $model->getConexion();
            $db->beginTransaction();

            if ($idEnvio === 0) {
                // Nuevo envío
                $folio = $model->generarFolioTransaccional($db);
                $data['folio']      = $folio;
                $data['created_by'] = $userId;
                $idEnvio = $model->insertEnvio($db, $data);
            }

            // Guardar paradas (reemplazar siempre)
            $model->deleteParadasEnvio($db, $idEnvio);
            foreach ($paradas as $idx => $p) {
                $model->insertParada($db, [
                    'id_envio'             => $idEnvio,
                    'orden'                => $idx + 1,
                    'id_destino_cat'       => !empty($p['id_destino_cat']) ? intval($p['id_destino_cat']) : null,
                    'destino_nombre_libre' => htmlspecialchars(trim($p['destino_nombre_libre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'km_tramo'             => floatval($p['km_tramo'] ?? 0),
                    'observaciones'        => htmlspecialchars(trim($p['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8'),
                ]);
            }

            // Recalcular km_total desde las paradas y actualizar destino final
            $model->actualizarKmTotalDesdeParadas($db, $idEnvio);

            $db->commit();

            echo $this->successResponse(['id_envio' => $idEnvio], "Envío guardado exitosamente");

        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
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
            $paradas   = $model->getParadasEnvio($idEnvio);

            $data = [
                'envio'      => $envio,
                'madrinas'   => $madrinas,
                'choferes'   => $choferes,
                'vins'       => $vins,
                'existentes' => $existentes,
                'paradas'    => $paradas,
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
                    'id_parada'        => !empty($asig['id_parada']) ? intval($asig['id_parada']) : null,
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

    /**
     * Endpoint AJAX para calcular distancias y tramos de ruta con Google Maps Service
     * URL: {{base_url}}/Lgs_envios/calcularDistanciaRuta
     */
    public function calcularDistanciaRuta(): void
    {
        try {
            $rawInput = file_get_contents('php://input');
            $reqData  = json_decode($rawInput, true) ?: $_POST;

            $idOrigen = intval($reqData['id_origen'] ?? 0);
            $paradas  = $reqData['paradas'] ?? [];

            if (!is_array($paradas) || empty($paradas)) {
                echo $this->successResponse(['paradas' => [], 'km_total' => 0], "Sin paradas.");
                return;
            }

            require_once "Services/GoogleMapsService.php";
            $googleMaps = new GoogleMapsService();
            $model = new Lgs_enviosModel();

            // Buscar coordenadas del Origen
            $origenInfo = null;
            if ($idOrigen > 0) {
                $origenesList = $model->getSelectCatalogos()['origenes'] ?? [];
                foreach ($origenesList as $o) {
                    if ((int)$o['id'] === $idOrigen) { $origenInfo = $o; break; }
                }
            }

            $currentLat = $origenInfo ? floatval($origenInfo['lat'] ?? 0) : 0;
            $currentLng = $origenInfo ? floatval($origenInfo['lng'] ?? 0) : 0;
            $currentText = $origenInfo ? ($origenInfo['direccion'] ?? $origenInfo['nombre'] ?? '') : '';

            $destinosList = $model->getSelectCatalogos()['destinos'] ?? [];
            $destinosMap  = [];
            foreach ($destinosList as $d) {
                $destinosMap[(int)$d['id']] = $d;
            }

            $kmTotal = 0.0;
            $paradasCalculadas = [];

            foreach ($paradas as $idx => $p) {
                $idDestCat = !empty($p['id_destino_cat']) ? intval($p['id_destino_cat']) : null;
                $destInfo  = $idDestCat ? ($destinosMap[$idDestCat] ?? null) : null;

                $nextLat = $destInfo ? floatval($destInfo['lat'] ?? 0) : 0;
                $nextLng = $destInfo ? floatval($destInfo['lng'] ?? 0) : 0;
                $nextText= $destInfo ? ($destInfo['direccion'] ?? $destInfo['nombre'] ?? '') : ($p['destino_nombre_libre'] ?? '');

                $kmTramo = 0.0;

                if ($currentLat != 0 && $currentLng != 0 && $nextLat != 0 && $nextLng != 0) {
                    $kmTramo = $googleMaps->calcularDistanciaCoords($currentLat, $currentLng, $nextLat, $nextLng);
                } else if (!empty($currentText) && !empty($nextText)) {
                    $kmTramo = $googleMaps->calcularDistanciaTexto($currentText, $nextText);
                } else {
                    $kmTramo = floatval($p['km_tramo'] ?? 150.0);
                }

                $kmTotal += $kmTramo;

                $p['km_tramo'] = $kmTramo;
                $p['direccion'] = $nextText;
                $paradasCalculadas[] = $p;

                // El punto actual para el siguiente tramo pasa a ser esta parada
                if ($nextLat != 0 && $nextLng != 0) {
                    $currentLat = $nextLat;
                    $currentLng = $nextLng;
                }
                if (!empty($nextText)) {
                    $currentText = $nextText;
                }
            }

            echo $this->successResponse([
                'paradas' => $paradasCalculadas,
                'km_total' => round($kmTotal, 2)
            ], "Ruta y distancias calculadas via Google Maps API");

        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Elimina lógicamente un envío y libera sus paradas/acomodo
     * URL: {{base_url}}/Lgs_envios/delete
     */
    public function delete(): void
    {
        try {
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            if ($idEnvio <= 0) {
                echo $this->errorResponse("ID de envío no válido.", 400);
                return;
            }

            $model = new Lgs_enviosModel();
            $db = $model->getConexion();
            $db->beginTransaction();

            // 1. Borrado lógico de la cabecera
            $stmt = $db->prepare("UPDATE lgs_envios SET deleted_at = NOW(), id_estado = 0 WHERE id_envio = ?");
            $stmt->execute([$idEnvio]);

            // 2. Liberar el acomodo (eliminar relaciones de lgs_envios_vins)
            $model->deleteAcomodoEnvio($db, $idEnvio);

            // 3. Eliminar paradas
            $model->deleteParadasEnvio($db, $idEnvio);

            $db->commit();

            echo $this->successResponse(null, "Envío eliminado exitosamente");
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}

