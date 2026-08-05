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
        $stmtEnvio = $db->prepare("SELECT id_tipo_traslado, id_proveedor, km_total FROM lgs_envios WHERE id_envio = :id");
        $stmtEnvio->execute(['id' => $idEnvio]);
        $envio = $stmtEnvio->fetch(PDO::FETCH_ASSOC);

        if (!$envio) return 0.0;

        $idTipoTraslado = (int)$envio['id_tipo_traslado'];
        $idProveedor    = (int)$envio['id_proveedor'];
        $kmTotal        = (float)$envio['km_total'];

        // 2. Obtener VINs asociados
        $stmtVins = $db->prepare("
            SELECT v.id, v.id_unidad, v.id_madrina, v.id_chofer, u.id_segmento 
            FROM lgs_envios_vins v
            LEFT JOIN mrp_unidades_terminadas u ON v.id_unidad = u.id_unidad
            WHERE v.id_envio = :id
        ");
        $stmtVins->execute(['id' => $idEnvio]);
        $vins = $stmtVins->fetchAll(PDO::FETCH_ASSOC);

        if (empty($vins)) return 0.0;

        $costoTotalEnvio = 0.0;

        // TIPO 2: CHOFER (RODANDO)
        if ($idTipoTraslado === 2) {
            foreach ($vins as $vin) {
                // Buscamos tarifa plana (1 VIN) para el chofer de este proveedor
                $tarifa = $this->getTarifaProveedor($db, $idProveedor, $vin['id_segmento'], 1);
                $costoVin = $kmTotal * $tarifa['costo_por_km'];

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
                $volumen = count($vinsMadrina);
                
                foreach ($vinsMadrina as $vin) {
                    $tarifa = $this->getTarifaProveedor($db, $idProveedor, $vin['id_segmento'], $volumen);
                    
                    // Fórmula Madrina: KM * Costo Base * Factor por Volumen
                    $costoVin = $kmTotal * $tarifa['costo_por_km'] * $tarifa['factor'];
                    
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
     * Helper: Busca la tarifa en la matriz de costos del proveedor
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
        
        // Si no hay tarifa configurada, evitamos que el costo se vaya a cero absoluto si no se desea,
        // pero por defecto devolvemos 0 para alertar que falta configuración comercial.
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
