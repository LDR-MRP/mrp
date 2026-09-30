<?php

class Lgs_costosModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ==========================================
     * MÓDULO 1: DISTANCIAS GEOGRÁFICAS (KMs)
     * ==========================================
     */
    public function selectDistanciasAgrupadas(): array
    {
        $sql = "SELECT 
                    d.id_distancia,
                    d.id_ubicacion_a,
                    ua.nombre AS origen_nombre,
                    d.id_ubicacion_b,
                    ub.nombre AS destino_nombre,
                    d.km
                FROM lgs_distancias d
                INNER JOIN lgs_cat_ubicaciones ua ON d.id_ubicacion_a = ua.id_ubicacion
                INNER JOIN lgs_cat_ubicaciones ub ON d.id_ubicacion_b = ub.id_ubicacion
                ORDER BY ua.nombre ASC, ub.nombre ASC";
        return $this->select_all($sql) ?: [];
    }

    public function saveDistancia(int $idUbicacionA, int $idUbicacionB, float $km): bool
    {
        // Ordenar IDs para mantener la regla bidireccional (menor primero)
        $idA = min($idUbicacionA, $idUbicacionB);
        $idB = max($idUbicacionA, $idUbicacionB);

        $check = $this->select("SELECT id_distancia FROM lgs_distancias WHERE id_ubicacion_a = $idA AND id_ubicacion_b = $idB");
        if(empty($check)){
            $sql = "INSERT INTO lgs_distancias (id_ubicacion_a, id_ubicacion_b, km) VALUES (?, ?, ?)";
            $request = $this->insert($sql, [$idA, $idB, $km]);
            return $request > 0;
        }else{
            $sql = "UPDATE lgs_distancias SET km = ? WHERE id_ubicacion_a = ? AND id_ubicacion_b = ?";
            $request = $this->update($sql, [$km, $idA, $idB]);
            return $request;
        }
    }

    public function deleteDistancia(int $idDistancia): bool
    {
        $sql = "DELETE FROM lgs_distancias WHERE id_distancia = ?";
        return $this->delete($sql, [$idDistancia]);
    }

    /**
     * ==========================================
     * MÓDULO 2: TARIFAS POR PROVEEDOR
     * ==========================================
     */
    public function selectTarifasProveedor(?int $idProveedor = null): array
    {
        // 1. Segmentos
        $sqlSegmentos = "SELECT id_segmento, nombre, descripcion FROM lgs_cat_segmentos WHERE activo = 2 ORDER BY id_segmento ASC";
        $segmentos = $this->select_all($sqlSegmentos) ?: [];

        $provVal = ($idProveedor !== null && $idProveedor > 0) ? $idProveedor : 0;

        // 2. Consultar tarifas en BD
        $sqlTarifas = "SELECT id_tipo_traslado, id_segmento, num_vins_min, num_vins_max, costo_por_km, precio_plano, factor, es_personalizada
                       FROM lgs_tarifas_proveedores
                       WHERE id_proveedor = ? AND activo != 0
                       ORDER BY num_vins_min ASC";

        // Tarifas de la Base General (id_proveedor = 0)
        $tarifasGlobal = $this->select_all($sqlTarifas, [0]) ?: [];

        // Tarifas del proveedor específico si aplica
        $tarifasProv = [];
        if ($provVal > 0) {
            $tarifasProv = $this->select_all($sqlTarifas, [$provVal]) ?: [];
        }

        // Agrupar globales por [tipo_traslado][id_segmento]
        $globalMap = [1 => [], 2 => [], 3 => []];
        foreach ($tarifasGlobal as $t) {
            $globalMap[(int)$t['id_tipo_traslado']][(int)$t['id_segmento']][] = $t;
        }

        // Agrupar proveedor por [tipo_traslado][id_segmento]
        $provMap = [1 => [], 2 => [], 3 => []];
        foreach ($tarifasProv as $t) {
            $provMap[(int)$t['id_tipo_traslado']][(int)$t['id_segmento']][] = $t;
        }

        $tieneTarifasPropias = !empty($tarifasProv);
        $esPersonalizada = false;
        if ($provVal > 0 && !empty($tarifasProv)) {
            foreach ($tarifasProv as $tp) {
                if (!empty($tp['es_personalizada'])) {
                    $esPersonalizada = true;
                    break;
                }
            }
        }

        $generarMatriz = function($tipoTraslado, $forzarGlobal = false) use ($segmentos, $provMap, $globalMap, $provVal) {
            $matriz = [];
            foreach ($segmentos as $seg) {
                $idSeg = (int)$seg['id_segmento'];
                
                // Prioridad 1: Tarifa propia del proveedor (si no se fuerza global)
                $items = (!$forzarGlobal && $provVal > 0 && !empty($provMap[$tipoTraslado][$idSeg])) 
                    ? $provMap[$tipoTraslado][$idSeg] 
                    : [];

                $esHeredado = false;

                // Prioridad 2: Si no tiene tarifa propia o se fuerza global, precargar de la global
                if (empty($items)) {
                    $items = $globalMap[$tipoTraslado][$idSeg] ?? [];
                    if ($provVal > 0 && !empty($items)) {
                        $esHeredado = true;
                    }
                }

                $costoPorKm = 0.00;
                $precioPlano = 0.00;
                $factorBase = 1.00;

                if (!empty($items)) {
                    $costoPorKm = (float)$items[0]['costo_por_km'];
                    $precioPlano = (float)$items[0]['precio_plano'];
                    $factorBase = (float)$items[0]['factor'];
                }

                $factores15 = [];
                $maxU = ($tipoTraslado === 3) ? 4 : 10;
                for ($u = 1; $u <= $maxU; $u++) {
                    if (!empty($items)) {
                        $f = $factorBase;
                        foreach ($items as $it) {
                            if ($u >= (int)$it['num_vins_min'] && $u <= (int)$it['num_vins_max']) {
                                $f = (float)$it['factor'];
                                break;
                            }
                        }
                    } else {
                        if ($tipoTraslado === 3) {
                            // Plataforma (1 a 3 unidades): asume un cobro equivalente a mover un lowboy ($80)
                            $f = max(0.20, (80.0 / ($costoPorKm > 0 ? $costoPorKm : 27.0)) / $u);
                        } else {
                            // Descuento progresivo estimado por defecto (-2% por VIN)
                            $f = max(0.20, 1.0 - (($u - 1) * 0.02));
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
                    'es_heredado' => $esHeredado,
                    'tarifas_raw' => $items
                ];
            }
            return $matriz;
        };

        return [
            'id_proveedor' => $provVal,
            'tiene_tarifas_propias' => $tieneTarifasPropias,
            'es_personalizada' => $esPersonalizada,
            'madrina' => $generarMatriz(1),
            'chofer'  => $generarMatriz(2),
            'plataforma' => $generarMatriz(3),
            'base_general' => [
                'madrina' => $generarMatriz(1, true),
                'chofer'  => $generarMatriz(2, true),
                'plataforma' => $generarMatriz(3, true)
            ]
        ];
    }

    /**
     * Helper privado para guardar las tarifas de un proveedor individual o base general
     */
    private function saveTarifasProveedorInterno(
        PDO $db, 
        int $idProveedor, 
        array $madrinaSegs, 
        array $choferSegs, 
        array $plataformaSegs = [], 
        bool $esPersonalizada = false
    ): void
    {
        $provVal = $idProveedor;
        $flagPersonalizada = $esPersonalizada ? 1 : 0;

        // Limpiar tarifas actuales para este proveedor
        $stmtDel = $db->prepare("DELETE FROM lgs_tarifas_proveedores WHERE id_proveedor = ?");
        $stmtDel->execute([$provVal]);

        $stmtIns = $db->prepare("INSERT INTO lgs_tarifas_proveedores (
                                    id_proveedor, id_tipo_traslado, id_segmento,
                                    num_vins_min, num_vins_max, costo_por_km, precio_plano, factor, es_personalizada
                                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Guardar Madrina (Factores 1-10)
        if (!empty($madrinaSegs)) {
            foreach ($madrinaSegs as $seg) {
                $idSegmento = intval($seg['id_segmento']);
                $costoPorKm = floatval($seg['costo_por_km'] ?? 0);
                $precioPlano = floatval($seg['precio_plano'] ?? 0);

                if (isset($seg['factores']) && is_array($seg['factores']) && count($seg['factores']) > 0) {
                    foreach ($seg['factores'] as $unidad => $valIngresado) {
                        $u = intval($unidad);
                        $precioReal = floatval($valIngresado);
                        if ($u >= 1 && $u <= 10) {
                            $fFinal = ($costoPorKm > 0) ? ($precioReal / $costoPorKm) : 1.0;
                            $stmtIns->execute([$provVal, 1, $idSegmento, $u, $u, $costoPorKm, $precioPlano, $fFinal, $flagPersonalizada]);
                        }
                    }
                } else {
                    $valIngresado = floatval($seg['factor'] ?? 0);
                    $fFinal = ($costoPorKm > 0 && $valIngresado > 0) ? ($valIngresado / $costoPorKm) : 1.0;
                    $stmtIns->execute([$provVal, 1, $idSegmento, 1, 10, $costoPorKm, $precioPlano, $fFinal, $flagPersonalizada]);
                }
            }
        }

        // Guardar Chofer (Fijo 1 unidad)
        if (!empty($choferSegs)) {
            foreach ($choferSegs as $seg) {
                $idSegmento = intval($seg['id_segmento']);
                $costoPorKm = floatval($seg['costo_por_km'] ?? 0);
                $precioPlano = floatval($seg['precio_plano'] ?? 0);
                $stmtIns->execute([$provVal, 2, $idSegmento, 1, 1, $costoPorKm, $precioPlano, 1.00, $flagPersonalizada]);
            }
        }
        // Guardar Plataforma (Factores 1-4)
        if (!empty($plataformaSegs)) {
            foreach ($plataformaSegs as $seg) {
                $idSegmento = intval($seg['id_segmento']);
                $costoPorKm = floatval($seg['costo_por_km'] ?? 0);
                $precioPlano = floatval($seg['precio_plano'] ?? 0);

                if (isset($seg['factores']) && is_array($seg['factores']) && count($seg['factores']) > 0) {
                    foreach ($seg['factores'] as $unidad => $valIngresado) {
                        $u = intval($unidad);
                        $precioReal = floatval($valIngresado);
                        if ($u >= 1 && $u <= 4) {
                            $fFinal = ($costoPorKm > 0) ? ($precioReal / $costoPorKm) : 1.0;
                            $stmtIns->execute([$provVal, 3, $idSegmento, $u, $u, $costoPorKm, $precioPlano, $fFinal, $flagPersonalizada]);
                        }
                    }
                } else {
                    $valIngresado = floatval($seg['factor'] ?? 0);
                    $fFinal = ($costoPorKm > 0 && $valIngresado > 0) ? ($valIngresado / $costoPorKm) : 1.0;
                    $stmtIns->execute([$provVal, 3, $idSegmento, 1, 4, $costoPorKm, $precioPlano, $fFinal, $flagPersonalizada]);
                }
            }
        }
    }

    public function saveTarifasProveedor(int $idProveedor, array $madrinaSegs, array $choferSegs, array $plataformaSegs = []): bool
    {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            // Al guardar tarifas para un proveedor específico (>0), se marcan como personalizadas (1)
            $esPersonalizada = ($idProveedor > 0);
            $this->saveTarifasProveedorInterno($db, $idProveedor, $madrinaSegs, $choferSegs, $plataformaSegs, $esPersonalizada);
            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Guarda la Tarifa Base General (id=0) y opcionalmente la replica a los proveedores seleccionados
     */
    public function saveTarifasBaseConReplicacion(
        array $madrinaSegs, 
        array $choferSegs, 
        array $plataformaSegs = [], 
        array $proveedoresReplicar = [],
        bool $mantenerPersonalizadas = false
    ): bool
    {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();

            // 1. Guardar la Base General (id_proveedor = 0, es_personalizada = 0)
            $this->saveTarifasProveedorInterno($db, 0, $madrinaSegs, $choferSegs, $plataformaSegs, false);

            // 2. Replicar a los proveedores seleccionados (id_proveedor > 0)
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM lgs_tarifas_proveedores WHERE id_proveedor = ? AND es_personalizada = 1 AND activo != 0");

            foreach ($proveedoresReplicar as $idProv) {
                $idProvVal = intval($idProv);
                if ($idProvVal > 0) {
                    if ($mantenerPersonalizadas) {
                        $stmtCheck->execute([$idProvVal]);
                        if ((int)$stmtCheck->fetchColumn() > 0) {
                            // Este proveedor tiene tarifas personalizadas y se solicitó conservarlas
                            continue;
                        }
                    }
                    // La réplica hereda del global, por ende es_personalizada = 0
                    $this->saveTarifasProveedorInterno($db, $idProvVal, $madrinaSegs, $choferSegs, $plataformaSegs, false);
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
     * Compara las tarifas de un proveedor específico contra la Base General
     */
    public function compareProveedorConGlobal(int $idProveedor): array
    {
        $info = $this->selectTarifasProveedor($idProveedor);
        $provMadrina = $info['madrina'] ?? [];
        $baseMadrina = $info['base_general']['madrina'] ?? [];

        $diferencias = [];
        if (!empty($provMadrina) && !empty($baseMadrina)) {
            $baseMap = [];
            foreach ($baseMadrina as $b) {
                $baseMap[$b['id_segmento']] = $b;
            }
            foreach ($provMadrina as $p) {
                $idSeg = $p['id_segmento'];
                $b = $baseMap[$idSeg] ?? null;
                if (!$b) continue;

                $pCost = (float)$p['costo_por_km'];
                $bCost = (float)$b['costo_por_km'];

                for ($u = 1; $u <= 10; $u++) {
                    $pUnit = round($pCost * (float)($p['factores_15'][$u] ?? 1.0), 2);
                    $bUnit = round($bCost * (float)($b['factores_15'][$u] ?? 1.0), 2);
                    if (abs($pUnit - $bUnit) > 0.01) {
                        $diferencias[] = [
                            'tipo' => 'Madrina',
                            'id_segmento' => $idSeg,
                            'segmento' => $p['segmento_nombre'],
                            'unidad' => $u,
                            'precio_proveedor' => $pUnit,
                            'precio_global' => $bUnit,
                            'diferencia' => round($pUnit - $bUnit, 2)
                        ];
                    }
                }
            }
        }

        $provPlat = $info['plataforma'] ?? [];
        $basePlat = $info['base_general']['plataforma'] ?? [];
        if (!empty($provPlat) && !empty($basePlat)) {
            $basePlatMap = [];
            foreach ($basePlat as $b) {
                $basePlatMap[$b['id_segmento']] = $b;
            }
            foreach ($provPlat as $p) {
                $idSeg = $p['id_segmento'];
                $b = $basePlatMap[$idSeg] ?? null;
                if (!$b) continue;

                $pCost = (float)$p['costo_por_km'];
                $bCost = (float)$b['costo_por_km'];

                for ($u = 1; $u <= 4; $u++) {
                    $pUnit = round($pCost * (float)($p['factores_15'][$u] ?? 1.0), 2);
                    $bUnit = round($bCost * (float)($b['factores_15'][$u] ?? 1.0), 2);
                    if (abs($pUnit - $bUnit) > 0.01) {
                        $diferencias[] = [
                            'tipo' => 'Plataforma',
                            'id_segmento' => $idSeg,
                            'segmento' => $p['segmento_nombre'],
                            'unidad' => $u,
                            'precio_proveedor' => $pUnit,
                            'precio_global' => $bUnit,
                            'diferencia' => round($pUnit - $bUnit, 2)
                        ];
                    }
                }
            }
        }

        return [
            'id_proveedor' => $idProveedor,
            'es_personalizada' => !empty($info['es_personalizada']),
            'total_diferencias' => count($diferencias),
            'diferencias' => $diferencias
        ];
    }

    /**
     * Elimina las tarifas personalizadas de un proveedor para que vuelva a heredar dinámicamente la base general
     */
    public function resetTarifasProveedor(int $idProveedor): bool
    {
        if ($idProveedor <= 0) {
            throw new Exception("No se puede restablecer la Tarifa Base General.", 400);
        }
        $db = $this->getConexion();
        $stmtDel = $db->prepare("DELETE FROM lgs_tarifas_proveedores WHERE id_proveedor = ?");
        return $stmtDel->execute([$idProveedor]);
    }

    /**
     * Retorna la lista de transportistas con indicador de si tienen tarifas personalizadas
     */
    public function selectProveedoresConEstadoTarifa(): array
    {
        $sql = "SELECT DISTINCT
                    p.id_proveedor,
                    p.razon_social,
                    p.nombre_comercial,
                    (CASE WHEN MAX(t.es_personalizada) = 1 THEN 1 ELSE 0 END) AS tiene_personalizada,
                    (CASE WHEN COUNT(t.id_tarifa) > 0 THEN 1 ELSE 0 END) AS tiene_tarifas_registradas,
                    MAX(t.updated_at) AS ultima_actualizacion
                FROM prv_cat_proveedores p
                INNER JOIN prv_rel_proveedores_actividades r ON p.id_proveedor = r.id_proveedor
                INNER JOIN prv_cat_actividades a ON a.id_actividad = r.id_actividad
                LEFT JOIN lgs_tarifas_proveedores t ON t.id_proveedor = p.id_proveedor AND t.activo != 0
                WHERE a.cve_actividad = 'TRASLADO_UNIDADES' 
                  AND p.deleted_at IS NULL
                GROUP BY p.id_proveedor, p.razon_social, p.nombre_comercial
                ORDER BY p.razon_social ASC";
        return $this->select_all($sql) ?: [];
    }

    /**
     * ==========================================
     * KPIs y Selectores
     * ==========================================
     */
    public function getKpis(): array
    {
        $totalDist = ($this->select("SELECT COUNT(*) as t FROM lgs_distancias"))['t'] ?? 0;
        
        $sqlNodos = "SELECT COUNT(DISTINCT id_ubicacion) AS t FROM (
                        SELECT id_ubicacion_a AS id_ubicacion FROM lgs_distancias
                        UNION
                        SELECT id_ubicacion_b AS id_ubicacion FROM lgs_distancias
                     ) AS tmp";
        $totalNodos = ($this->select($sqlNodos))['t'] ?? 0;

        $totalProv = ($this->select("SELECT COUNT(DISTINCT id_proveedor) as t FROM lgs_tarifas_proveedores WHERE id_proveedor > 0 AND activo != 0"))['t'] ?? 0;
        $totalSeg = ($this->select("SELECT COUNT(*) as t FROM lgs_cat_segmentos WHERE activo=2"))['t'] ?? 0;

        return [
            'total_distancias' => $totalDist,
            'total_nodos' => $totalNodos,
            'total_proveedores_config' => $totalProv,
            'total_segmentos' => $totalSeg
        ];
    }

    public function selectTiposTraslado(): array
    {
        return $this->select_all("SELECT id_tipo_traslado, nombre FROM lgs_cat_tipo_traslado WHERE activo = 1") ?: [];
    }

    public function selectUbicaciones(): array
    {
        // Unificamos Origenes y Destinos
        return $this->select_all("SELECT id_ubicacion, nombre FROM lgs_cat_ubicaciones WHERE activo = 1 ORDER BY nombre ASC") ?: [];
    }

    public function selectSegmentos(): array
    {
        return $this->select_all("SELECT id_segmento, nombre, descripcion FROM lgs_cat_segmentos WHERE activo = 2 ORDER BY id_segmento ASC") ?: [];
    }

    public function selectProveedores(): array
    {
        $sql = "SELECT DISTINCT
                    p.id_proveedor,
                    p.razon_social,
                    p.nombre_comercial
                FROM prv_cat_proveedores p
                INNER JOIN prv_rel_proveedores_actividades r ON p.id_proveedor = r.id_proveedor
                INNER JOIN prv_cat_actividades a ON a.id_actividad = r.id_actividad
                WHERE a.cve_actividad = 'TRASLADO_UNIDADES' 
                  AND p.deleted_at IS NULL
                ORDER BY p.razon_social ASC";
        return $this->select_all($sql) ?: [];
    }

    public function selectModelosVin(): array
    {
        $sql = "SELECT m.id_cat_modelo_vin, m.modelo, m.vin_base, m.id_segmento, s.nombre AS segmento, s.descripcion AS segmento_desc
                FROM cat_modelos_vin m
                LEFT JOIN lgs_cat_segmentos s ON m.id_segmento = s.id_segmento
                WHERE m.estado != 0
                ORDER BY m.id_segmento ASC, m.modelo ASC";
        return $this->select_all($sql) ?: [];
    }

    public function updateModeloSegmento(int $idModelo, ?int $idSegmento): bool
    {
        $sql = "UPDATE cat_modelos_vin SET id_segmento = ? WHERE id_cat_modelo_vin = $idModelo";
        return $this->update($sql, [$idSegmento]);
    }

    public function insertModeloVin(string $modelo, int $idSegmento, ?string $vinBase = null): bool
    {
        $chk = $this->select("SELECT id_cat_modelo_vin FROM cat_modelos_vin WHERE LOWER(TRIM(modelo)) = LOWER(TRIM(?)) LIMIT 1", [$modelo]);
        if (!empty($chk)) {
            $sql = "UPDATE cat_modelos_vin SET id_segmento = ?, vin_base = COALESCE(?, vin_base) WHERE id_cat_modelo_vin = ?";
            return $this->update($sql, [$idSegmento, $vinBase, $chk['id_cat_modelo_vin']]);
        }
        $sql = "INSERT INTO cat_modelos_vin (modelo, id_fabricante, id_tipo_vehiculo, peso_bruto_kg, id_tipo_motor, potencia_hp, distancia_ejes, id_cat_anio_vin, id_planta, id_segmento, vin_base, fecha_creacion, estado) VALUES (?, 1, 1, 12000, 1, 350, 4500, 1, 1, ?, ?, NOW(), 2)";
        return (bool)$this->insert($sql, [$modelo, $idSegmento, $vinBase]);
    }

}
