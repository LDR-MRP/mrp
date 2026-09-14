<?php

class Lgs_enviosService {

    private Lgs_enviosModel $model;

    public function __construct() {
        $this->model = new Lgs_enviosModel();
    }

    /**
     * Obtiene todos los envíos para la vista principal
     */
    public function getAllEnvios(): array {
        return $this->model->getEnviosDataTable();
    }

    public function getCatalogosSelect(): array {
        return $this->model->getSelectCatalogos();
    }

    /**
     * Crea la cabecera de un envío nuevo (Transaction con bloqueo)
     */
    public function createEnvio(array $data, int $userId): int {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            
            // 1. Bloquea la tabla y genera el folio (EN-000001)
            $folio = $this->model->generarFolioTransaccional($db);
            $data['folio'] = $folio;
            $data['created_by'] = $userId;
            
            // 2. Inserta la cabecera
            $idEnvio = $this->model->insertEnvio($db, $data);
            
            $db->commit();
            return $idEnvio;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Asigna un VIN a un envío con soporte para múltiples paradas
     */
    public function asignarVin(int $idEnvio, int $idUnidad, array $params, int $userId): bool {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            
            $params['id_envio'] = $idEnvio;
            $params['id_unidad'] = $idUnidad;
            $params['created_by'] = $userId; // Opcional, si aplicara

            // 1. Insertar el VIN en la pivot
            $this->model->insertVin($db, $params);
            
            // 2. Aquí iría la llamada a recalcularCostoTotal($idEnvio)
            // $this->recalcularCostoTotal($idEnvio, $db);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function recalcularCostoTotal(int $idEnvio, PDO $db = null): float {
        if ($db === null) {
            $db = $this->model->getConexion();
        }

        // 1. Cabecera del envío
        $stmtEnvio = $db->prepare("SELECT id_tipo_traslado, id_proveedor, id_origen, id_destino, km_total, id_estado, costo_total FROM lgs_envios WHERE id_envio = :id");
        $stmtEnvio->execute(['id' => $idEnvio]);
        $envio = $stmtEnvio->fetch(PDO::FETCH_ASSOC);

        if (!$envio) return 0.0;

        // Regla histórica: no recalcular ni alterar envíos cerrados/entregados (id_estado = 7)
        if (isset($envio['id_estado']) && (int)$envio['id_estado'] === 7) {
            return (float)($envio['costo_total'] ?? 0.0);
        }

        $idTipoTraslado = (int)$envio['id_tipo_traslado'];
        $idProveedor    = (int)$envio['id_proveedor'];

        // 2. Obtener los nodos ordenados
        $stmtNodos = $db->prepare("SELECT id_nodo, orden, id_ubicacion, destino_nombre_libre, km_tramo_anterior FROM lgs_envios_nodos WHERE id_envio = ? ORDER BY orden ASC");
        $stmtNodos->execute([$idEnvio]);
        $nodos = $stmtNodos->fetchAll(PDO::FETCH_ASSOC);

        if (empty($nodos) || count($nodos) < 2) return 0.0; // Mínimo origen y 1 destino

        // Mapear nombre de ubicaciones
        $ubicaciones = [];
        $stmtUbi = $db->query("SELECT id_ubicacion, nombre FROM lgs_cat_ubicaciones");
        while ($row = $stmtUbi->fetch(PDO::FETCH_ASSOC)) {
            $ubicaciones[$row['id_ubicacion']] = $row['nombre'];
        }

        // 3. Obtener VINs
        $stmtVins = $db->prepare("SELECT id, id_unidad, id_madrina, id_chofer, id_nodo_subida, id_nodo_bajada FROM lgs_envios_vins WHERE id_envio = ?");
        $stmtVins->execute([$idEnvio]);
        $vins = $stmtVins->fetchAll(PDO::FETCH_ASSOC);

        if (empty($vins)) return 0.0;

        foreach ($vins as &$vin) {
            $vin['id_segmento'] = $this->resolveSegmentoForUnit($db, (int)$vin['id_unidad']);
        }
        unset($vin);

        // Agrupar por Madrina (si es madrina) o Chofer (si es rodando)
        $unidadesAsignadas = []; // id_madrina_o_chofer => array de vins
        foreach ($vins as $vin) {
            $key = ($idTipoTraslado === 1) ? ((int)$vin['id_madrina']) : ((int)$vin['id_chofer']);
            $unidadesAsignadas[$key][] = $vin;
        }

        // Limpiar tabla de tramos_costos para este envío
        $db->prepare("DELETE FROM lgs_envios_tramos_costos WHERE id_envio = ?")->execute([$idEnvio]);

        $costoTotalEnvio = 0.0;

        // Recorrer cada madrina/chofer
        foreach ($unidadesAsignadas as $idAgrupador => $vinsGrupo) {
            
            // Recorrer los tramos (nodos consecutivos)
            for ($i = 1; $i < count($nodos); $i++) {
                $nodoOrigen = $nodos[$i-1];
                $nodoDestino = $nodos[$i];

                $idLocOrigen = (int)$nodoOrigen['id_ubicacion'];
                $idLocDestino = (int)$nodoDestino['id_ubicacion'];
                $kmTramo = (float)$nodoDestino['km_tramo_anterior'];

                // 1. Resolver distancia desde la memoria progresiva (lgs_distancias)
                if ($idLocOrigen > 0 && $idLocDestino > 0) {
                    $distanciaMemoria = $this->model->getDistanciaEntre($idLocOrigen, $idLocDestino, $db);
                    if ($distanciaMemoria !== null && $distanciaMemoria > 0) {
                        $kmTramo = $distanciaMemoria;
                    } elseif ($kmTramo > 0) {
                        // Si ya tenía km_tramo_anterior pero no en memoria, aprenderlo
                        $this->model->saveDistancia($idLocOrigen, $idLocDestino, $kmTramo, $db);
                    }
                }

                // Guardar el snapshot de distancia en el nodo de destino si cambió
                if ($kmTramo > 0 && (float)$nodoDestino['km_tramo_anterior'] != $kmTramo) {
                    $db->prepare("UPDATE lgs_envios_nodos SET km_tramo_anterior = ? WHERE id_nodo = ?")
                       ->execute([$kmTramo, $nodoDestino['id_nodo']]);
                }

                // Determinar qué VINs van en la madrina durante este tramo
                // Un VIN va en el tramo si su nodo de subida es <= nodoOrigen.orden 
                // y su nodo de bajada es >= nodoDestino.orden
                $vinsEnTramo = [];
                $vinsLigeros = 0;
                $vinsMedianos = 0;
                $vinsPesados = 0;
                $vinsBuses = 0;
                $vinsEspeciales = 0; // Lowboy
                
                foreach ($vinsGrupo as $vin) {
                    $ordenSubida = null;
                    $ordenBajada = null;

                    foreach ($nodos as $n) {
                        if (!empty($vin['id_nodo_subida']) && $n['id_nodo'] == $vin['id_nodo_subida']) {
                            $ordenSubida = (int)$n['orden'];
                        }
                        if (!empty($vin['id_nodo_bajada']) && $n['id_nodo'] == $vin['id_nodo_bajada']) {
                            $ordenBajada = (int)$n['orden'];
                        }
                    }

                    // Defaults si no están explícitamente fijados
                    if ($ordenSubida === null) {
                        $ordenSubida = 0; // Sube al inicio
                    }
                    if ($ordenBajada === null) {
                        if (!empty($vin['id_parada'])) {
                            foreach ($nodos as $n) {
                                if ($n['id_nodo'] == $vin['id_parada']) {
                                    $ordenBajada = (int)$n['orden'];
                                    break;
                                }
                            }
                        }
                        if ($ordenBajada === null) {
                            $ordenBajada = count($nodos) - 1; // Baja al final
                        }
                    }

                    if ($ordenSubida <= (int)$nodoOrigen['orden'] && $ordenBajada >= (int)$nodoDestino['orden']) {
                        $vinsEnTramo[] = $vin;
                        $seg = $vin['id_segmento'];
                        if ($seg == 1) $vinsLigeros++;
                        elseif ($seg == 2) $vinsMedianos++;
                        elseif ($seg == 3) $vinsPesados++;
                        elseif ($seg == 4) $vinsBuses++;
                        elseif ($seg == 5) $vinsEspeciales++;
                    }
                }

                $volumenTotal = count($vinsEnTramo);
                if ($volumenTotal === 0) continue; // Madrina vacía en este tramo

                // Determinar el segmento dominante (el más pesado)
                $segmentoDominante = 1;
                if ($vinsEspeciales > 0) $segmentoDominante = 5;
                elseif ($vinsBuses > 0) $segmentoDominante = 4;
                elseif ($vinsPesados > 0) $segmentoDominante = 3;
                elseif ($vinsMedianos > 0) $segmentoDominante = 2;

                // 2. Intentar tarifa de ruta estricta
                $tarifa = $this->getTarifaRutaEstricta($db, $idTipoTraslado, $idLocOrigen, $idLocDestino, $segmentoDominante, $volumenTotal, $idProveedor);

                // Si no hay tarifa estricta, aplicar costeo base ($/km proveedor/segmento * factor)
                if (!$tarifa) {
                    $tarifa = $this->getTarifaFallbackBase($db, $idTipoTraslado, $idProveedor, $segmentoDominante, $volumenTotal);
                }

                $distanciaUsar = ((float)($tarifa['km'] ?? 0) > 0) ? (float)$tarifa['km'] : $kmTramo;
                $costoPorKm = (float)($tarifa['costo_por_km'] ?? 0);
                $factor = ((float)($tarifa['factor'] ?? 0) > 0) ? (float)$tarifa['factor'] : 1.0;
                $costoPlano = (float)($tarifa['precio_plano'] ?? 0);

                $costoTramo = ($distanciaUsar * $costoPorKm + $costoPlano) * $factor;

                // Insertar el costo del tramo
                $stmtInsertCosto = $db->prepare("
                    INSERT INTO lgs_envios_tramos_costos 
                    (id_envio, id_madrina, id_chofer, id_nodo_origen, id_nodo_destino, km_tramo, vins_ligeros, vins_medianos, vins_pesados, vins_especiales, factor_aplicado, costo_estimado, tarifa_usada_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtInsertCosto->execute([
                    $idEnvio,
                    ($idTipoTraslado === 1) ? $idAgrupador : null,
                    ($idTipoTraslado === 2) ? $idAgrupador : null,
                    $nodoOrigen['id_nodo'],
                    $nodoDestino['id_nodo'],
                    $distanciaUsar,
                    $vinsLigeros,
                    $vinsMedianos,
                    $vinsPesados,
                    ($vinsBuses + $vinsEspeciales),
                    $factor,
                    $costoTramo,
                    $tarifa['id'] ?? null
                ]);

                $costoTotalEnvio += $costoTramo;

                // Actualizar costo unitario (prorrateado informativo)
                $costoUnitarioTramo = $costoTramo / $volumenTotal;
                foreach ($vinsEnTramo as $v) {
                    $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = costo_unidad + ? WHERE id = ?")->execute([$costoUnitarioTramo, $v['id']]);
                }
            }
        }

        // 3. Actualizar el Costo Total y KM Total en la Cabecera
        $stmtKmTotal = $db->prepare("SELECT SUM(km_tramo_anterior) AS total_km FROM lgs_envios_nodos WHERE id_envio = ?");
        $stmtKmTotal->execute([$idEnvio]);
        $rowKmTotal = $stmtKmTotal->fetch(PDO::FETCH_ASSOC);
        $kmTotalCalculado = (float)($rowKmTotal['total_km'] ?? 0);

        $stmtUpdate = $db->prepare("UPDATE lgs_envios SET costo_total = :costo, km_total = :km WHERE id_envio = :id");
        $stmtUpdate->execute([
            'costo' => round($costoTotalEnvio, 2),
            'km'    => round($kmTotalCalculado, 2),
            'id'    => $idEnvio
        ]);

        return $costoTotalEnvio;
    }

    /**
     * Resuelve dinámicamente el segmento de una unidad (VIN)
     */
    private function resolveSegmentoForUnit(PDO $db, int $idUnidad): int {
        // 1. Intentar obtener el modelo del VIN desde lgs_unidades_envios
        $stmtMock = $db->prepare("SELECT vin, modelo FROM lgs_unidades_envios WHERE id_unidad = ? LIMIT 1");
        $stmtMock->execute([$idUnidad]);
        $mock = $stmtMock->fetch(PDO::FETCH_ASSOC);
        
        $vin = '';
        $modelo = '';
        if ($mock) {
            $vin = $mock['vin'] ?? '';
            $modelo = $mock['modelo'] ?? '';
        } else {
            // Intentar desde mrp_unidades_terminadas
            $stmtReal = $db->prepare("
                SELECT ut.clave AS vin, p.descripcion AS modelo 
                FROM mrp_unidades_terminadas ut
                LEFT JOIN mrp_planeacion pl ON ut.planeacionid = pl.idplaneacion
                LEFT JOIN mrp_productos p ON pl.productoid = p.idproducto
                WHERE ut.idunidad = ? 
                LIMIT 1
            ");
            $stmtReal->execute([$idUnidad]);
            $real = $stmtReal->fetch(PDO::FETCH_ASSOC);
            if ($real) {
                $vin = $real['vin'] ?? '';
                $modelo = $real['modelo'] ?? '';
            }
        }

        if (empty($vin) && empty($modelo)) {
            return 1; // Default LIGEROS
        }

        // 2. Buscar en cat_modelos_vin por coincidencia de vin_base (prefijo) o modelo string
        $stmtModel = $db->prepare("
            SELECT id_segmento 
            FROM cat_modelos_vin 
            WHERE (? LIKE CONCAT(vin_base, '%') OR LOWER(modelo) = ? OR ? LIKE CONCAT('%', LOWER(modelo), '%'))
              AND id_segmento IS NOT NULL
            LIMIT 1
        ");
        $stmtModel->execute([$vin, strtolower($modelo), strtolower($modelo)]);
        $res = $stmtModel->fetch(PDO::FETCH_ASSOC);
        
        if ($res && !empty($res['id_segmento'])) {
            return (int)$res['id_segmento'];
        }

        // 3. Fallback: Parsear por nombre del modelo
        $modeloLower = strtolower($modelo);
        if (strpos($modeloLower, 'miller') !== false || strpos($modeloLower, 's3') !== false || strpos($modeloLower, 's5') !== false || strpos($modeloLower, 's6') !== false || strpos($modeloLower, 'van') !== false || strpos($modeloLower, 'pickup') !== false || strpos($modeloLower, 'panel') !== false) {
            return 1; // LIGEROS
        }
        if (strpos($modeloLower, 's8') !== false || strpos($modeloLower, 's12') !== false || strpos($modeloLower, 's20') !== false || strpos($modeloLower, 'chasis') !== false) {
            return 2; // MEDIANO
        }
        if (strpos($modeloLower, 'est') !== false || strpos($modeloLower, 'galaxy') !== false || strpos($modeloLower, 's35') !== false || strpos($modeloLower, 's38') !== false || strpos($modeloLower, 'isg') !== false || strpos($modeloLower, 'tracto') !== false || strpos($modeloLower, 'volteo') !== false) {
            return 3; // PESADO
        }
        if (strpos($modeloLower, 'auv') !== false || strpos($modeloLower, 'araña') !== false || strpos($modeloLower, 'bus') !== false || strpos($modeloLower, 'autob') !== false) {
            return 4; // BUSES
        }
        if (strpos($modeloLower, 'lowboy') !== false) {
            return 5; // LOWBOY
        }

        return 1; // Default LIGEROS
    }

    /**
     * Helper: Busca tarifa ESTRICTA, sin fallbacks globales. Si no hay, retorna nulo.
     */
    private function getTarifaRutaEstricta(PDO $db, int $idTipoTraslado, int $idOrigen, int $idDestino, int $idSegmento, int $volumenVins, int $idProveedor): ?array {
        
        // 1. Intentar con id_proveedor específico
        if ($idProveedor > 0) {
            $sql0 = "SELECT id, km, costo_por_km, precio_plano, factor 
                     FROM lgs_costos_rutas 
                     WHERE id_proveedor = ?
                       AND id_tipo_traslado = ? 
                       AND id_origen = ? 
                       AND id_destino = ? 
                       AND id_segmento = ?
                       AND ? BETWEEN num_vins_min AND num_vins_max
                       AND activo != 0
                     LIMIT 1";
            $stmt0 = $db->prepare($sql0);
            $stmt0->execute([$idProveedor, $idTipoTraslado, $idOrigen, $idDestino, $idSegmento, $volumenVins]);
            $tarifa = $stmt0->fetch(PDO::FETCH_ASSOC);
            if ($tarifa) return $tarifa;
        }

        // 2. Intentar tarifa general (id_proveedor IS NULL o 0)
        $sql = "SELECT id, km, costo_por_km, precio_plano, factor 
                FROM lgs_costos_rutas 
                WHERE id_tipo_traslado = ? 
                  AND id_origen = ? 
                  AND id_destino = ? 
                  AND id_segmento = ?
                  AND ? BETWEEN num_vins_min AND num_vins_max
                  AND activo != 0
                  AND (id_proveedor IS NULL OR id_proveedor = 0)
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idTipoTraslado, $idOrigen, $idDestino, $idSegmento, $volumenVins]);
        $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $tarifa ?: null;
    }

    /**
     * Helper: Obtiene la tarifa fallback cuando una ruta no tiene tarifa estricta definida.
     * Consulta costo base por km del proveedor o del segmento y el factor volumétrico.
     */
    private function getTarifaFallbackBase(PDO $db, int $idTipoTraslado, int $idProveedor, int $idSegmento, int $volumenVins): array {
        $costoPorKm = 0.0;
        $factor = 1.0;

        // 1. Intentar costo_por_km para este proveedor y segmento en cualquier ruta
        if ($idProveedor > 0) {
            $stmtProv = $db->prepare("SELECT costo_por_km, factor FROM lgs_costos_rutas 
                                      WHERE id_proveedor = ? AND id_tipo_traslado = ? AND id_segmento = ? AND costo_por_km > 0 AND activo != 0 
                                      ORDER BY id DESC LIMIT 1");
            $stmtProv->execute([$idProveedor, $idTipoTraslado, $idSegmento]);
            $rowProv = $stmtProv->fetch(PDO::FETCH_ASSOC);
            if ($rowProv && floatval($rowProv['costo_por_km']) > 0) {
                $costoPorKm = (float)$rowProv['costo_por_km'];
            }
        }

        // 2. Si no hay tarifa del proveedor, buscar costo base general del segmento en lgs_costos_rutas
        if ($costoPorKm <= 0) {
            $stmtBase = $db->prepare("SELECT costo_por_km FROM lgs_costos_rutas 
                                      WHERE id_tipo_traslado = ? AND id_segmento = ? AND costo_por_km > 0 AND activo != 0 
                                      ORDER BY id DESC LIMIT 1");
            $stmtBase->execute([$idTipoTraslado, $idSegmento]);
            $rowBase = $stmtBase->fetch(PDO::FETCH_ASSOC);
            if ($rowBase && floatval($rowBase['costo_por_km']) > 0) {
                $costoPorKm = (float)$rowBase['costo_por_km'];
            }
        }

        // 3. Si aún no hay, usar tarifas base oficiales por segmento
        if ($costoPorKm <= 0) {
            $defaultSegmentos = [
                1 => 18.0000, // Ligeros
                2 => 20.0000, // Medianos
                3 => 25.0000, // Pesados
                4 => 28.0000, // Autobuses
                5 => 80.0000  // Lowboy
            ];
            $costoPorKm = $defaultSegmentos[$idSegmento] ?? 20.0000;
        }

        // 4. Factor volumétrico según volumenTotal (1 al 15)
        if ($idTipoTraslado === 1 && $volumenVins > 1) {
            $stmtFactor = $db->prepare("SELECT factor FROM lgs_costos_rutas 
                                        WHERE id_tipo_traslado = 1 
                                          AND ? BETWEEN num_vins_min AND num_vins_max 
                                          AND factor > 0 AND activo != 0 
                                        ORDER BY id DESC LIMIT 1");
            $stmtFactor->execute([$volumenVins]);
            $rowFactor = $stmtFactor->fetch(PDO::FETCH_ASSOC);
            if ($rowFactor && floatval($rowFactor['factor']) > 0) {
                $factor = (float)$rowFactor['factor'];
            }
        }

        return [
            'id' => null,
            'km' => 0.00,
            'costo_por_km' => $costoPorKm,
            'precio_plano' => 0.00,
            'factor' => $factor,
            'es_fallback' => true
        ];
    }

    /**
     * Helper: Actualiza el costo individual calculado para el VIN
     */
    private function updateCostoVin(PDO $db, int $idVinEnvio, float $costo): void {
        $stmt = $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = :costo WHERE id = :id");
        $stmt->execute(['costo' => $costo, 'id' => $idVinEnvio]);
    }
}
