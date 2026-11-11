<?php

class Lgs_evidencias extends Controllers
{
    use ApiResponser;

    private Lgs_evidenciasService $service;

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
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    /**
     * Devuelve la lista de evidencias asociadas a un envío
     * URL: {{base_url}}/Lgs_evidencias/getEvidenciasEnvio/12
     */
    public function getEvidenciasEnvio($idEnvio = 0): void
    {
        try {
            $idEnvio = intval($idEnvio);
            if ($idEnvio <= 0) {
                throw new Exception("ID de envío no válido.");
            }
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
            $idUnidad = !empty($_POST['id_unidad']) ? intval($_POST['id_unidad']) : null;
            $tipoEvidencia = intval($_POST['tipo_evidencia'] ?? 1); // 1: Salida, 2: Llegada
            $rutaArchivo = $_POST['ruta_archivo'] ?? '';
            $observaciones = $_POST['observaciones'] ?? $_POST['notas'] ?? '';

            // Soporte para Carga Masiva mediante archivo ZIP
            $zipFile = null;
            if (isset($_FILES['archivo_zip']) && $_FILES['archivo_zip']['error'] === UPLOAD_ERR_OK) {
                $zipFile = $_FILES['archivo_zip'];
            } elseif (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
                if ($ext === 'zip') {
                    $zipFile = $_FILES['archivo'];
                }
            }

            if ($zipFile !== null) {
                if ($idEnvio === 0) {
                    throw new Exception("El ID de envío es obligatorio para procesar el archivo ZIP.");
                }
                $ids = $this->service->guardarEvidenciasZip($idEnvio, $idUnidad, $tipoEvidencia, $zipFile, $observaciones, $userId);
                echo $this->successResponse(['total_fotos' => count($ids), 'ids' => $ids], "Se procesaron y guardaron " . count($ids) . " fotografías desde el archivo ZIP.");
                return;
            }

            // Si viene un archivo subido individual por formulario
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'Assets/images/uploads/evidencias/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $ext = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
                $fileName = 'ev_admin_' . $idEnvio . '_' . md5(uniqid()) . '.' . $ext;
                $destPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destPath)) {
                    $rutaArchivo = $destPath;
                }
            }

            if ($idEnvio === 0 || empty($rutaArchivo)) {
                throw new Exception("Debe proporcionar un archivo o una ruta válida de evidencia.");
            }

            $idEvidencia = $this->service->guardarEvidencia([
                'id_envio' => $idEnvio,
                'id_unidad' => $idUnidad,
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
     * GET: Descarga un paquete ZIP con todas las evidencias fotográficas de un envío
     * URL: {{base_url}}/Lgs_evidencias/descargarZipEvidencias/15
     */
    public function descargarZipEvidencias($idEnvio = 0): void
    {
        try {
            $idEnvio = intval($idEnvio);
            if ($idEnvio <= 0) {
                throw new Exception("ID de envío no válido.");
            }

            $resultado = $this->service->generarZipEvidenciasEnvio($idEnvio);
            $zipPath = $resultado['path'];
            $filename = $resultado['filename'];

            if (!file_exists($zipPath)) {
                throw new Exception("El archivo ZIP no se generó correctamente.");
            }

            if (ob_get_level()) {
                ob_end_clean();
            }

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($zipPath));
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

            readfile($zipPath);
            @unlink($zipPath);
            exit;
        } catch (Throwable $e) {
            echo "<script>alert('Error al descargar evidencias: " . addslashes($e->getMessage()) . "'); window.close();</script>";
            exit;
        }
    }

    /**
     * POST: Elimina una evidencia por ID
     * URL: {{base_url}}/Lgs_evidencias/delete/5
     */
    public function delete($idEvidencia = 0): void
    {
        try {
            $idEvidencia = intval($idEvidencia);
            if ($idEvidencia <= 0) {
                throw new Exception("ID de evidencia no válido.");
            }
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

    /**
     * Genera el PDF oficial de la Hoja de Entrega / Traspaso en el Módulo de Evidencias y Cierre
     * URL: {{base_url}}/Lgs_evidencias/hoja_entrega_pdf/123/456
     */
    public function hoja_entrega_pdf($idEnvio = 0, $idUnidad = 0): void
    {
        if (is_string($idEnvio) && str_contains($idEnvio, ',')) {
            [$idEnvio, $idUnidad] = array_pad(array_map('intval', explode(',', $idEnvio)), 2, 0);
        } else {
            $idEnvio = (int)$idEnvio;
            $idUnidad = (int)$idUnidad;
        }

        try {
            require_once 'Models/Lgs_enviosModel.php';
            require_once 'Services/Lgs_enviosService.php';

            $model = new Lgs_enviosModel();
            $enviosService = new Lgs_enviosService();
            $db = $model->getConexion();
            
            // Asegurar que el costo unitario por segmento esté calculado para este envío
            if (file_exists(__DIR__ . '/../sync_unit_costs.php')) { @unlink(__DIR__ . '/../sync_unit_costs.php'); }
            $enviosService->asegurarCostosVins($idEnvio, $db);

            // Si no se proporcionó una unidad específica, tomar la primera unidad del envío
            if ($idUnidad <= 0) {
                $stmtFirstVin = $db->prepare("SELECT id_unidad FROM lgs_envios_vins WHERE id_envio = ? ORDER BY id ASC LIMIT 1");
                $stmtFirstVin->execute([$idEnvio]);
                $idUnidad = (int)$stmtFirstVin->fetchColumn();
            }

            $stmt = $db->prepare("
                SELECT 
                    e.folio, e.fecha_tentativa_envio, e.fecha_tentativa_llegada, e.km_total, e.costo_total,
                    e.id_tipo_traslado, e.modalidad_calculo,
                    mo.descripcion AS motivo_nombre,
                    COALESCE(u.vin, ut.clave, CONCAT('VIN-', ev.id_unidad)) AS vin,
                    COALESCE(u.modelo, 'Unidad') AS modelo,
                    'Blanco' AS color,
                    ev.costo_unidad,
                    ev.tipo_servicio,
                    COALESCE(pr.nombre_comercial, pr.razon_social, 'LDR Solutions') AS proveedor_nombre,
                    COALESCE(m.numero_economico, 'Sin Asignar') AS madrina_nombre,
                    COALESCE(m.placas, 'S/P') AS madrina_placas,
                    COALESCE(CONCAT(c.nombre, ' ', c.apellidos), 'Sin Chofer') AS chofer_nombre,
                    (SELECT nombre FROM lgs_cat_ubicaciones WHERE id_ubicacion = e.id_origen) AS origen_global,
                    (SELECT nombre FROM lgs_cat_ubicaciones WHERE id_ubicacion = e.id_destino) AS destino_global,
                    (SELECT nombre FROM lgs_cat_ubicaciones WHERE id_ubicacion = ns.id_ubicacion) AS subida_nombre,
                    (SELECT nombre FROM lgs_cat_ubicaciones WHERE id_ubicacion = nb.id_ubicacion) AS bajada_nombre,
                    (SELECT km_tramo FROM lgs_envios_tramos_costos WHERE id_envio = e.id_envio ORDER BY id_tramo_costo DESC LIMIT 1) as km_tramo,
                    (SELECT AVG(factor_aplicado) FROM lgs_envios_tramos_costos WHERE id_envio = e.id_envio) as factor_promedio
                FROM lgs_envios_vins ev
                INNER JOIN lgs_envios e ON e.id_envio = ev.id_envio
                LEFT JOIN lgs_cat_motivo_envio mo ON mo.id_motivo = e.id_motivo
                LEFT JOIN prv_cat_proveedores pr ON pr.id_proveedor = e.id_proveedor
                LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                LEFT JOIN lgs_unidades lu ON ev.id_unidad = lu.id_unidad
                LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                LEFT JOIN prv_det_madrinas m ON ev.id_madrina = m.id_madrina
                LEFT JOIN prv_det_choferes c ON ev.id_chofer = c.id_chofer
                LEFT JOIN lgs_envios_nodos ns ON ns.id_nodo = ev.id_nodo_subida
                LEFT JOIN lgs_envios_nodos nb ON nb.id_nodo = ev.id_nodo_bajada
                WHERE ev.id_envio = ? AND ev.id_unidad = ?
            ");
            $stmt->execute([$idEnvio, $idUnidad]);
            $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

            if (empty($detalle)) {
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                echo "<div style='font-family: Arial, sans-serif; padding: 40px; text-align: center; color: #333;'>";
                echo "<h2>No se encontró información</h2>";
                echo "<p>No existe registro para el Envío #{$idEnvio} con la Unidad #{$idUnidad}.</p>";
                echo "</div>";
                exit;
            }

            // Resolver información de Segmento y Tarifas
            $segId = $enviosService->resolveSegmentoForUnit($db, $idUnidad);
            $idTipoTraslado = (int)($detalle['id_tipo_traslado'] ?? 1);
            if ($idTipoTraslado === 2) {
                // Tarifas rodando (Chofer)
                $segmentosInfo = [
                    1 => ['nombre' => 'LDT (Ligeros)', 'tarifa' => 18.00],
                    2 => ['nombre' => 'MDT (Medianos)', 'tarifa' => 20.00],
                    3 => ['nombre' => 'HDT (Pesados)', 'tarifa' => 25.00],
                    4 => ['nombre' => 'BUSES', 'tarifa' => 25.00],
                    5 => ['nombre' => 'LOWBOY', 'tarifa' => 25.00],
                ];
            } else {
                $segmentosInfo = [
                    1 => ['nombre' => 'LDT (Ligeros)', 'tarifa' => 27.00],
                    2 => ['nombre' => 'MDT (Medianos)', 'tarifa' => 30.00],
                    3 => ['nombre' => 'HDT (Pesados)', 'tarifa' => 40.00],
                    4 => ['nombre' => 'BUSES', 'tarifa' => 50.00],
                    5 => ['nombre' => 'LOWBOY', 'tarifa' => 60.00],
                ];
            }
            $sInfo = $segmentosInfo[$segId] ?? $segmentosInfo[1];

            $detalle['segmento'] = $sInfo['nombre'];
            $detalle['costo_base_tramo'] = (float)$sInfo['tarifa'];
            $detalle['costo_estimado'] = (float)($detalle['costo_total'] ?? 0.0);
            $detalle['costo_total_unitario'] = (float)($detalle['costo_unidad'] ?? 0.0);

            // 1. Obtener datos de este VIN (nodos de subida y bajada)
            $stmtVinInfo = $db->prepare("SELECT id, id_unidad, id_nodo_subida, id_nodo_bajada, id_parada FROM lgs_envios_vins WHERE id_envio = ? AND id_unidad = ?");
            $stmtVinInfo->execute([$idEnvio, $idUnidad]);
            $currentVin = $stmtVinInfo->fetch(PDO::FETCH_ASSOC);

            // 2. Obtener todos los nodos del envío
            $stmtNodos = $db->prepare("
                SELECT n.id_nodo, n.orden, n.id_ubicacion, 
                       COALESCE(u.nombre, n.destino_nombre_libre, CONCAT('Parada ', n.orden)) AS nombre
                FROM lgs_envios_nodos n
                LEFT JOIN lgs_cat_ubicaciones u ON n.id_ubicacion = u.id_ubicacion
                WHERE n.id_envio = ?
                ORDER BY n.orden ASC
            ");
            $stmtNodos->execute([$idEnvio]);
            $nodos = $stmtNodos->fetchAll(PDO::FETCH_ASSOC);
            $nodosMap = [];
            foreach ($nodos as $nd) {
                $nodosMap[$nd['id_nodo']] = $nd;
            }

            $ordenSubidaVin = 0;
            $ordenBajadaVin = count($nodos) > 0 ? (count($nodos) - 1) : 1;
            if (!empty($currentVin['id_nodo_subida']) && isset($nodosMap[$currentVin['id_nodo_subida']])) {
                $ordenSubidaVin = (int)$nodosMap[$currentVin['id_nodo_subida']]['orden'];
            }
            if (!empty($currentVin['id_nodo_bajada']) && isset($nodosMap[$currentVin['id_nodo_bajada']])) {
                $ordenBajadaVin = (int)$nodosMap[$currentVin['id_nodo_bajada']]['orden'];
            } elseif (!empty($currentVin['id_parada']) && isset($nodosMap[$currentVin['id_parada']])) {
                $ordenBajadaVin = (int)$nodosMap[$currentVin['id_parada']]['orden'];
            }

            // 3. Obtener todos los VINs del envío para conocer la ocupación y segmentos en cada tramo
            $stmtAllVins = $db->prepare("SELECT id, id_unidad, id_nodo_subida, id_nodo_bajada, id_parada FROM lgs_envios_vins WHERE id_envio = ?");
            $stmtAllVins->execute([$idEnvio]);
            $allVins = $stmtAllVins->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allVins as &$v) {
                $v['id_segmento'] = $enviosService->resolveSegmentoForUnit($db, (int)$v['id_unidad']);
                $v['ord_sub'] = 0;
                $v['ord_baj'] = count($nodos) > 0 ? (count($nodos) - 1) : 1;
                if (!empty($v['id_nodo_subida']) && isset($nodosMap[$v['id_nodo_subida']])) {
                    $v['ord_sub'] = (int)$nodosMap[$v['id_nodo_subida']]['orden'];
                }
                if (!empty($v['id_nodo_bajada']) && isset($nodosMap[$v['id_nodo_bajada']])) {
                    $v['ord_baj'] = (int)$nodosMap[$v['id_nodo_bajada']]['orden'];
                } elseif (!empty($v['id_parada']) && isset($nodosMap[$v['id_parada']])) {
                    $v['ord_baj'] = (int)$nodosMap[$v['id_parada']]['orden'];
                }
            }
            unset($v);

            // 4. Obtener tramos de costos
            $stmtTramos = $db->prepare("SELECT * FROM lgs_envios_tramos_costos WHERE id_envio = ? ORDER BY id_tramo_costo ASC");
            $stmtTramos->execute([$idEnvio]);
            $tramos = $stmtTramos->fetchAll(PDO::FETCH_ASSOC);

            $tarifasBasePond = [1 => 27.0, 2 => 30.0, 3 => 40.0, 4 => 50.0, 5 => 60.0];
            $miPeso = $tarifasBasePond[$segId] ?? 27.0;

            $tramosRecorridos = [];
            $kmRecorridosTotal = 0.0;
            $costoTotalCalculado = 0.0;

            $modalidad = strtoupper($detalle['modalidad_calculo'] ?? 'ESCALONADO');
            $nodoBajadaVinId = !empty($currentVin['id_nodo_bajada']) ? (int)$currentVin['id_nodo_bajada'] : (!empty($currentVin['id_parada']) ? (int)$currentVin['id_parada'] : (!empty($nodos) ? (int)$nodos[count($nodos) - 1]['id_nodo'] : 0));

            if (!empty($tramos)) {
                $idx = 1;
                if ($modalidad === 'DIRECTO') {
                    foreach ($tramos as $tr) {
                        if ((int)$tr['id_nodo_destino'] !== $nodoBajadaVinId) {
                            continue;
                        }

                        $nomOrig = 'Origen';
                        $nomDest = 'Destino';
                        if (isset($nodosMap[$tr['id_nodo_origen']])) {
                            $nomOrig = $nodosMap[$tr['id_nodo_origen']]['nombre'];
                        }
                        if (isset($nodosMap[$tr['id_nodo_destino']])) {
                            $nomDest = $nodosMap[$tr['id_nodo_destino']]['nombre'];
                        }

                        $vinsEnTramo = [];
                        $sumaPondTramo = 0.0;
                        foreach ($allVins as $v) {
                            $vBaj = !empty($v['id_nodo_bajada']) ? (int)$v['id_nodo_bajada'] : (!empty($v['id_parada']) ? (int)$v['id_parada'] : (!empty($nodos) ? (int)$nodos[count($nodos) - 1]['id_nodo'] : 0));
                            if ($vBaj === $nodoBajadaVinId) {
                                $vinsEnTramo[] = $v;
                                $seg = (int)($v['id_segmento'] ?? 1);
                                $sumaPondTramo += ($tarifasBasePond[$seg] ?? 27.0);
                            }
                        }
                        $volumenTramo = count($vinsEnTramo);
                        if ($sumaPondTramo <= 0) $sumaPondTramo = (float)$volumenTramo;

                        $costoTramo = (float)$tr['costo_estimado'];
                        if ($idTipoTraslado === 1 || $idTipoTraslado === 3) {
                            $cuotaUnidadTramo = ($volumenTramo > 0) ? ($costoTramo / $volumenTramo) : 0.0;
                        } else {
                            $cuotaUnidadTramo = ($sumaPondTramo > 0) ? ($costoTramo * ($miPeso / $sumaPondTramo)) : 0.0;
                        }
                        $kmTramo = (float)$tr['km_tramo'];

                        $kmRecorridosTotal += $kmTramo;
                        $costoTotalCalculado += $cuotaUnidadTramo;

                        $tramosRecorridos[] = [
                            'num' => $idx++,
                            'origen' => $nomOrig,
                            'destino' => $nomDest,
                            'km' => $kmTramo,
                            'unidades_a_bordo' => $volumenTramo,
                            'factor' => (float)$tr['factor_aplicado'],
                            'costo_tramo' => $costoTramo,
                            'cuota_unidad' => round($cuotaUnidadTramo, 2),
                            'porcentaje' => ($costoTramo > 0) ? round(($cuotaUnidadTramo / $costoTramo) * 100, 1) : 100
                        ];
                    }
                } else {
                    foreach ($tramos as $tr) {
                        $ordOrig = 0;
                        $ordDest = 1;
                        $nomOrig = 'Origen';
                        $nomDest = 'Destino';
                        if (isset($nodosMap[$tr['id_nodo_origen']])) {
                            $ordOrig = (int)$nodosMap[$tr['id_nodo_origen']]['orden'];
                            $nomOrig = $nodosMap[$tr['id_nodo_origen']]['nombre'];
                        }
                        if (isset($nodosMap[$tr['id_nodo_destino']])) {
                            $ordDest = (int)$nodosMap[$tr['id_nodo_destino']]['orden'];
                            $nomDest = $nodosMap[$tr['id_nodo_destino']]['nombre'];
                        }

                        if (!($ordenSubidaVin <= $ordOrig && $ordenBajadaVin >= $ordDest)) {
                            continue;
                        }

                        $vinsEnTramo = [];
                        $sumaPondTramo = 0.0;
                        foreach ($allVins as $v) {
                            if ($v['ord_sub'] <= $ordOrig && $v['ord_baj'] >= $ordDest) {
                                $vinsEnTramo[] = $v;
                                $seg = (int)($v['id_segmento'] ?? 1);
                                $sumaPondTramo += ($tarifasBasePond[$seg] ?? 27.0);
                            }
                        }
                        $volumenTramo = count($vinsEnTramo);
                        if ($sumaPondTramo <= 0) $sumaPondTramo = (float)$volumenTramo;

                        $costoTramo = (float)$tr['costo_estimado'];
                        if ($idTipoTraslado === 1 || $idTipoTraslado === 3) {
                            $cuotaUnidadTramo = ($volumenTramo > 0) ? ($costoTramo / $volumenTramo) : 0.0;
                        } else {
                            $cuotaUnidadTramo = ($sumaPondTramo > 0) ? ($costoTramo * ($miPeso / $sumaPondTramo)) : 0.0;
                        }
                        $kmTramo = (float)$tr['km_tramo'];

                        $kmRecorridosTotal += $kmTramo;
                        $costoTotalCalculado += $cuotaUnidadTramo;

                        $tramosRecorridos[] = [
                            'num' => $idx++,
                            'origen' => $nomOrig,
                            'destino' => $nomDest,
                            'km' => $kmTramo,
                            'unidades_a_bordo' => $volumenTramo,
                            'factor' => (float)$tr['factor_aplicado'],
                            'costo_tramo' => $costoTramo,
                            'cuota_unidad' => round($cuotaUnidadTramo, 2),
                            'porcentaje' => ($costoTramo > 0) ? round(($cuotaUnidadTramo / $costoTramo) * 100, 1) : 100
                        ];
                    }
                }
            }

            // Fallback si no hay tramos detallados
            if (empty($tramosRecorridos)) {
                $kmEnvio = (float)($detalle['km_total'] ?? 0);
                $costoEnvio = (float)($detalle['costo_total'] ?? 0);
                $costoUnidad = (float)($detalle['costo_unidad'] ?? $costoEnvio);
                $volTotal = count($allVins) > 0 ? count($allVins) : 1;
                $kmRecorridosTotal = $kmEnvio;
                $costoTotalCalculado = $costoUnidad;

                $tramosRecorridos[] = [
                    'num' => 1,
                    'origen' => $detalle['subida_nombre'] ?? $detalle['origen_global'] ?? 'Origen',
                    'destino' => $detalle['bajada_nombre'] ?? $detalle['destino_global'] ?? 'Destino',
                    'km' => $kmEnvio,
                    'unidades_a_bordo' => $volTotal,
                    'factor' => (float)($detalle['factor_promedio'] ?? 1.0),
                    'costo_tramo' => $costoEnvio,
                    'cuota_unidad' => round($costoUnidad, 2),
                    'porcentaje' => ($costoEnvio > 0) ? round(($costoUnidad / $costoEnvio) * 100, 1) : 100
                ];
            }

            // Ajustar diferencia de centavos con el costo_unidad guardado
            if ($detalle['costo_total_unitario'] > 0 && !empty($tramosRecorridos)) {
                $dif = round($detalle['costo_total_unitario'] - array_sum(array_column($tramosRecorridos, 'cuota_unidad')), 2);
                if (abs($dif) > 0.001) {
                    $lastIdx = count($tramosRecorridos) - 1;
                    $tramosRecorridos[$lastIdx]['cuota_unidad'] = round($tramosRecorridos[$lastIdx]['cuota_unidad'] + $dif, 2);
                }
            }

            // Limpiar cualquier búfer de salida antes de renderizar PDF
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            require_once("Libraries/html2pdf/vendor/autoload.php");

            ob_start();
            $viewPdf = file_exists("Views/Lgs_evidencias/hoja_entrega_pdf.php")
                ? "Views/Lgs_evidencias/hoja_entrega_pdf.php"
                : "Views/Lgs_envios/hoja_entrega_pdf.php";
            include $viewPdf;
            $html = ob_get_clean();

            $pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'A4', 'es', true, 'UTF-8', [10, 10, 10, 10]);
            $pdf->pdf->SetDisplayMode('fullpage');
            $pdf->writeHTML($html);
            $pdf->output("Hoja_Entrega_" . ($detalle['vin'] ?? 'Unidad') . ".pdf", 'I');
            exit;

        } catch (Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            echo "<div style='font-family: Arial, sans-serif; padding: 40px; color: #b91c1c;'>";
            echo "<h2>Error al generar PDF de Hoja de Entrega</h2>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
            exit;
        }
    }
}
