<?php

class Lgs_panelrutasModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_envios';

    public function getTableName(): string {
        return $this->table;
    }

    public function getConexion(): PDO {
        return $this->conexion;
    }

    /**
     * Obtiene los envíos activos en tránsito (Estado 6) con coordenadas GPS de origen y destino
     */
    public function getRutasEnTransito(?int $plantaId = null): array
    {
        $wherePlanta = "";
        $params = [];
        if ($plantaId !== null && $plantaId > 0) {
            $wherePlanta = " AND e.id_origen = :planta_id ";
            $params['planta_id'] = $plantaId;
        }

        $sql = "SELECT 
                    e.id_envio,
                    e.folio,
                    e.fecha_salida_real,
                    e.km_total,
                    e.costo_total,
                    e.observaciones,
                    o.nombre AS origen_nombre,
                    o.lat AS origen_lat,
                    o.lng AS origen_lng,
                    pr.razon_social AS trasladista,
                    tt.nombre AS tipo_traslado,
                    p.id_planeacion,
                    COALESCE(p.folio, '') AS planeacion_folio,
                    COALESCE(p.descripcion, '') AS planeacion_desc,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio AND (fecha_entrega_real IS NOT NULL OR estado_unidad_fisico = 'ENTREGADO')) AS vins_entregados
                FROM lgs_envios e
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                LEFT JOIN lgs_planeaciones_envios pe ON e.id_envio = pe.id_envio
                LEFT JOIN lgs_planeaciones p ON pe.id_planeacion = p.id_planeacion
                WHERE e.id_estado = 6 AND e.deleted_at IS NULL {$wherePlanta}
                ORDER BY e.id_envio DESC";
        
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtiene los VINs y destinos individuales de una ruta
     */
    public function getDetalleDestinosRuta(int $idEnvio): array
    {
        $sql = "SELECT 
                    ev.id,
                    ev.id_unidad,
                    COALESCE(NULLIF(u.vin, ''), ut.clave, '') AS vin,
                    COALESCE(NULLIF(u.modelo, ''), ut.num_unidad, '') AS modelo,
                    ev.posicion_acomodo,
                    COALESCE(ev.estado_unidad_fisico, 'EN_ENTREGAS') AS estado_unidad_fisico,
                    ev.fecha_entrega_real,
                    ev.recibe_nombre,
                    COALESCE(
                        NULLIF(c_parada.nombre_comercial, ''),
                        c_parada.razon_social,
                        d_parada.nombre,
                        ep.destino_nombre_libre,
                        NULLIF(c_vin.nombre_comercial, ''),
                        c_vin.razon_social,
                        d_vin.nombre,
                        ev.destino_nombre_libre,
                        NULLIF(c_envio.nombre_comercial, ''),
                        c_envio.razon_social,
                        d_envio.nombre,
                        e.destino_nombre_libre,
                        (SELECT COALESCE(NULLIF(c_p1.nombre_comercial, ''), c_p1.razon_social, d_p1.nombre, ep1.destino_nombre_libre)
                         FROM lgs_envios_paradas ep1 
                         LEFT JOIN lgs_cat_destinos d_p1 ON ep1.id_destino_cat = d_p1.id_destino 
                         LEFT JOIN cli_clientes c_p1 ON ep1.id_destino_cat = c_p1.idcliente 
                         WHERE ep1.id_envio = ev.id_envio 
                         ORDER BY ep1.orden ASC LIMIT 1),
                        'Destino no especificado'
                    ) AS destino_nombre,
                    COALESCE(
                        d_parada.lat,
                        d_vin.lat, 
                        d_envio.lat,
                        (SELECT d_p1.lat FROM lgs_envios_paradas ep1 LEFT JOIN lgs_cat_destinos d_p1 ON ep1.id_destino_cat = d_p1.id_destino WHERE ep1.id_envio = ev.id_envio AND d_p1.lat IS NOT NULL ORDER BY ep1.orden ASC LIMIT 1)
                    ) AS destino_lat,
                    COALESCE(
                        d_parada.lng,
                        d_vin.lng, 
                        d_envio.lng,
                        (SELECT d_p1.lng FROM lgs_envios_paradas ep1 LEFT JOIN lgs_cat_destinos d_p1 ON ep1.id_destino_cat = d_p1.id_destino WHERE ep1.id_envio = ev.id_envio AND d_p1.lng IS NOT NULL ORDER BY ep1.orden ASC LIMIT 1)
                    ) AS destino_lng,
                    ep.orden AS orden_parada
                FROM lgs_envios_vins ev
                INNER JOIN lgs_envios e ON ev.id_envio = e.id_envio
                LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                LEFT JOIN lgs_envios_paradas ep ON ev.id_parada = ep.id_parada
                LEFT JOIN lgs_cat_destinos d_parada ON ep.id_destino_cat = d_parada.id_destino
                LEFT JOIN cli_clientes c_parada ON ep.id_destino_cat = c_parada.idcliente
                LEFT JOIN lgs_cat_destinos d_vin ON ev.id_destino = d_vin.id_destino
                LEFT JOIN cli_clientes c_vin ON ev.id_destino = c_vin.idcliente
                LEFT JOIN lgs_cat_destinos d_envio ON e.id_destino = d_envio.id_destino
                LEFT JOIN cli_clientes c_envio ON e.id_destino = c_envio.idcliente
                WHERE ev.id_envio = :id_envio
                ORDER BY COALESCE(ep.orden, 999) ASC, ev.posicion_acomodo ASC, ev.id ASC";
        
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->execute(['id_envio' => $idEnvio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtiene las paradas intermedias / destinos del viaje
     */
    public function getParadasRuta(int $idEnvio): array
    {
        $sql = "SELECT 
                    ep.id_parada,
                    ep.orden,
                    COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social, d.nombre, ep.destino_nombre_libre, 'Parada') AS destino_nombre,
                    ep.km_tramo,
                    ep.observaciones,
                    d.lat,
                    d.lng
                FROM lgs_envios_paradas ep
                LEFT JOIN lgs_cat_destinos d ON ep.id_destino_cat = d.id_destino
                LEFT JOIN cli_clientes c ON ep.id_destino_cat = c.idcliente
                WHERE ep.id_envio = :id_envio
                ORDER BY ep.orden ASC";
        
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->execute(['id_envio' => $idEnvio]);
        $paradas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fallback: Si no tiene tabla de paradas explícita, construir paradas a partir de los destinos distintos de los VINs
        if (empty($paradas)) {
            $sqlVinsDest = "SELECT 
                                COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social, d.nombre, ev.destino_nombre_libre, 'Destino') AS destino_nombre,
                                d.lat,
                                d.lng,
                                COUNT(ev.id_unidad) AS total_vins_parada
                            FROM lgs_envios_vins ev
                            LEFT JOIN lgs_cat_destinos d ON ev.id_destino = d.id_destino
                            LEFT JOIN cli_clientes c ON ev.id_destino = c.idcliente
                            WHERE ev.id_envio = :id_envio
                            GROUP BY destino_nombre, d.lat, d.lng";
            $stmtV = $this->getConexion()->prepare($sqlVinsDest);
            $stmtV->execute(['id_envio' => $idEnvio]);
            $rows = $stmtV->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $i = 1;
            foreach ($rows as $r) {
                $paradas[] = [
                    'id_parada' => $i,
                    'orden' => $i,
                    'destino_nombre' => $r['destino_nombre'],
                    'km_tramo' => 0,
                    'observaciones' => "Entrega de {$r['total_vins_parada']} unidad(es)",
                    'lat' => $r['lat'],
                    'lng' => $r['lng']
                ];
                $i++;
            }
        }

        return $paradas;
    }
}
