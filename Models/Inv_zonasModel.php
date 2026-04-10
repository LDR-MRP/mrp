<?php

class Inv_zonasModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    // INSERT
    public function inserZona($cve_zona, $descripcion, $sedeid, $fecha, $estado)
    {
        // Validar duplicado por clave dentro de la misma sede
        $sql = "SELECT * FROM wms_zonas 
                WHERE cve_zona = ? 
                AND sedeid = ?";
        $request = $this->select_all($sql, [$cve_zona, $sedeid]);

        if (!empty($request)) {
            return "exist";
        }

        $query_insert = "INSERT INTO wms_zonas
                        (sedeid, cve_zona, descripcion, fecha_creacion, estado)
                        VALUES (?,?,?,?,?)";

        return $this->insert($query_insert, [
            $sedeid,
            $cve_zona,
            $descripcion,
            $fecha,
            $estado
        ]);
    }

    // SELECT LISTADO
    public function selectZonas()
    {
        $sql = "SELECT 
                    z.idzona,
                    z.cve_zona,
                    z.descripcion,
                    s.descripcion AS sede,
                    z.fecha_creacion,
                    z.estado
                FROM wms_zonas z
                INNER JOIN wms_sedes s ON z.sedeid = s.idsede
                WHERE z.estado != 0
                ORDER BY z.descripcion";

        return $this->select_all($sql);
    }

    // SELECT UNA
    public function selectZona(int $idzona)
    {
        $sql = "SELECT * FROM wms_zonas WHERE idzona = ?";
        return $this->select($sql, [$idzona]);
    }

    // DELETE (lógico)
    public function deleteZona(int $idzona)
    {
        $sql = "UPDATE wms_zonas SET estado = 0 WHERE idzona = ?";
        return $this->update($sql, [$idzona]);
    }

    // UPDATE
    public function updateZona($idzona, $cve_zona, $descripcion, $sedeid, $estado)
    {
        // Validar duplicado
        $sql = "SELECT * FROM wms_zonas 
                WHERE cve_zona = ? 
                AND sedeid = ?
                AND idzona != ?";
        $request = $this->select_all($sql, [
            $cve_zona,
            $sedeid,
            $idzona
        ]);

        if (!empty($request)) {
            return "exist";
        }

        $sql = "UPDATE wms_zonas SET
                    cve_zona = ?,
                    descripcion = ?,
                    sedeid = ?,
                    estado = ?
                WHERE idzona = ?";

        return $this->update($sql, [
            $cve_zona,
            $descripcion,
            $sedeid,
            $estado,
            $idzona
        ]);
    }

    // SELECT PARA COMBO
    public function selectOptionZonas($sedeid = 0)
    {
        $sql = "SELECT * FROM wms_zonas WHERE estado = 2";

        if ($sedeid > 0) {
            $sql .= " AND sedeid = $sedeid";
        }

        return $this->select_all($sql);
    }
}