<?php

class Lgs_costosService
{
    private Lgs_costosModel $model;

    public function __construct()
    {
        $this->model = new Lgs_costosModel();
    }

    /**
     * Obtiene todos los selectores de catálogos necesarios para los formularios
     */
    public function getFormCatalogs(): array
    {
        return [
            'tipos_traslado' => $this->model->selectTiposTraslado(),
            'origenes'       => $this->model->selectOrigenes(),
            'destinos'       => $this->model->selectDestinos(),
            'segmentos'      => $this->model->selectSegmentos(),
            'proveedores'    => $this->model->selectProveedores(),
            'kpis'           => $this->model->getKpis()
        ];
    }

    /**
     * Obtiene el listado de rutas agrupadas para la tabla principal
     */
    public function listRutasAgrupadas(): array
    {
        return $this->model->selectRutasAgrupadas();
    }

    /**
     * Obtiene la matriz completa de tarifas para una ruta
     */
    public function getRutaMatriz(int $idTipoTraslado, int $idOrigen, int $idDestino): array
    {
        if ($idTipoTraslado <= 0 || $idOrigen <= 0 || $idDestino <= 0) {
            throw new Exception("Parámetros de ruta inválidos.", 400);
        }
        return $this->model->selectRutaMatriz($idTipoTraslado, $idOrigen, $idDestino);
    }

    /**
     * Guarda la matriz completa de tarifas de una ruta
     */
    public function saveRutaMatriz(array $data): bool
    {
        $idTipoTraslado = intval($data['id_tipo_traslado'] ?? 0);
        $idOrigen = intval($data['id_origen'] ?? 0);
        $idDestino = intval($data['id_destino'] ?? 0);
        $km = floatval($data['km'] ?? 0);

        if ($idTipoTraslado <= 0 || $idOrigen <= 0 || $idDestino <= 0) {
            throw new Exception("El tipo de traslado, origen y destino son obligatorios.", 400);
        }

        if ($km <= 0) {
            throw new Exception("Debe ingresar la distancia en kilómetros (KM) de la ruta.", 400);
        }

        $segmentos = $data['segmentos'] ?? [];
        if (empty($segmentos) || !is_array($segmentos)) {
            throw new Exception("Debe incluir la configuración de al menos un segmento.", 400);
        }

        $idProveedor = !empty($data['id_proveedor']) ? intval($data['id_proveedor']) : null;

        return $this->model->saveRutaMatriz($idTipoTraslado, $idOrigen, $idDestino, $km, $segmentos, $idProveedor);
    }

    /**
     * Elimina todas las tarifas asociadas a una ruta
     */
    public function deleteRuta(int $idTipoTraslado, int $idOrigen, int $idDestino): bool
    {
        if ($idTipoTraslado <= 0 || $idOrigen <= 0 || $idDestino <= 0) {
            throw new Exception("Parámetros de ruta inválidos para eliminación.", 400);
        }
        return $this->model->deleteRuta($idTipoTraslado, $idOrigen, $idDestino);
    }

    /**
     * Obtiene el mapeo de modelos de VIN y sus segmentos
     */
    public function listModelosVin(): array
    {
        return $this->model->selectModelosVin();
    }

    /**
     * Asigna un segmento a un modelo de VIN
     */
    public function setSegmentoModelo(int $idModelo, ?int $idSegmento): bool
    {
        if ($idModelo <= 0) {
            throw new Exception("ID de modelo de VIN inválido.", 400);
        }
        return $this->model->updateModeloSegmento($idModelo, $idSegmento);
    }

    /**
     * Procesa e importa un archivo CSV de tarifas
     */
    /**
     * Procesa e importa un archivo CSV de tarifas (Rodando o Madrina)
     */
    public function importCSV(string $filePath, ?int $forcedTipoTraslado = null): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("El archivo a importar no existe en el servidor.", 404);
        }

        $content = file_get_contents($filePath);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        $lines = explode("\n", str_replace("\r", "", $content));
        
        $originName = 'Lagos de Moreno';
        $tipoTrasladoId = ($forcedTipoTraslado !== null && $forcedTipoTraslado > 0) ? $forcedTipoTraslado : 2;
        
        foreach ($lines as $line) {
            if (stripos($line, 'ORIGEN:') !== false) {
                $parts = explode(':', $line);
                if (isset($parts[1])) {
                    $originName = trim(explode(',', $parts[1])[0]);
                }
            }
            if ($forcedTipoTraslado === null || $forcedTipoTraslado <= 0) {
                if (stripos($line, 'RODANDO') !== false || stripos($line, 'CHOFER') !== false) {
                    $tipoTrasladoId = 2;
                } else if (stripos($line, 'MADRINA') !== false) {
                    $tipoTrasladoId = 1;
                }
            }
        }

        $db = $this->model->getConexion();
        $stmtOrig = $db->prepare("SELECT id_origen FROM lgs_cat_origenes WHERE LOWER(nombre) = ? AND activo = 1 LIMIT 1");
        $stmtOrig->execute([strtolower($originName)]);
        $origRow = $stmtOrig->fetch(PDO::FETCH_ASSOC);
        if ($origRow) {
            $idOrigen = intval($origRow['id_origen']);
        } else {
            $stmtInsOrig = $db->prepare("INSERT INTO lgs_cat_origenes (nombre, activo) VALUES (?, 1)");
            $stmtInsOrig->execute([$originName]);
            $idOrigen = intval($db->lastInsertId());
        }

        $segmentos = [];
        $segRows = $this->model->selectSegmentos();
        foreach ($segRows as $sr) {
            $segmentos[strtoupper($sr['nombre'])] = intval($sr['id_segmento']);
        }

        $importedCount = 0;
        $errors = [];

        $headerFound = false;
        foreach ($lines as $lineIdx => $line) {
            $data = str_getcsv($line, ',');
            if (empty($data) || count($data) < 2) continue;

            if (strtoupper(trim($data[0])) === 'DESTINO') {
                $headerFound = true;
                continue;
            }

            if (!$headerFound) continue;
            
            $destinoNombre = trim($data[0]);
            if (empty($destinoNombre) || stripos($destinoNombre, 'ORIGEN:') !== false || stripos($destinoNombre, 'TARIFARIO') !== false) {
                continue;
            }

            $km = floatval($data[1] ?? 0.0);
            if ($km <= 0) continue;

            $stmtDest = $db->prepare("SELECT id_destino FROM lgs_cat_destinos WHERE LOWER(nombre) = ? AND activo = 1 LIMIT 1");
            $stmtDest->execute([strtolower($destinoNombre)]);
            $destRow = $stmtDest->fetch(PDO::FETCH_ASSOC);
            if ($destRow) {
                $idDestino = intval($destRow['id_destino']);
            } else {
                $stmtInsDest = $db->prepare("INSERT INTO lgs_cat_destinos (nombre, activo) VALUES (?, 1)");
                $stmtInsDest->execute([$destinoNombre]);
                $idDestino = intval($db->lastInsertId());
            }

            $tarifasMapear = [
                'LIGEROS' => ['col' => 2, 'costo' => 18.00],
                'MEDIANO' => ['col' => 3, 'costo' => 20.00],
                'PESADO'  => ['col' => 4, 'costo' => 25.00],
                'BUSES'   => ['col' => 8, 'costo' => 28.00],
                'LOWBOY'  => ['col' => 9, 'costo' => 80.00]
            ];

            $numVinsMin = 1;
            $numVinsMax = ($tipoTrasladoId === 2) ? 1 : 15;

            foreach ($tarifasMapear as $segName => $cfg) {
                if (!isset($segmentos[$segName])) continue;
                $idSegmento = $segmentos[$segName];
                
                $valRaw = isset($data[$cfg['col']]) ? trim($data[$cfg['col']]) : '';
                if ($valRaw === '') continue;

                $valClean = preg_replace('/[^\d\.]/', '', $valRaw);
                $costoTotal = floatval($valClean);
                
                $costoPorKm = $cfg['costo'];
                if ($costoTotal > 0 && $km > 0) {
                    $costoPorKm = round($costoTotal / $km, 4);
                }

                try {
                    // Si es Chofer (Rodando) -> 1 sola unidad fija
                    if ($tipoTrasladoId === 2) {
                        $stmtDel = $db->prepare("DELETE FROM lgs_costos_rutas 
                                                 WHERE id_tipo_traslado = ? AND id_origen = ? AND id_destino = ? AND id_segmento = ?");
                        $stmtDel->execute([$tipoTrasladoId, $idOrigen, $idDestino, $idSegmento]);

                        $stmtIns = $db->prepare("INSERT INTO lgs_costos_rutas (
                                                    id_tipo_traslado, id_origen, id_destino, id_segmento, 
                                                    num_vins_min, num_vins_max, km, costo_por_km, precio_plano, factor, activo
                                                 ) VALUES (?, ?, ?, ?, 1, 1, ?, ?, 0.00, 1.00, 2)");
                        $stmtIns->execute([$tipoTrasladoId, $idOrigen, $idDestino, $idSegmento, $km, $costoPorKm]);
                        $importedCount++;
                    } 
                    // Si es Madrina -> Generar los 15 factores por capacidad de volumen
                    else {
                        $stmtDel = $db->prepare("DELETE FROM lgs_costos_rutas 
                                                 WHERE id_tipo_traslado = ? AND id_origen = ? AND id_destino = ? AND id_segmento = ?");
                        $stmtDel->execute([$tipoTrasladoId, $idOrigen, $idDestino, $idSegmento]);

                        $stmtIns = $db->prepare("INSERT INTO lgs_costos_rutas (
                                                    id_tipo_traslado, id_origen, id_destino, id_segmento, 
                                                    num_vins_min, num_vins_max, km, costo_por_km, precio_plano, factor, activo
                                                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, 2)");

                        for ($u = 1; $u <= 15; $u++) {
                            $factorVal = round(max(0.20, 1.00 - (($u - 1) * 0.02)), 4);
                            $stmtIns->execute([
                                $tipoTrasladoId, $idOrigen, $idDestino, $idSegmento,
                                $u, $u, $km, $costoPorKm, $factorVal
                            ]);
                        }
                        $importedCount++;
                    }
                } catch (Throwable $e) {
                    $errors[] = "Error fila {$lineIdx} segment {$segName}: " . $e->getMessage();
                }
            }
        }

        return [
            'success' => true,
            'imported_records' => $importedCount,
            'origin' => $originName,
            'modalidad' => ($tipoTrasladoId === 1 ? 'Madrina' : 'Chofer (Rodando)'),
            'errors' => $errors
        ];
    }
}
