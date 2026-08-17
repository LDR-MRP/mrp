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

    /**
     * Motor de cálculo: Recalcula costos basado en Madrina vs Chofer (Rodando)
     * Regla de Negocio:
     * - Madrina: Costo prorrateado/factorizado por la cantidad de VINs que van en esa madrina.
     * - Chofer: Costo directo por KM (Factor 1, 1 a 1).
     */
    public function recalcularCostoTotal(int $idEnvio, PDO $db = null): float {
        if ($db === null) {
            $db = $this->model->getConexion();
        }

        // 1. Obtener datos de la cabecera del envío
        $stmtEnvio = $db->prepare("SELECT id_tipo_traslado, id_proveedor, id_origen, id_destino, km_total FROM lgs_envios WHERE id_envio = :id");
        $stmtEnvio->execute(['id' => $idEnvio]);
        $envio = $stmtEnvio->fetch(PDO::FETCH_ASSOC);

        if (!$envio) return 0.0;

        $idTipoTraslado = (int)$envio['id_tipo_traslado'];
        $idProveedor    = (int)$envio['id_proveedor'];
        $idOrigen       = (int)($envio['id_origen'] ?? 0);
        $idDestino      = (int)($envio['id_destino'] ?? 0);
        $kmTotal        = (float)$envio['km_total'];

        // 2. Obtener VINs asociados
        $stmtVins = $db->prepare("
            SELECT v.id, v.id_unidad, v.id_madrina, v.id_chofer 
            FROM lgs_envios_vins v
            WHERE v.id_envio = :id
        ");
        $stmtVins->execute(['id' => $idEnvio]);
        $vins = $stmtVins->fetchAll(PDO::FETCH_ASSOC);

        if (empty($vins)) return 0.0;

        // Resolver segmentos de cada VIN dinámicamente
        foreach ($vins as &$vin) {
            $vin['id_segmento'] = $this->resolveSegmentoForUnit($db, (int)$vin['id_unidad']);
        }
        unset($vin);

        $costoTotalEnvio = 0.0;

        // TIPO 2: CHOFER (RODANDO)
        if ($idTipoTraslado === 2) {
            foreach ($vins as $vin) {
                // Buscamos tarifa de ruta para 1 unidad
                $tarifa = $this->getTarifaRuta($db, 2, $idOrigen, $idDestino, $vin['id_segmento'], 1, $idProveedor, $kmTotal);
                
                // Usamos la distancia de la ruta si está configurada, de lo contrario usamos la general
                $distancia = ($tarifa['km'] > 0) ? $tarifa['km'] : $kmTotal;
                $costoVin = ($distancia * $tarifa['costo_por_km'] + $tarifa['precio_plano']) * $tarifa['factor'];

                $this->updateCostoVin($db, $vin['id'], $costoVin);
                $costoTotalEnvio += $costoVin;
            }
        } 
        // TIPO 1: MADRINA
        else if ($idTipoTraslado === 1) {
            // Agrupar VINs por Madrina para saber el volumen (factor de ocupación)
            $vinsPorMadrina = [];
            foreach ($vins as $vin) {
                $idMadrina = $vin['id_madrina'] ?? 0;
                $vinsPorMadrina[$idMadrina][] = $vin;
            }

            foreach ($vinsPorMadrina as $idMadrina => $vinsMadrina) {
                $volumen = min(count($vinsMadrina), 15); // Tope máximo: 15 unidades por madrina
                
                foreach ($vinsMadrina as $vin) {
                    $tarifa = $this->getTarifaRuta($db, 1, $idOrigen, $idDestino, $vin['id_segmento'], $volumen, $idProveedor, $kmTotal);
                    
                    $distancia = ($tarifa['km'] > 0) ? $tarifa['km'] : $kmTotal;
                    $costoVin = ($distancia * $tarifa['costo_por_km'] + $tarifa['precio_plano']) * $tarifa['factor'];
                    
                    $this->updateCostoVin($db, $vin['id'], $costoVin);
                    $costoTotalEnvio += $costoVin;
                }
            }
        }

        // 3. Actualizar el Costo Total en la Cabecera
        $stmtUpdate = $db->prepare("UPDATE lgs_envios SET costo_total = :costo WHERE id_envio = :id");
        $stmtUpdate->execute(['costo' => $costoTotalEnvio, 'id' => $idEnvio]);

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
        if (strpos($modeloLower, 'miller') !== false || strpos($modeloLower, 's3') !== false || strpos($modeloLower, 's5') !== false || strpos($modeloLower, 's6') !== false) {
            return 1; // LIGEROS
        }
        if (strpos($modeloLower, 's8') !== false || strpos($modeloLower, 's12') !== false || strpos($modeloLower, 's20') !== false) {
            return 2; // MEDIANO
        }
        if (strpos($modeloLower, 'est') !== false || strpos($modeloLower, 'galaxy') !== false || strpos($modeloLower, 's35') !== false || strpos($modeloLower, 's38') !== false || strpos($modeloLower, 'isg') !== false) {
            return 3; // PESADO
        }
        if (strpos($modeloLower, 'auv') !== false || strpos($modeloLower, 'araña') !== false || strpos($modeloLower, 'bus') !== false) {
            return 4; // BUSES
        }
        if (strpos($modeloLower, 'lowboy') !== false) {
            return 5; // LOWBOY
        }

        return 1; // Default LIGEROS
    }

    /**
     * Helper: Busca la tarifa por Ruta -> Transporte -> Segmento -> Rango de VINs
     */
    private function getTarifaRuta(PDO $db, int $idTipoTraslado, int $idOrigen, int $idDestino, int $idSegmento, int $volumenVins, int $idProveedor, float $kmTotal): array {
        $sql = "SELECT km, costo_por_km, precio_plano, factor 
                FROM lgs_costos_rutas 
                WHERE id_tipo_traslado = :id_tipo_traslado 
                  AND id_origen = :id_origen 
                  AND id_destino = :id_destino 
                  AND id_segmento = :id_segmento
                  AND :volumen BETWEEN num_vins_min AND num_vins_max
                  AND activo = 2
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id_tipo_traslado' => $idTipoTraslado,
            'id_origen'        => $idOrigen,
            'id_destino'       => $idDestino,
            'id_segmento'      => $idSegmento,
            'volumen'          => $volumenVins
        ]);
        
        $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tarifa) {
            return [
                'km'           => (float)$tarifa['km'],
                'costo_por_km' => (float)$tarifa['costo_por_km'],
                'precio_plano' => (float)$tarifa['precio_plano'],
                'factor'       => (float)$tarifa['factor']
            ];
        }
        
        // Fallback: Si no hay tarifa de ruta configurada, buscar en la tarifa global de proveedor/segmento
        $tarifaProv = $this->getTarifaProveedor($db, $idProveedor, $idSegmento, $volumenVins);
        
        return [
            'km'           => $kmTotal,
            'costo_por_km' => $tarifaProv['costo_por_km'],
            'precio_plano' => 0.0,
            'factor'       => $tarifaProv['factor']
        ];
    }

    /**
     * Helper: Busca la tarifa en la matriz de costos del proveedor (fallback)
     */
    private function getTarifaProveedor(PDO $db, int $idProveedor, ?int $idSegmento, int $volumenVins): array {
        $sql = "SELECT costo_por_km, factor 
                FROM lgs_costos_proveedor_segmento 
                WHERE id_proveedor = :id_proveedor 
                  AND (id_segmento = :id_segmento OR id_segmento IS NULL)
                  AND :volumen BETWEEN num_vins_min AND num_vins_max
                ORDER BY id_segmento DESC 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id_proveedor' => $idProveedor,
            'id_segmento'  => $idSegmento,
            'volumen'      => $volumenVins
        ]);
        
        $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tarifa) {
            return ['costo_por_km' => 0.0, 'factor' => 1.0];
        }
        
        return [
            'costo_por_km' => (float)$tarifa['costo_por_km'],
            'factor'       => (float)$tarifa['factor']
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
