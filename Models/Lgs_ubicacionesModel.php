<?php

class Lgs_ubicacionesModel extends Mysql
{
    protected string $table = 'lgs_cat_ubicaciones';

    public function getTableName(): string {
        return $this->table;
    }

    public function getConexion(): PDO {
        return $this->conexion;
    }

    /**
     * Obtiene todas las ubicaciones activas.
     */
    public function getUbicaciones(): array
    {
        $sql = "SELECT 
                    u.id_ubicacion AS id, 
                    u.nombre, 
                    u.direccion, 
                    u.id_tipo_destino, 
                    td.descripcion AS tipo_destino,
                    u.id_distribuidor,
                    u.lat, 
                    u.lng 
                FROM {$this->table} u
                LEFT JOIN lgs_cat_tipo_destino td ON u.id_tipo_destino = td.id_tipo_destino
                WHERE u.activo = 1 
                ORDER BY u.nombre ASC";
                
        $request = $this->select_all($sql);
        return $request ?: [];
    }

    /**
     * Obtiene una ubicación por su ID.
     */
    public function getUbicacion(int $idUbicacion): array
    {
        $sql = "SELECT 
                    u.id_ubicacion AS id, 
                    u.nombre, 
                    u.direccion, 
                    u.id_tipo_destino, 
                    td.descripcion AS tipo_destino,
                    u.id_distribuidor,
                    u.lat, 
                    u.lng 
                FROM {$this->table} u
                LEFT JOIN lgs_cat_tipo_destino td ON u.id_tipo_destino = td.id_tipo_destino
                WHERE u.id_ubicacion = ?";
                
        $request = $this->select($sql, [$idUbicacion]);
        return $request ?: [];
    }

    /**
     * Obtiene todos los distribuidores activos.
     */
    public function getDistribuidores(): array
    {
        $sql = "SELECT 
                    d.id_distribuidor, 
                    d.nombre, 
                    d.clave,
                    COUNT(u.id_ubicacion) AS total_sedes
                FROM lgs_cat_distribuidores d
                LEFT JOIN {$this->table} u ON u.id_distribuidor = d.id_distribuidor AND u.activo = 1
                WHERE d.activo = 1
                GROUP BY d.id_distribuidor
                ORDER BY d.nombre ASC";

        $request = $this->select_all($sql);
        return $request ?: [];
    }

    /**
     * Obtiene las sedes (ubicaciones) de un distribuidor específico.
     */
    public function getSedesByDistribuidor(int $idDistribuidor): array
    {
        $sql = "SELECT 
                    u.id_ubicacion AS id, 
                    u.nombre, 
                    u.direccion, 
                    u.id_tipo_destino,
                    u.id_distribuidor,
                    u.lat, 
                    u.lng 
                FROM {$this->table} u
                WHERE u.id_distribuidor = ? AND u.activo = 1
                ORDER BY u.nombre ASC";

        $request = $this->select_all($sql, [$idDistribuidor]);
        return $request ?: [];
    }

    /**
     * Obtiene todas las ubicaciones activas con info de distribuidor incluida.
     */
    public function getUbicacionesConSedes(): array
    {
        $sql = "SELECT 
                    u.id_ubicacion AS id, 
                    u.nombre, 
                    u.direccion, 
                    u.id_tipo_destino, 
                    td.descripcion AS tipo_destino,
                    u.id_distribuidor,
                    d.nombre AS distribuidor_nombre,
                    u.lat, 
                    u.lng 
                FROM {$this->table} u
                LEFT JOIN lgs_cat_tipo_destino td ON u.id_tipo_destino = td.id_tipo_destino
                LEFT JOIN lgs_cat_distribuidores d ON u.id_distribuidor = d.id_distribuidor
                WHERE u.activo = 1 
                ORDER BY u.nombre ASC";
                
        $request = $this->select_all($sql);
        return $request ?: [];
    }
}
