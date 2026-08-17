<?php

class Lgs_costosModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obtiene el listado de rutas agrupadas con sus métricas y preview de segmentos
     */
    public function selectRutasAgrupadas(): array
    {
        $sql = "SELECT 
                    r.id_tipo_traslado,
                    tt.nombre AS tipo_traslado,
                    r.id_origen,
                    o.nombre AS origen,
                    r.id_destino,
                    d.nombre AS destino,
                    MAX(r.km) AS km,
                    COUNT(DISTINCT r.id_segmento) AS total_segmentos,
                    GROUP_CONCAT(
                        DISTINCT CONCAT(s.nombre, ':', r.costo_por_km) 
                        ORDER BY s.id_segmento ASC 
                        SEPARATOR ' | '
                    ) AS segmentos_resumen,
                    MAX(r.activo) AS activo
                FROM lgs_costos_rutas r
                INNER JOIN lgs_cat_tipo_traslado tt ON r.id_tipo_traslado = tt.id_tipo_traslado
                INNER JOIN lgs_cat_origenes o ON r.id_origen = o.id_origen
                INNER JOIN lgs_cat_destinos d ON r.id_destino = d.id_destino
                INNER JOIN lgs_cat_segmentos s ON r.id_segmento = s.id_segmento
                WHERE r.activo != 0
                GROUP BY r.id_tipo_traslado, r.id_origen, r.id_destino
                ORDER BY o.nombre ASC, d.nombre ASC";
        return $this->select_all($sql) ?: [];
    }

    /**
     * Obtiene la matriz completa de tarifas de una ruta para todos los segmentos y sus 15 factores
     */
    public function selectRutaMatriz(int $idTipoTraslado, int $idOrigen, int $idDestino): array
    {
        // 1. Obtener datos de segmentos activos
        $sqlSegmentos = "SELECT id_segmento, nombre, descripcion FROM lgs_cat_segmentos WHERE activo = 2 ORDER BY id_segmento ASC";
        $segmentos = $this->select_all($sqlSegmentos) ?: [];

        // 2. Obtener tarifas existentes
        $sqlTarifas = "SELECT id, id_segmento, num_vins_min, num_vins_max, km, costo_por_km, precio_plano, factor, activo
                       FROM lgs_costos_rutas
                       WHERE id_tipo_traslado = ? AND id_origen = ? AND id_destino = ? AND activo != 0
                       ORDER BY num_vins_min ASC";
        $tarifas = $this->select_all($sqlTarifas, [$idTipoTraslado, $idOrigen, $idDestino]) ?: [];

        // Mapear tarifas por id_segmento
        $tarifasMap = [];
        $kmRuta = 0.0;
        foreach ($tarifas as $t) {
            $tarifasMap[$t['id_segmento']][] = $t;
            if ($t['km'] > $kmRuta) {
                $kmRuta = (float)$t['km'];
            }
        }

        // Estructurar respuesta unificada para cada segmento
        $matriz = [];
        foreach ($segmentos as $seg) {
            $idSeg = (int)$seg['id_segmento'];
            $items = $tarifasMap[$idSeg] ?? [];
            
            $costoPorKm = 0.00;
            $precioPlano = 0.00;
            $factorBase = 1.00;

            if (!empty($items)) {
                $costoPorKm = (float)$items[0]['costo_por_km'];
                $precioPlano = (float)$items[0]['precio_plano'];
                $factorBase = (float)$items[0]['factor'];
            }

            // Construir el mapa de 1 a 15 factores de unidades
            $factores15 = [];
            for ($u = 1; $u <= 15; $u++) {
                $f = $factorBase;
                foreach ($items as $it) {
                    if ($u >= (int)$it['num_vins_min'] && $u <= (int)$it['num_vins_max']) {
                        $f = (float)$it['factor'];
                        break;
                    }
                }
                $factores15[$u] = $f;
            }

            $matriz[] = [
                'id_segmento' => $idSeg,
                'segmento_nombre' => $seg['nombre'],
                'segmento_descripcion' => $seg['descripcion'],
                'costo_por_km' => $costoPorKm,
                'precio_plano' => $precioPlano,
                'factor_base' => $factorBase,
                'factores_15' => $factores15,
                'tarifas_raw' => $items
            ];
        }

        return [
            'id_tipo_traslado' => $idTipoTraslado,
            'id_origen' => $idOrigen,
            'id_destino' => $idDestino,
            'km' => $kmRuta,
            'matriz' => $matriz
        ];
    }

    /**
     * Obtiene los datos de tarifas tanto para Madrina (1) como para Chofer (2) del mismo trayecto
     */
    public function selectRutaDual(int $idOrigen, int $idDestino): array
    {
        $madrina = $this->selectRutaMatriz(1, $idOrigen, $idDestino);
        $chofer = $this->selectRutaMatriz(2, $idOrigen, $idDestino);

        $km = max($madrina['km'] ?? 0, $chofer['km'] ?? 0);

        return [
            'id_origen' => $idOrigen,
            'id_destino' => $idDestino,
            'km' => $km,
            'madrina' => $madrina['matriz'],
            'chofer' => $chofer['matriz']
        ];
    }

    /**
     * Guarda o actualiza la matriz completa de tarifas para una ruta soportando los 15 factores
     */
    public function saveRutaMatriz(int $idTipoTraslado, int $idOrigen, int $idDestino, float $km, array $segmentosData): bool
    {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();

            foreach ($segmentosData as $seg) {
                $idSegmento = intval($seg['id_segmento']);
                $costoPorKm = floatval($seg['costo_por_km'] ?? 0);
                $precioPlano = floatval($seg['precio_plano'] ?? 0);

                // MODALIDAD 2: CHOFER (RODANDO) -> Solo 1 unidad/factor fija
                if ($idTipoTraslado === 2) {
                    $stmtDel = $db->prepare("DELETE FROM lgs_costos_rutas 
                                             WHERE id_tipo_traslado = ? AND id_origen = ? AND id_destino = ? AND id_segmento = ?");
                    $stmtDel->execute([$idTipoTraslado, $idOrigen, $idDestino, $idSegmento]);

                    $stmtIns = $db->prepare("INSERT INTO lgs_costos_rutas (
                                                id_tipo_traslado, id_origen, id_destino, id_segmento, 
                                                num_vins_min, num_vins_max, km, costo_por_km, precio_plano, factor, activo
                                             ) VALUES (?, ?, ?, ?, 1, 1, ?, ?, ?, 1.00, 2)");
                    $stmtIns->execute([$idTipoTraslado, $idOrigen, $idDestino, $idSegmento, $km, $costoPorKm, $precioPlano]);
                }
                // MODALIDAD 1: MADRINA -> Soporta factores del 1 al 15
                else {
                    // Si viene el desglose de los 15 factores
                    if (isset($seg['factores']) && is_array($seg['factores']) && count($seg['factores']) > 0) {
                        $stmtDel = $db->prepare("DELETE FROM lgs_costos_rutas 
                                                 WHERE id_tipo_traslado = ? AND id_origen = ? AND id_destino = ? AND id_segmento = ?");
                        $stmtDel->execute([$idTipoTraslado, $idOrigen, $idDestino, $idSegmento]);

                        $stmtIns = $db->prepare("INSERT INTO lgs_costos_rutas (
                                                    id_tipo_traslado, id_origen, id_destino, id_segmento, 
                                                    num_vins_min, num_vins_max, km, costo_por_km, precio_plano, factor, activo
                                                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2)");

                        foreach ($seg['factores'] as $unidad => $factorVal) {
                            $u = intval($unidad);
                            $f = floatval($factorVal);
                            if ($u >= 1 && $u <= 15) {
                                $stmtIns->execute([
                                    $idTipoTraslado, $idOrigen, $idDestino, $idSegmento,
                                    $u, $u, $km, $costoPorKm, $precioPlano, $f
                                ]);
                            }
                        }
                    } else {
                        // Factor único / base para madrina
                        $factor = floatval($seg['factor'] ?? 1.0);
                        $min = intval($seg['num_vins_min'] ?? 1);
                        $max = intval($seg['num_vins_max'] ?? 15);

                        $sqlUpsert = "INSERT INTO lgs_costos_rutas (
                                        id_tipo_traslado, id_origen, id_destino, id_segmento, 
                                        num_vins_min, num_vins_max, km, costo_por_km, precio_plano, factor, activo
                                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2)
                                      ON DUPLICATE KEY UPDATE 
                                        km = VALUES(km), 
                                        costo_por_km = VALUES(costo_por_km), 
                                        precio_plano = VALUES(precio_plano), 
                                        factor = VALUES(factor), 
                                        activo = 2";
                        $stmt = $db->prepare($sqlUpsert);
                        $stmt->execute([
                            $idTipoTraslado, $idOrigen, $idDestino, $idSegmento,
                            $min, $max, $km, $costoPorKm, $precioPlano, $factor
                        ]);
                    }
                }
            }

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Elimina lógicamente todas las tarifas asociadas a una ruta
     */
    public function deleteRuta(int $idTipoTraslado, int $idOrigen, int $idDestino): bool
    {
        $sql = "UPDATE lgs_costos_rutas 
                SET activo = 0 
                WHERE id_tipo_traslado = ? AND id_origen = ? AND id_destino = ?";
        return $this->update($sql, [$idTipoTraslado, $idOrigen, $idDestino]);
    }

    /**
     * Obtiene estadísticas y KPIs para el dashboard superior
     */
    public function getKpis(): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT CONCAT(id_tipo_traslado, '-', id_origen, '-', id_destino)) AS total_rutas,
                    COUNT(DISTINCT id_origen) AS total_origenes,
                    COUNT(DISTINCT id_destino) AS total_destinos,
                    COUNT(DISTINCT id_segmento) AS total_segmentos
                FROM lgs_costos_rutas 
                WHERE activo != 0";
        return $this->select($sql) ?: [
            'total_rutas' => 0,
            'total_origenes' => 0,
            'total_destinos' => 0,
            'total_segmentos' => 0
        ];
    }

    /**
     * Selectores para los formularios de catálogos
     */
    public function selectTiposTraslado(): array
    {
        return $this->select_all("SELECT id_tipo_traslado, nombre FROM lgs_cat_tipo_traslado WHERE activo = 1") ?: [];
    }

    public function selectOrigenes(): array
    {
        return $this->select_all("SELECT id_origen, nombre FROM lgs_cat_origenes WHERE activo = 1 ORDER BY nombre ASC") ?: [];
    }

    public function selectDestinos(): array
    {
        return $this->select_all("SELECT id_destino, nombre FROM lgs_cat_destinos WHERE activo = 1 ORDER BY nombre ASC") ?: [];
    }

    public function selectSegmentos(): array
    {
        return $this->select_all("SELECT id_segmento, nombre, descripcion FROM lgs_cat_segmentos WHERE activo = 2 ORDER BY id_segmento ASC") ?: [];
    }

    /**
     * Obtiene el listado de modelos VIN para el mapeo
     */
    public function selectModelosVin(): array
    {
        $sql = "SELECT m.id_cat_modelo_vin, m.modelo, m.vin_base, s.nombre AS segmento 
                FROM cat_modelos_vin m
                LEFT JOIN lgs_cat_segmentos s ON m.id_segmento = s.id_segmento
                WHERE m.estado != 0
                ORDER BY m.modelo ASC";
        return $this->select_all($sql) ?: [];
    }

    /**
     * Actualiza el segmento asignado a un modelo de VIN
     */
    public function updateModeloSegmento(int $idModelo, ?int $idSegmento): bool
    {
        $sql = "UPDATE cat_modelos_vin SET id_segmento = ? WHERE id_cat_modelo_vin = $idModelo";
        return $this->update($sql, [$idSegmento]);
    }

    /**
     * Obtiene todas las rutas activas con su desglose de segmentos y factores para exportación completa
     */
    public function selectExportData(): array
    {
        $sql = "SELECT 
                    tt.nombre AS tipo_traslado,
                    o.nombre AS origen,
                    d.nombre AS destino,
                    r.km,
                    s.nombre AS segmento,
                    r.num_vins_min,
                    r.num_vins_max,
                    r.costo_por_km,
                    r.precio_plano,
                    r.factor,
                    ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) AS precio_unitario,
                    ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor * r.num_vins_min, 2) AS flete_total
                FROM lgs_costos_rutas r
                INNER JOIN lgs_cat_tipo_traslado tt ON r.id_tipo_traslado = tt.id_tipo_traslado
                INNER JOIN lgs_cat_origenes o ON r.id_origen = o.id_origen
                INNER JOIN lgs_cat_destinos d ON r.id_destino = d.id_destino
                INNER JOIN lgs_cat_segmentos s ON r.id_segmento = s.id_segmento
                WHERE r.activo != 0
                ORDER BY tt.nombre ASC, o.nombre ASC, d.nombre ASC, s.id_segmento ASC, r.num_vins_min ASC";
        return $this->select_all($sql) ?: [];
    }

    /**
     * Obtiene el resumen matricial de todas las rutas registradas (filtrable por modalidad)
     */
    public function selectExportMatriz(?int $idTipoTraslado = null): array
    {
        $where = "WHERE r.activo != 0";
        $params = [];
        if ($idTipoTraslado !== null && $idTipoTraslado > 0) {
            $where .= " AND r.id_tipo_traslado = ?";
            $params[] = $idTipoTraslado;
        }

        $sql = "SELECT 
                    tt.nombre AS tipo_traslado,
                    r.id_tipo_traslado,
                    o.nombre AS origen,
                    d.nombre AS destino,
                    MAX(r.km) AS km,
                    MAX(CASE WHEN s.id_segmento = 1 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS ligeros,
                    MAX(CASE WHEN s.id_segmento = 2 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS mediano,
                    MAX(CASE WHEN s.id_segmento = 3 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS pesado,
                    MAX(CASE WHEN s.id_segmento = 4 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS buses,
                    MAX(CASE WHEN s.id_segmento = 5 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS lowboy
                FROM lgs_costos_rutas r
                INNER JOIN lgs_cat_tipo_traslado tt ON r.id_tipo_traslado = tt.id_tipo_traslado
                INNER JOIN lgs_cat_origenes o ON r.id_origen = o.id_origen
                INNER JOIN lgs_cat_destinos d ON r.id_destino = d.id_destino
                INNER JOIN lgs_cat_segmentos s ON r.id_segmento = s.id_segmento
                $where
                GROUP BY r.id_tipo_traslado, r.id_origen, r.id_destino
                ORDER BY tt.nombre ASC, o.nombre ASC, d.nombre ASC";
        return $this->select_all($sql, $params) ?: [];
    }

    /**
     * Obtiene el desglose completo de Factores 1 al 15 para Madrinas
     */
    public function selectExportMadrinaFactores(): array
    {
        $sql = "SELECT 
                    o.nombre AS origen,
                    d.nombre AS destino,
                    MAX(r.km) AS km,
                    s.nombre AS segmento,
                    MAX(CASE WHEN r.num_vins_min = 1 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_1,
                    MAX(CASE WHEN r.num_vins_min = 2 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_2,
                    MAX(CASE WHEN r.num_vins_min = 3 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_3,
                    MAX(CASE WHEN r.num_vins_min = 4 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_4,
                    MAX(CASE WHEN r.num_vins_min = 5 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_5,
                    MAX(CASE WHEN r.num_vins_min = 6 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_6,
                    MAX(CASE WHEN r.num_vins_min = 7 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_7,
                    MAX(CASE WHEN r.num_vins_min = 8 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_8,
                    MAX(CASE WHEN r.num_vins_min = 9 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_9,
                    MAX(CASE WHEN r.num_vins_min = 10 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_10,
                    MAX(CASE WHEN r.num_vins_min = 11 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_11,
                    MAX(CASE WHEN r.num_vins_min = 12 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_12,
                    MAX(CASE WHEN r.num_vins_min = 13 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_13,
                    MAX(CASE WHEN r.num_vins_min = 14 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_14,
                    MAX(CASE WHEN r.num_vins_min = 15 THEN ROUND((r.km * r.costo_por_km + r.precio_plano) * r.factor, 2) END) AS factor_15
                FROM lgs_costos_rutas r
                INNER JOIN lgs_cat_origenes o ON r.id_origen = o.id_origen
                INNER JOIN lgs_cat_destinos d ON r.id_destino = d.id_destino
                INNER JOIN lgs_cat_segmentos s ON r.id_segmento = s.id_segmento
                WHERE r.activo != 0 AND r.id_tipo_traslado = 1
                GROUP BY r.id_origen, r.id_destino, r.id_segmento
                ORDER BY o.nombre ASC, d.nombre ASC, s.id_segmento ASC";
        return $this->select_all($sql) ?: [];
    }
}
