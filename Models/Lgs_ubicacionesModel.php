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
                    u.lat, 
                    u.lng 
                FROM {$this->table} u
                LEFT JOIN lgs_cat_tipo_destino td ON u.id_tipo_destino = td.id_tipo_destino
                WHERE u.id_ubicacion = ?";
                
        $request = $this->select($sql, [$idUbicacion]);
        return $request ?: [];
    }
}
