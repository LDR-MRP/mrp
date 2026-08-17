<?php

class Lgs_costos extends Controllers
{
    use ApiResponser;

    private Lgs_costosService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();
        
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        // Aseguramos permisos de lectura/escritura/edición/eliminación para costos de logística
        $_SESSION['permisosMod'] = [
            'r' => 1,
            'w' => 1,
            'u' => 1,
            'd' => 1
        ];

        $this->service = new Lgs_costosService();
    }

    /**
     * Renderiza la vista principal del Administrador de Costos
     * URL: {{base_url}}/Lgs_costos
     */
    public function Lgs_costos(): void
    {
        $catalogs = $this->service->getFormCatalogs();
        
        $this->views->getView(
            $this,
            "../Lgs_costos/index",
            [
                'page_tag'          => "Admin de Costos",
                'page_title'        => "Administrador de Costos Logísticos",
                'page_name'         => "lgs_costos",
                'page_functions_js' => "functions_lgs_costos.js",
                'catalogs'          => $catalogs
            ]
        );
    }

    /**
     * API: Obtiene las rutas agrupadas con sus métricas y segmentos configurados
     */
    public function getRutas(): void
    {
        try {
            $data = $this->service->listRutasAgrupadas();
            for ($i = 0; $i < count($data); $i++) {
                $row = $data[$i];
                
                // Badge Tipo Traslado
                if ($row['id_tipo_traslado'] == 1) {
                    $data[$i]['tipo_traslado_html'] = '<span class="badge bg-primary-subtle text-primary fs-12 px-2 py-1"><i class="ri-truck-line me-1"></i>Madrina</span>';
                } else {
                    $data[$i]['tipo_traslado_html'] = '<span class="badge bg-warning-subtle text-warning fs-12 px-2 py-1"><i class="ri-steering-2-line me-1"></i>Chofer (Rodando)</span>';
                }

                // Ruta Visual
                $data[$i]['ruta_html'] = '
                    <div class="d-flex align-items-center">
                        <span class="fw-semibold text-body">' . htmlspecialchars($row['origen']) . '</span>
                        <i class="ri-arrow-right-line text-muted mx-2 fs-16"></i>
                        <span class="fw-bold text-primary">' . htmlspecialchars($row['destino']) . '</span>
                    </div>';

                // Distancia
                $data[$i]['km_html'] = '<span class="fw-medium text-dark"><i class="ri-dashboard-3-line text-muted me-1"></i>' . number_format($row['km'], 2) . ' KM</span>';

                // Badges de Segmentos configurados
                $segmentosTags = '';
                if (!empty($row['segmentos_resumen'])) {
                    $arrSegs = explode(' | ', $row['segmentos_resumen']);
                    foreach ($arrSegs as $s) {
                        $parts = explode(':', $s);
                        $segName = $parts[0] ?? '';
                        $segCost = isset($parts[1]) ? '$' . number_format((float)$parts[1], 2) : '';
                        $segmentosTags .= '<span class="badge bg-light text-dark border me-1 mb-1 fs-11">' . htmlspecialchars($segName) . ': <b class="text-success">' . $segCost . '</b></span>';
                    }
                } else {
                    $segmentosTags = '<span class="badge bg-danger-subtle text-danger">Sin Tarifas</span>';
                }
                $data[$i]['segmentos_html'] = '<div class="d-flex flex-wrap">' . $segmentosTags . '</div>';

                // Botones de acción
                $btnEdit = '<button class="btn btn-sm btn-primary shadow-sm me-1" title="Gestionar Tarifas y Factores" onClick="fntOpenMatrizModal(' . $row['id_tipo_traslado'] . ', ' . $row['id_origen'] . ', ' . $row['id_destino'] . ', \'' . addslashes($row['origen']) . '\', \'' . addslashes($row['destino']) . '\', \'' . addslashes($row['tipo_traslado']) . '\')"><i class="ri-settings-4-line align-middle me-1"></i> Tarifas</button>';
                $btnDelete = '<button class="btn btn-sm btn-soft-danger" title="Eliminar Ruta Completa" onClick="fntDeleteRuta(' . $row['id_tipo_traslado'] . ', ' . $row['id_origen'] . ', ' . $row['id_destino'] . ', \'' . addslashes($row['origen'] . ' ➔ ' . $row['destino']) . '\')"><i class="ri-delete-bin-fill align-middle"></i></button>';
                
                $data[$i]['options'] = '<div class="text-center">' . $btnEdit . $btnDelete . '</div>';
            }
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    /**
     * API: Obtiene la matriz completa de tarifas para una ruta
     */
    public function getRutaMatriz(): void
    {
        try {
            $idTipoTraslado = intval($_GET['id_tipo_traslado'] ?? 0);
            $idOrigen = intval($_GET['id_origen'] ?? 0);
            $idDestino = intval($_GET['id_destino'] ?? 0);

            $data = $this->service->getRutaMatriz($idTipoTraslado, $idOrigen, $idDestino);
            echo $this->successResponse($data, "Matriz de tarifas obtenida con éxito.");
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 400);
        }
        die();
    }

    /**
     * API: Obtiene las tarifas de ambas modalidades (Madrina y Chofer) para un mismo trayecto
     */
    public function getRutaDual(): void
    {
        try {
            $idOrigen = intval($_GET['id_origen'] ?? 0);
            $idDestino = intval($_GET['id_destino'] ?? 0);

            if ($idOrigen <= 0 || $idDestino <= 0) {
                echo $this->errorResponse("Parámetros de trayecto inválidos.", 400);
                die();
            }

            $model = new Lgs_costosModel();
            $data = $model->selectRutaDual($idOrigen, $idDestino);
            echo $this->successResponse($data, "Tarifas de trayecto obtenidas con éxito.");
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    /**
     * API: Guarda simultáneamente o individualmente las tarifas de Madrina y Chofer para un trayecto
     */
    public function saveRutaDual(): void
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if (empty($data)) {
                $data = $_POST;
            }

            $idOrigen = intval($data['id_origen'] ?? 0);
            $idDestino = intval($data['id_destino'] ?? 0);
            $km = floatval($data['km'] ?? 0);
            $madrinaSegs = $data['madrina_segmentos'] ?? [];
            $choferSegs = $data['chofer_segmentos'] ?? [];

            if ($idOrigen <= 0 || $idDestino <= 0) {
                echo $this->errorResponse("Debe especificar el origen y destino.", 400);
                die();
            }

            $model = new Lgs_costosModel();
            if (!empty($madrinaSegs)) {
                $model->saveRutaMatriz(1, $idOrigen, $idDestino, $km, $madrinaSegs);
            }
            if (!empty($choferSegs)) {
                $model->saveRutaMatriz(2, $idOrigen, $idDestino, $km, $choferSegs);
            }

            echo $this->successResponse(null, "Tarifas del trayecto (Madrina y Chofer) guardadas con éxito.");
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    /**
     * API: Guarda la matriz completa de tarifas de una ruta
     */
    public function saveRutaMatriz(): void
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (empty($data)) {
                $data = $_POST;
            }

            if (empty($data)) {
                echo $this->errorResponse("No se recibieron datos de la matriz.", 400);
                die();
            }

            $success = $this->service->saveRutaMatriz($data);
            if ($success) {
                echo $this->successResponse(null, "La matriz de tarifas para la ruta se ha guardado exitosamente.");
            } else {
                echo $this->errorResponse("No se pudo guardar la matriz de tarifas.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 400);
        }
        die();
    }

    /**
     * API: Elimina una ruta y todas sus tarifas
     */
    public function delRuta(): void
    {
        try {
            if (empty($_POST['id_tipo_traslado']) || empty($_POST['id_origen']) || empty($_POST['id_destino'])) {
                echo $this->errorResponse("Parámetros de ruta incompletos.", 400);
                die();
            }

            $idTipoTraslado = intval($_POST['id_tipo_traslado']);
            $idOrigen = intval($_POST['id_origen']);
            $idDestino = intval($_POST['id_destino']);

            $success = $this->service->deleteRuta($idTipoTraslado, $idOrigen, $idDestino);
            if ($success) {
                echo $this->successResponse(null, "Ruta y sus tarifas eliminadas con éxito.");
            } else {
                echo $this->errorResponse("No se pudo eliminar la ruta.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 400);
        }
        die();
    }

    /**
     * API: Mapeo de Modelos de VIN
     */
    public function getModelosVin(): void
    {
        try {
            $data = $this->service->listModelosVin();
            for ($i = 0; $i < count($data); $i++) {
                $segmento = $data[$i]['segmento'] ?? '<span class="badge bg-danger-subtle text-danger">Sin Asignar</span>';
                $data[$i]['segmento_html'] = $segmento;

                $btnLink = '<button class="btn btn-sm btn-soft-primary" title="Asignar Segmento" onClick="fntAsignarSegmento(' . $data[$i]['id_cat_modelo_vin'] . ', \'' . addslashes($data[$i]['modelo']) . '\')"><i class="ri-link"></i> Asignar</button>';
                $data[$i]['options'] = '<div class="text-center">' . $btnLink . '</div>';
            }
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    /**
     * API: Asigna un segmento a un modelo VIN
     */
    public function setSegmentoModelo(): void
    {
        try {
            if (empty($_POST['id_modelo_vin'])) {
                echo $this->errorResponse("ID de modelo requerido.", 400);
                die();
            }
            $idModelo = intval($_POST['id_modelo_vin']);
            $idSegmento = !empty($_POST['id_segmento']) ? intval($_POST['id_segmento']) : null;

            $success = $this->service->setSegmentoModelo($idModelo, $idSegmento);
            if ($success) {
                echo $this->successResponse(null, "Segmento asignado correctamente al modelo.");
            } else {
                echo $this->errorResponse("No se pudo realizar la asignación.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 400);
        }
        die();
    }

    /**
     * API: Importación de tarifas mediante CSV (Rodando o Madrina)
     */
    public function importTarifas(): void
    {
        try {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                echo $this->errorResponse("Debe seleccionar un archivo CSV válido.", 400);
                die();
            }

            $forcedTipo = !empty($_POST['id_tipo_traslado']) && is_numeric($_POST['id_tipo_traslado']) ? intval($_POST['id_tipo_traslado']) : null;
            $tempPath = $_FILES['csv_file']['tmp_name'];
            $result = $this->service->importCSV($tempPath, $forcedTipo);

            echo $this->successResponse($result, "Importación finalizada con éxito.");
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 400);
        }
        die();
    }

    /**
     * Descarga el tarifario exportado según la modalidad solicitada (Rodando, Madrina o Consolidado)
     */
    public function descargarPlantillaCSV(): void
    {
        $tipo = $_GET['tipo'] ?? 'all';
        $model = new Lgs_costosModel();

        // 1. TARIFARIO RODANDO (CHOFER) -> 1 Sola Unidad
        if ($tipo === '2') {
            $filename = "tarifario_rodando_chofer_" . date("Ymd_His") . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($output, ['ORIGEN: LAGOS DE MORENO - TARIFARIO RODANDO (CHOFER)']);
            fputcsv($output, ['MODALIDAD: CHOFER (RODANDO)']);
            fputcsv($output, ['DESTINO', 'KM', 'LIGEROS', 'MEDIANO', 'PESADO', 'BUSES', 'LOWBOY']);
            
            $matriz = $model->selectExportMatriz(2);
            foreach ($matriz as $row) {
                fputcsv($output, [
                    $row['destino'],
                    number_format((float)$row['km'], 2, '.', ''),
                    number_format((float)($row['ligeros'] ?? 0), 2, '.', ''),
                    number_format((float)($row['mediano'] ?? 0), 2, '.', ''),
                    number_format((float)($row['pesado'] ?? 0), 2, '.', ''),
                    number_format((float)($row['buses'] ?? 0), 2, '.', ''),
                    number_format((float)($row['lowboy'] ?? 0), 2, '.', '')
                ]);
            }
            fclose($output);
            exit;
        }

        // 2. TARIFARIO MADRINA -> Con desglose de Factores 1 al 15
        if ($tipo === '1') {
            $filename = "tarifario_madrinas_factores_" . date("Ymd_His") . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($output, ['ORIGEN: LAGOS DE MORENO - TARIFARIO MADRINA 2025']);
            fputcsv($output, ['MODALIDAD: MADRINA']);
            fputcsv($output, ['DESTINO', 'KM', 'LIGEROS', 'MEDIANO', 'PESADO', 'BUSES', 'LOWBOY']);
            
            $matrizMadrina = $model->selectExportMatriz(1);
            foreach ($matrizMadrina as $row) {
                fputcsv($output, [
                    $row['destino'],
                    number_format((float)$row['km'], 2, '.', ''),
                    number_format((float)($row['ligeros'] ?? 0), 2, '.', ''),
                    number_format((float)($row['mediano'] ?? 0), 2, '.', ''),
                    number_format((float)($row['pesado'] ?? 0), 2, '.', ''),
                    number_format((float)($row['buses'] ?? 0), 2, '.', ''),
                    number_format((float)($row['lowboy'] ?? 0), 2, '.', '')
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['# =========================================================================']);
            fputcsv($output, ['# DESGLOSE COMPLETO POR CAPACIDAD EN MADRINA (FACTOR 1 AL 15)']);
            fputcsv($output, ['# =========================================================================']);
            fputcsv($output, [
                'ORIGEN', 'DESTINO', 'KM', 'SEGMENTO', 
                'FACTOR_1', 'FACTOR_2', 'FACTOR_3', 'FACTOR_4', 'FACTOR_5',
                'FACTOR_6', 'FACTOR_7', 'FACTOR_8', 'FACTOR_9', 'FACTOR_10',
                'FACTOR_11', 'FACTOR_12', 'FACTOR_13', 'FACTOR_14', 'FACTOR_15'
            ]);
            
            $factoresMadrina = $model->selectExportMadrinaFactores();
            if (empty($factoresMadrina)) {
                // Si aún no hay tarifas de madrina en BD, generar plantilla con los destinos registrados
                $destinos = $model->selectDestinos();
                $segmentos = $model->selectSegmentos();
                foreach ($destinos as $d) {
                    foreach ($segmentos as $s) {
                        $rowSample = ['LAGOS DE MORENO', $d['nombre'], '100.00', $s['nombre']];
                        for ($u = 1; $u <= 15; $u++) {
                            $rowSample[] = number_format(1800.00 * (1.0 - ($u - 1) * 0.02), 2, '.', '');
                        }
                        fputcsv($output, $rowSample);
                    }
                }
            } else {
                foreach ($factoresMadrina as $f) {
                    fputcsv($output, [
                        $f['origen'],
                        $f['destino'],
                        number_format((float)$f['km'], 2, '.', ''),
                        $f['segmento'],
                        number_format((float)($f['factor_1'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_2'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_3'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_4'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_5'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_6'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_7'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_8'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_9'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_10'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_11'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_12'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_13'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_14'] ?? 0), 2, '.', ''),
                        number_format((float)($f['factor_15'] ?? 0), 2, '.', '')
                    ]);
                }
            }
            fclose($output);
            exit;
        }

        // 3. CONSOLIDADO GENERAL (TODOS)
        $filename = "tarifario_rutas_completo_" . date("Ymd_His") . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        $matriz = $model->selectExportMatriz();
        
        fputcsv($output, ['# =========================================================================']);
        fputcsv($output, ['# TARIFARIO GENERAL DE RUTAS LOGISTICAS (RESUMEN MATRICIAL) - ' . date('d/m/Y H:i')]);
        fputcsv($output, ['# =========================================================================']);
        fputcsv($output, ['MODALIDAD', 'ORIGEN', 'DESTINO', 'KM', 'LIGEROS', 'MEDIANO', 'PESADO', 'BUSES', 'LOWBOY']);
        
        foreach ($matriz as $row) {
            fputcsv($output, [
                $row['tipo_traslado'],
                $row['origen'],
                $row['destino'],
                number_format((float)$row['km'], 2, '.', ''),
                number_format((float)($row['ligeros'] ?? 0), 2, '.', ''),
                number_format((float)($row['mediano'] ?? 0), 2, '.', ''),
                number_format((float)($row['pesado'] ?? 0), 2, '.', ''),
                number_format((float)($row['buses'] ?? 0), 2, '.', ''),
                number_format((float)($row['lowboy'] ?? 0), 2, '.', '')
            ]);
        }
        
        fputcsv($output, []);
        fputcsv($output, ['# =========================================================================']);
        fputcsv($output, ['# DESGLOSE DETALLADO POR FACTORES DE VOLUMEN (1 A 15 UNIDADES)']);
        fputcsv($output, ['# =========================================================================']);
        fputcsv($output, ['MODALIDAD', 'ORIGEN', 'DESTINO', 'KM', 'SEGMENTO', 'COSTO_POR_KM', 'PRECIO_PLANO', 'UNIDADES_MIN', 'UNIDADES_MAX', 'FACTOR_MULTIPLICADOR', 'PRECIO_UNITARIO_VIN', 'TOTAL_FLETE']);
        
        $detallado = $model->selectExportData();
        foreach ($detallado as $det) {
            fputcsv($output, [
                $det['tipo_traslado'],
                $det['origen'],
                $det['destino'],
                number_format((float)$det['km'], 2, '.', ''),
                $det['segmento'],
                number_format((float)$det['costo_por_km'], 4, '.', ''),
                number_format((float)$det['precio_plano'], 2, '.', ''),
                $det['num_vins_min'],
                $det['num_vins_max'],
                number_format((float)$det['factor'], 4, '.', ''),
                number_format((float)$det['precio_unitario'], 2, '.', ''),
                number_format((float)$det['flete_total'], 2, '.', '')
            ]);
        }

        fclose($output);
        exit;
    }
}
