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
                    u.id_cliente,
                    u.id_sucursal_cli,
                    u.id_direccion_cli,
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
                    u.id_cliente,
                    u.id_sucursal_cli,
                    u.id_direccion_cli,
                    u.lat, 
                    u.lng 
                FROM {$this->table} u
                LEFT JOIN lgs_cat_tipo_destino td ON u.id_tipo_destino = td.id_tipo_destino
                WHERE u.id_ubicacion = ?";
                
        $request = $this->select($sql, [$idUbicacion]);
        return $request ?: [];
    }

    /**
     * Obtiene todos los distribuidores activos (deduplicados por nombre normalizado).
     */
    public function getDistribuidores(): array
    {
        $hasGrupoCol = false;
        try {
            $chk = $this->select("SHOW COLUMNS FROM cli_clientes LIKE 'grupo_comercial'");
            $hasGrupoCol = !empty($chk);
        } catch (Throwable $e) {
            $hasGrupoCol = false;
        }
        $grupoSelect = $hasGrupoCol ? "MAX(COALESCE(NULLIF(TRIM(c.grupo_comercial), ''), '')) AS grupo_comercial," : "'' AS grupo_comercial,";

        $sql = "SELECT 
                    g.id_distribuidor, 
                    g.nombre, 
                    g.clave,
                    g.id_cliente,
                    {$grupoSelect}
                    COUNT(DISTINCT u.id_ubicacion) AS total_sedes
                FROM (
                    SELECT 
                        MIN(id_distribuidor) AS id_distribuidor,
                        MAX(TRIM(nombre)) AS nombre,
                        MAX(clave) AS clave,
                        MAX(id_cliente) AS id_cliente,
                        UPPER(TRIM(nombre)) AS nombre_norm
                    FROM lgs_cat_distribuidores
                    WHERE activo = 1
                    GROUP BY UPPER(TRIM(nombre))
                ) g
                LEFT JOIN cli_clientes c
                    ON c.idcliente = g.id_cliente
                LEFT JOIN lgs_cat_distribuidores d_all 
                    ON UPPER(TRIM(d_all.nombre)) = g.nombre_norm AND d_all.activo = 1
                LEFT JOIN {$this->table} u 
                    ON u.id_distribuidor = d_all.id_distribuidor AND u.activo = 1
                WHERE (g.id_cliente IS NULL OR c.estado = 2)
                GROUP BY g.id_distribuidor, g.nombre, g.clave, g.id_cliente
                ORDER BY g.nombre ASC";

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
                    u.id_cliente,
                    u.id_sucursal_cli,
                    u.id_direccion_cli,
                    u.lat, 
                    u.lng 
                FROM {$this->table} u
                WHERE u.activo = 1
                  AND (
                      u.id_distribuidor IN (
                          SELECT d2.id_distribuidor
                          FROM lgs_cat_distribuidores d1
                          INNER JOIN lgs_cat_distribuidores d2
                              ON UPPER(TRIM(d1.nombre)) = UPPER(TRIM(d2.nombre))
                          WHERE d1.id_distribuidor = ?
                      )
                      OR u.id_cliente IN (
                          SELECT d3.id_cliente
                          FROM lgs_cat_distribuidores d3
                          WHERE d3.id_distribuidor = ? AND d3.id_cliente IS NOT NULL
                      )
                  )
                ORDER BY u.nombre ASC";

        $request = $this->select_all($sql, [$idDistribuidor, $idDistribuidor]);
        return $request ?: [];
    }

    /**
     * Obtiene las sedes (ubicaciones activas) pertenecientes estrictamente a un cliente (`cli_clientes.idcliente`).
     */
    public function getSedesByCliente(int $idCliente): array
    {
        if ($idCliente <= 0) {
            return [];
        }
        $sql = "SELECT 
                    u.id_ubicacion AS id,
                    u.nombre,
                    u.direccion,
                    u.id_tipo_destino,
                    u.id_distribuidor,
                    u.id_cliente,
                    u.id_sucursal_cli,
                    u.id_direccion_cli,
                    u.lat,
                    u.lng
                FROM {$this->table} u
                LEFT JOIN lgs_cat_distribuidores d ON d.id_distribuidor = u.id_distribuidor
                WHERE u.activo = 1
                  AND (u.id_cliente = ? OR d.id_cliente = ?)
                ORDER BY u.nombre ASC";
        $request = $this->select_all($sql, [$idCliente, $idCliente]);
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
                    u.id_cliente,
                    u.id_sucursal_cli,
                    u.id_direccion_cli,
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
