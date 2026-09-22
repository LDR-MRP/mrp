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
    /**
     * Endpoint AJAX para verificar si existen distancias conocidas para una secuencia de nodos.
     * Retorna si faltan distancias y cuáles son.
     * URL: {{base_url}}/Lgs_envios/verificarDistanciasRuta
     */
    public function verificarDistanciasRuta(): void
    {
        try {
            $rawInput = file_get_contents('php://input');
            $reqData  = json_decode($rawInput, true) ?: $_POST;
            $nodos    = $reqData['nodos'] ?? [];

            if (!is_array($nodos) || count($nodos) < 2) {
                echo $this->successResponse([
                    'completo'  => true,
                    'faltantes' => [],
                    'tramos'    => [],
                    'km_total'  => 0
                ], "Ruta con menos de 2 nodos.");
                return;
            }

            $model = new Lgs_enviosModel();
            $result = $model->verificarDistanciasRuta($nodos);

            echo $this->successResponse($result, "Verificación de distancias completada");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Guarda distancias ingresadas por el usuario en la memoria progresiva (lgs_distancias).
     * URL: {{base_url}}/Lgs_envios/guardarDistanciasFaltantes
     */
    public function guardarDistanciasFaltantes(): void
    {
        try {
            $rawInput = file_get_contents('php://input');
            $reqData  = json_decode($rawInput, true) ?: $_POST;
            $distancias = $reqData['distancias'] ?? [];

            if (!is_array($distancias) || empty($distancias)) {
                echo $this->errorResponse("No se enviaron distancias para guardar.", 400);
                return;
            }

            $model = new Lgs_enviosModel();
            $db    = $model->getConexion();
            $saved = 0;

            foreach ($distancias as $d) {
                $idA = intval($d['id_ubicacion_a'] ?? 0);
                $idB = intval($d['id_ubicacion_b'] ?? 0);
                $km  = floatval($d['km'] ?? 0);

                if ($idA > 0 && $idB > 0 && $km > 0) {
                    if ($model->saveDistancia($idA, $idB, $km, $db)) {
                        $saved++;
                    }
                }
            }

            echo $this->successResponse(['guardadas' => $saved], "Distancias registradas exitosamente en la memoria progresiva.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Guarda o actualiza la cabecera de un envío y sus nodos de ruta (Multi-Origen y Multi-Destino)
     * URL: {{base_url}}/Lgs_envios/store
     */
    public function store(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;

            // Procesar nodos (nuevo formato) o paradas (formato anterior)
            $nodosRaw = $_POST['nodos'] ?? null;
            if ($nodosRaw) {
                $nodos = is_array($nodosRaw) ? $nodosRaw : (json_decode($nodosRaw, true) ?: []);
            } else {
                // Retrocompatibilidad: Origen único + array de paradas
                $idOrigen = intval($_POST['id_origen'] ?? 0);
                $paradasRaw = $_POST['paradas'] ?? '[]';
                $paradas = is_array($paradasRaw) ? $paradasRaw : (json_decode($paradasRaw, true) ?: []);
                $nodos = [];
                if ($idOrigen > 0) {
                    $nodos[] = [
                        'orden' => 0,
                        'id_ubicacion' => $idOrigen,
                        'km_tramo' => 0.00
                    ];
                }
                foreach ($paradas as $idx => $p) {
                    $nodos[] = [
                        'orden' => $idx + 1,
                        'id_ubicacion' => !empty($p['id_destino_cat']) ? intval($p['id_destino_cat']) : (!empty($p['id_ubicacion']) ? intval($p['id_ubicacion']) : null),
                        'destino_nombre_libre' => htmlspecialchars(trim($p['destino_nombre_libre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        'km_tramo' => floatval($p['km_tramo'] ?? 0),
                        'observaciones' => htmlspecialchars(trim($p['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    ];
                }
            }

            if (count($nodos) < 2) {
                echo $this->errorResponse("La ruta debe contener al menos un punto de partida y un destino.", 400);
                return;
            }

            // Validar que no haya tramos consecutivos con origen y destino idénticos
            for ($i = 1; $i < count($nodos); $i++) {
                $locA = intval($nodos[$i - 1]['id_ubicacion'] ?? 0);
                $locB = intval($nodos[$i]['id_ubicacion'] ?? 0);
                if ($locA > 0 && $locB > 0 && $locA === $locB) {
                    echo $this->errorResponse("El Punto #" . ($i + 1) . " no puede ser la misma ubicación que el Punto #" . $i . ". Una ruta no puede tener tramos que salgan y lleguen al mismo punto.", 400);
                    return;
                }
            }

            $model = new Lgs_enviosModel();
            $db    = $model->getConexion();
            $model->ensureDistanciasTable($db);
            $db->beginTransaction();

            // Identificar primer origen y último destino para compatibilidad
            $firstLoc = intval($nodos[0]['id_ubicacion'] ?? 0);
            $lastLoc  = intval($nodos[count($nodos) - 1]['id_ubicacion'] ?? 0);
            $lastFree = $nodos[count($nodos) - 1]['destino_nombre_libre'] ?? '';

            $data = [
                'id_tipo_traslado' => intval($_POST['id_tipo_traslado'] ?? 0),
                'id_motivo'        => intval($_POST['id_motivo'] ?? 0),
                'id_proveedor'     => intval($_POST['id_proveedor'] ?? 0),
                'id_origen'        => $firstLoc > 0 ? $firstLoc : intval($_POST['id_origen'] ?? 0),
                'id_destino'       => $lastLoc > 0 ? $lastLoc : intval($_POST['id_destino'] ?? 0),
                'destino_nombre_libre' => !empty($lastFree) ? $lastFree : ($_POST['destino_nombre_libre'] ?? ''),
                'km_total'         => 0,
                'fecha_tentativa_envio'   => !empty($_POST['fecha_tentativa_envio']) ? str_replace('T', ' ', $_POST['fecha_tentativa_envio']) : null,
                'fecha_tentativa_llegada' => !empty($_POST['fecha_tentativa_llegada']) ? str_replace('T', ' ', $_POST['fecha_tentativa_llegada']) : null,
                'observaciones'    => $_POST['observaciones'] ?? '',
            ];

            $idEnvio = intval($_POST['id_envio'] ?? 0);

            if ($idEnvio === 0) {
                // Nuevo envío
                $folio = $model->generarFolioTransaccional($db);
                $data['folio']      = $folio;
                $data['created_by'] = $userId;
                $idEnvio = $model->insertEnvio($db, $data);
            } else {
                // Verificar que el envío pueda ser editado (solo si está en Borrador [1] o Confirmado [8])
                $existingEnvio = $model->getEnvioCabecera($idEnvio);
                if (empty($existingEnvio)) {
                    echo $this->errorResponse("El envío a actualizar no existe.", 404);
                    return;
                }
                
                $idEstadoStr = intval($existingEnvio['id_estado']);
                if ($idEstadoStr !== 1 && $idEstadoStr !== 8) {
                    echo $this->errorResponse("El envío no puede ser modificado porque ya está en proceso de planeación o ejecución (Estado actual: $idEstadoStr).", 400);
                    return;
                }

                // Si estaba Confirmado (8) y lo editan, lo regresamos a Borrador (1) para que deba confirmarse de nuevo
                $newEstado = ($idEstadoStr === 8) ? 1 : $idEstadoStr;

                // Actualizar cabecera existente
                $stmtUpd = $db->prepare("UPDATE lgs_envios SET 
                                            id_estado = :id_estado,
                                            id_tipo_traslado = :id_tipo_traslado,
                                            id_motivo = :id_motivo,
                                            id_proveedor = :id_proveedor,
                                            id_origen = :id_origen,
                                            id_destino = :id_destino,
                                            destino_nombre_libre = :destino_nombre_libre,
                                            fecha_tentativa_envio = :fecha_tentativa_envio,
                                            fecha_tentativa_llegada = :fecha_tentativa_llegada,
                                            observaciones = :observaciones,
                                            updated_by = :updated_by,
                                            updated_at = NOW()
                                         WHERE id_envio = :id_envio");
                $stmtUpd->execute([
                    'id_estado'        => $newEstado,
                    'id_tipo_traslado' => $data['id_tipo_traslado'],
                    'id_motivo'        => $data['id_motivo'],
                    'id_proveedor'     => $data['id_proveedor'],
                    'id_origen'        => $data['id_origen'],
                    'id_destino'       => $data['id_destino'],
                    'destino_nombre_libre' => $data['destino_nombre_libre'],
                    'fecha_tentativa_envio' => $data['fecha_tentativa_envio'],
                    'fecha_tentativa_llegada' => $data['fecha_tentativa_llegada'],
                    'observaciones'    => $data['observaciones'],
                    'updated_by'       => $userId,
                    'id_envio'         => $idEnvio
                ]);
            }

            // Guardar todos los nodos de la ruta (reemplazar anteriores)
            $model->deleteParadasEnvio($db, $idEnvio);

            $kmTotalAcumulado = 0.0;
            $hasNewCols = true;
            foreach ($nodos as $idx => $nodo) {
                $locId = !empty($nodo['id_ubicacion']) ? intval($nodo['id_ubicacion']) : (!empty($nodo['id_destino_cat']) ? intval($nodo['id_destino_cat']) : null);
                $kmTramo = floatval($nodo['km_tramo'] ?? 0);

                // Si es tramo > 0 y no trae KM explícito, intentar resolver desde memoria
                if ($idx > 0 && $kmTramo <= 0) {
                    $prevLocId = !empty($nodos[$idx - 1]['id_ubicacion']) ? intval($nodos[$idx - 1]['id_ubicacion']) : null;
                    if ($prevLocId > 0 && $locId > 0) {
                        $distMem = $model->getDistanciaEntre($prevLocId, $locId, $db);
                        if ($distMem !== null && $distMem > 0) {
                            $kmTramo = $distMem;
                        }
                    }
                } elseif ($idx > 0 && $kmTramo > 0) {
                    // Si el usuario capturó la distancia, guardarla en memoria progresiva
                    $prevLocId = !empty($nodos[$idx - 1]['id_ubicacion']) ? intval($nodos[$idx - 1]['id_ubicacion']) : null;
                    if ($prevLocId > 0 && $locId > 0) {
                        $model->saveDistancia($prevLocId, $locId, $kmTramo, $db);
                    }
                }

                $kmTotalAcumulado += $kmTramo;

                // Detectar si las columnas nuevas existen (solo en la primera iteración)
                if ($idx === 0) {
                    $hasNewCols = true;
                    try {
                        $chk = $db->query("SELECT tipo_nodo, fecha_estimada FROM lgs_envios_nodos LIMIT 0");
                    } catch (Throwable $checkEx) {
                        $hasNewCols = false;
                    }
                }

                if ($hasNewCols) {
                    $tipoNodo = !empty($nodo['tipo_nodo']) ? $nodo['tipo_nodo'] : ($idx === 0 ? 'origen' : 'entrega');
                    $fechaEstimada = !empty($nodo['fecha_estimada']) ? str_replace('T', ' ', $nodo['fecha_estimada']) : null;

                    $stmtInsertNodo = $db->prepare("INSERT INTO lgs_envios_nodos 
                        (id_envio, orden, tipo_nodo, id_ubicacion, destino_nombre_libre, km_tramo_anterior, observaciones, fecha_estimada) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtInsertNodo->execute([
                        $idEnvio,
                        $idx,
                        $tipoNodo,
                        $locId,
                        htmlspecialchars(trim($nodo['destino_nombre_libre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        $kmTramo,
                        htmlspecialchars(trim($nodo['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        $fechaEstimada
                    ]);
                } else {
                    $stmtInsertNodo = $db->prepare("INSERT INTO lgs_envios_nodos 
                        (id_envio, orden, id_ubicacion, destino_nombre_libre, km_tramo_anterior, observaciones) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtInsertNodo->execute([
                        $idEnvio,
                        $idx,
                        $locId,
                        htmlspecialchars(trim($nodo['destino_nombre_libre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        $kmTramo,
                        htmlspecialchars(trim($nodo['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8')
                    ]);
                }
            }

            // Actualizar km_total en cabecera
            $db->prepare("UPDATE lgs_envios SET km_total = ? WHERE id_envio = ?")->execute([$kmTotalAcumulado, $idEnvio]);

            // Recomputación automática si el envío ya tenía unidades asignadas
            try {
                $service = new Lgs_enviosService();
                $service->recalcularCostoTotal($idEnvio, $db);
            } catch (Throwable $e) {}

            $db->commit();

            echo $this->successResponse(['id_envio' => $idEnvio], "Envío guardado exitosamente con ruta multi-nodo.");

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
     * - Paradas y Nodos ordenados de la ruta
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

            $madrinas   = $model->getMadrinasPorProveedor($idProveedor);
            $choferes   = $model->getChoferesPorProveedor($idProveedor);
            $vins       = $model->getVinsDisponiblesOrigen($idOrigen, $idEnvio);
            $existentes = $model->getAcomodoExistenteEnvio($idEnvio);
            $paradas    = $model->getParadasEnvio($idEnvio);
            $nodos      = $model->getNodosEnvio($idEnvio);

            // Filtrar VINs para mostrar solo los que van a los destinos de la ruta
            if (!empty($nodos) && !empty($vins)) {
                $destinosValidos = [];
                foreach ($nodos as $nodo) {
                    if ($nodo['orden'] > 0) {
                        $nombreDestino = strtolower(trim($nodo['nombre'] ?? ''));
                        if (!empty($nombreDestino)) {
                            $destinosValidos[] = $nombreDestino;
                        }
                    }
                }
                
                if (!empty($destinosValidos)) {
                    $vinsFiltrados = [];
                    foreach ($vins as $vin) {
                        $destinoVin = strtolower(trim($vin['destino'] ?? ''));
                        $coincide = false;
                        foreach ($destinosValidos as $dv) {
                            // Búsqueda bidireccional para coincidencias parciales (ej. 'Toluca' vs 'Distribuidor Toluca')
                            if (strpos($destinoVin, $dv) !== false || strpos($dv, $destinoVin) !== false) {
                                $coincide = true;
                                break;
                            }
                        }
                        if ($coincide) {
                            $vinsFiltrados[] = $vin;
                        }
                    }
                    $vins = array_values($vinsFiltrados);
                }
            }

            $data = [
                'envio'      => $envio,
                'madrinas'   => $madrinas,
                'choferes'   => $choferes,
                'vins'       => $vins,
                'existentes' => $existentes,
                'paradas'    => $paradas,
                'nodos'      => $nodos,
            ];

            echo $this->successResponse($data, "Datos de acomodo obtenidos correctamente");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Guarda la distribución/acomodo de VINs asignados a Madrinas/Choferes con soporte de subida y bajada por nodo
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
            
            $existingEnvio = $model->getEnvioCabecera($idEnvio);
            if (empty($existingEnvio)) {
                echo $this->errorResponse("El envío no existe.", 404);
                return;
            }
            
            $idEstadoStr = intval($existingEnvio['id_estado']);
            if ($idEstadoStr !== 1 && $idEstadoStr !== 8) {
                echo $this->errorResponse("El acomodo no puede ser modificado porque el envío ya está asignado a una planeación o en ejecución.", 400);
                return;
            }

            $model = new Lgs_enviosModel();
            $db = $model->getConexion();
            $db->beginTransaction();

            // 1. Limpiar acomodo previo de este envío
            $model->deleteAcomodoEnvio($db, $idEnvio);

            // 2. Insertar las nuevas asignaciones con id_nodo_subida e id_nodo_bajada
            $assignedUnitIds = [];
            foreach ($asignaciones as $asig) {
                $uId = intval($asig['id_unidad'] ?? 0);
                if ($uId > 0) {
                    $assignedUnitIds[] = $uId;
                    $model->insertVin($db, [
                        'id_envio'         => $idEnvio,
                        'id_unidad'        => $uId,
                        'id_parada'        => !empty($asig['id_parada']) ? intval($asig['id_parada']) : null,
                        'id_nodo_subida'   => !empty($asig['id_nodo_subida']) ? intval($asig['id_nodo_subida']) : null,
                        'id_nodo_bajada'   => !empty($asig['id_nodo_bajada']) ? intval($asig['id_nodo_bajada']) : null,
                        'id_madrina'       => !empty($asig['id_madrina']) ? intval($asig['id_madrina']) : null,
                        'id_chofer'        => !empty($asig['id_chofer']) ? intval($asig['id_chofer']) : null,
                        'posicion_acomodo' => !empty($asig['posicion_acomodo']) ? intval($asig['posicion_acomodo']) : null,
                    ]);
                }
            }

            // 3. Sincronizar estados en la bandeja de salida (lgs_unidades)
            if (!empty($assignedUnitIds)) {
                $placeholders = implode(',', array_fill(0, count($assignedUnitIds), '?'));
                $stmtState = $db->prepare("UPDATE lgs_unidades SET id_estado_proceso = 2, updated_at = NOW() WHERE id_unidad IN ({$placeholders})");
                $stmtState->execute($assignedUnitIds);
            }

            // Unidades que no estén en ningún envío activo regresan a Pendiente (1)
            $stmtReset = $db->prepare("UPDATE lgs_unidades 
                                       SET id_estado_proceso = 1, updated_at = NOW() 
                                       WHERE id_estado_proceso = 2 
                                         AND id_unidad NOT IN (
                                             SELECT ev.id_unidad 
                                             FROM lgs_envios_vins ev 
                                             INNER JOIN lgs_envios e ON ev.id_envio = e.id_envio 
                                             WHERE e.deleted_at IS NULL AND e.id_estado NOT IN (0, 7)
                                         )");
            $stmtReset->execute();

            // Si el usuario confirma finalizar el envío, pasarlo a estado 8 (Confirmado / Listo para planear)
            $finalizar = !empty($dataJson['finalizar']) && $dataJson['finalizar'] == true;
            if ($finalizar) {
                $stmtFinalizar = $db->prepare("UPDATE lgs_envios SET id_estado = 8 WHERE id_envio = ?");
                $stmtFinalizar->execute([$idEnvio]);
                
                // Si el envío se confirma, eliminamos estos VINs de cualquier OTRO envío que esté en Borrador (1)
                if (!empty($assignedUnitIds)) {
                    $inIds = implode(',', array_fill(0, count($assignedUnitIds), '?'));
                    $sqlDelVins = "DELETE ev FROM lgs_envios_vins ev
                                   INNER JOIN lgs_envios e ON ev.id_envio = e.id_envio
                                   WHERE e.id_estado = 1 
                                     AND e.id_envio != ? 
                                     AND ev.id_unidad IN ($inIds)";
                    $paramsDelVins = array_merge([$idEnvio], $assignedUnitIds);
                    $stmtDelVins = $db->prepare($sqlDelVins);
                    $stmtDelVins->execute($paramsDelVins);
                }
            }

            $db->commit();

            // 4. Recalcular costos con el motor de factores y memoria de distancias
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
     * Endpoint AJAX para calcular distancias y tramos de ruta con Memoria Progresiva (lgs_distancias) y Tarifario
     * URL: {{base_url}}/Lgs_envios/calcularDistanciaRuta
     */
    public function calcularDistanciaRuta(): void
    {
        try {
            $rawInput = file_get_contents('php://input');
            $reqData  = json_decode($rawInput, true) ?: $_POST;

            // Soporta tanto array de 'nodos' (nuevo) como 'paradas' (anterior)
            $nodos = $reqData['nodos'] ?? null;
            if (!$nodos) {
                $idOrigen = intval($reqData['id_origen'] ?? 0);
                $paradas  = $reqData['paradas'] ?? [];
                $nodos = [];
                if ($idOrigen > 0) {
                    $nodos[] = ['orden' => 0, 'id_ubicacion' => $idOrigen];
                }
                foreach ($paradas as $idx => $p) {
                    $nodos[] = [
                        'orden' => $idx + 1,
                        'id_ubicacion' => !empty($p['id_destino_cat']) ? intval($p['id_destino_cat']) : (!empty($p['id_ubicacion']) ? intval($p['id_ubicacion']) : null),
                        'destino_nombre_libre' => $p['destino_nombre_libre'] ?? '',
                        'km_tramo' => floatval($p['km_tramo'] ?? 0)
                    ];
                }
            }

            if (!is_array($nodos) || count($nodos) < 2) {
                echo $this->successResponse(['paradas' => [], 'km_total' => 0, 'completo' => true], "Sin tramos suficientes.");
                return;
            }

            $model = new Lgs_enviosModel();
            $verif = $model->verificarDistanciasRuta($nodos);

            // Mapear respuesta compatible para el frontend
            $paradasCalculadas = [];
            foreach ($verif['tramos'] as $t) {
                $paradasCalculadas[] = [
                    'orden'                => $t['tramo_indice'],
                    'id_destino_cat'       => $t['id_ubicacion_b'],
                    'id_ubicacion'         => $t['id_ubicacion_b'],
                    'nombre_a'             => $t['nombre_a'],
                    'nombre_b'             => $t['nombre_b'],
                    'km_tramo'             => $t['km'],
                ];
            }

            echo $this->successResponse([
                'paradas'   => $paradasCalculadas,
                'km_total'  => $verif['km_total'],
                'completo'  => $verif['completo'],
                'faltantes' => $verif['faltantes']
            ], "Ruta y distancias procesadas");

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
            
            $existingEnvio = $model->getEnvioCabecera($idEnvio);
            if (empty($existingEnvio)) {
                echo $this->errorResponse("El envío no existe.", 404);
                return;
            }
            
            $idEstadoStr = intval($existingEnvio['id_estado']);
            if ($idEstadoStr !== 1 && $idEstadoStr !== 8) {
                echo $this->errorResponse("El envío no puede ser eliminado porque ya está asignado a una planeación o en ejecución.", 400);
                return;
            }
            $db = $model->getConexion();
            $db->beginTransaction();

            // 1. Borrado lógico de la cabecera
            $stmt = $db->prepare("UPDATE lgs_envios SET deleted_at = NOW(), id_estado = 0 WHERE id_envio = ?");
            $stmt->execute([$idEnvio]);

            // 2. Liberar el acomodo (eliminar relaciones de lgs_envios_vins)
            $model->deleteAcomodoEnvio($db, $idEnvio);

            // 3. Eliminar paradas
            $model->deleteParadasEnvio($db, $idEnvio);

            // 4. Limpiar tramos de costos
            try {
                $db->prepare("DELETE FROM lgs_envios_tramos_costos WHERE id_envio = ?")->execute([$idEnvio]);
            } catch (Throwable $e) {}

            $db->commit();

            echo $this->successResponse(null, "Envío eliminado exitosamente");
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Reabre / desbloquea un envío regresándolo a estado 1 (Creado / Borrador)
     * URL: {{base_url}}/Lgs_envios/reabrir
     */
    public function reabrir(): void
    {
        try {
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            if ($idEnvio <= 0) {
                echo $this->errorResponse("ID de envío no válido.", 400);
                return;
            }

            $model = new Lgs_enviosModel();
            $model->reabrirEnvio($idEnvio);

            echo $this->successResponse(null, "Envío reabierto y desbloqueado exitosamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Función temporal para ejecutar la migración de base de datos
     */
    public function migrarDB()
    {
        try {
            $model = new Lgs_enviosModel();
            $db = $model->getConexion();
            
            // Columna tipo_nodo
            try {
                $db->exec("ALTER TABLE `lgs_envios_nodos` ADD COLUMN `tipo_nodo` ENUM('origen','carga','entrega') NOT NULL DEFAULT 'entrega' AFTER `orden`");
                echo "Columna 'tipo_nodo' creada exitosamente.<br>";
            } catch (Throwable $e) {
                echo "Nota sobre 'tipo_nodo': " . $e->getMessage() . "<br>";
            }

            // Columna fecha_estimada
            try {
                $db->exec("ALTER TABLE `lgs_envios_nodos` ADD COLUMN `fecha_estimada` DATETIME NULL AFTER `observaciones`");
                echo "Columna 'fecha_estimada' creada exitosamente.<br>";
            } catch (Throwable $e) {
                echo "Nota sobre 'fecha_estimada': " . $e->getMessage() . "<br>";
            }

            echo "<br><b>Migración finalizada. Ya puedes cerrar esta pestaña y probar guardar tu ruta.</b>";

        } catch (Throwable $e) {
            echo "Error crítico: " . $e->getMessage();
        }
    }
}
?>
