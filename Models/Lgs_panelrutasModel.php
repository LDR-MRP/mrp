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
    public function getRutasEnTransito(): array
    {
        $sql = "SELECT 
                    e.id_envio,
                    e.folio,
                    e.fecha_salida_real,
                    e.km_total,
                    e.costo_total,
                    o.nombre AS origen_nombre,
                    o.latitud AS origen_lat,
                    o.longitud AS origen_lng,
                    pr.razon_social AS trasladista,
                    tt.nombre AS tipo_traslado,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins
                FROM lgs_envios e
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                WHERE e.id_estado = 6 AND e.deleted_at IS NULL
                ORDER BY e.id_envio DESC";
        
        return $this->select_all($sql);
    }

    /**
     * Obtiene los VINs y destinos individuales de una ruta
     */
    public function getDetalleDestinosRuta(int $idEnvio): array
    {
        $sql = "SELECT 
                    ev.id_unidad,
                    u.vin,
                    u.modelo,
                    d.nombre AS destino_nombre,
                    d.latitud AS destino_lat,
                    d.longitud AS destino_lng
                FROM lgs_envios_vins ev
                INNER JOIN mrp_unidades_terminadas u ON ev.id_unidad = u.id_unidad
                LEFT JOIN lgs_cat_destinos d ON ev.id_destino = d.id_destino
                WHERE ev.id_envio = :id_envio";
        
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->execute(['id_envio' => $idEnvio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
