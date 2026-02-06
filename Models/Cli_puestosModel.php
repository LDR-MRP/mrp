<?php

class Cli_puestosModel extends Mysql
{
    public $intIdpuesto;
    public $intDepartamentoId;
    public $strNombre;
    public $strDescripcion;

    public function __construct()
    {
        parent::__construct();
    }

    public function selectPuestos()
    {
        $sql = "SELECT 
                p.id,
                d.nombre AS nombre_departamento,
                p.nombre AS nombre_puesto,
                p.descripcion,
                p.fecha_registro,
                p.estado,
                p.departamento_id
            FROM cli_puestos p
            INNER JOIN cli_departamentos d 
                ON p.departamento_id = d.id
            WHERE p.estado != 0";

        $request = $this->select_all($sql);
        return $request;
    }

    public function selectPuesto(int $idpuesto)
    {
        $this->intIdpuesto = $idpuesto;

        $sql = "SELECT 
                p.id,
                d.nombre AS nombre_departamento,
                p.nombre AS nombre_puesto,
                p.descripcion,
                p.fecha_registro,
                p.estado,
                p.departamento_id
            FROM cli_puestos p
            INNER JOIN cli_departamentos d 
                ON p.departamento_id = d.id
            WHERE p.id = $this->intIdpuesto
              AND p.estado != 0";

        $request = $this->select($sql);
        return $request;
    }

    public function deletePuesto(int $idpuesto)
    {
        $this->intIdpuesto = $idpuesto;
        $sql = "UPDATE cli_puestos SET estado = ? WHERE id = $this->intIdpuesto ";
        $arrData = array(0);
        $request = $this->update($sql, $arrData);
        return $request;
    }

    public function insertPuesto($departamento_id, $nombre, $descripcion)
    {
        $this->intDepartamentoId = $departamento_id;
        $this->strNombre = $nombre;
        $this->strDescripcion = $descripcion;

        $sql = "SELECT * FROM cli_puestos WHERE nombre = '{$this->strNombre}' OR descripcion = '{$this->strDescripcion}'";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $query_insert = "INSERT INTO cli_puestos(departamento_id, nombre, descripcion) VALUES(?,?,?)";
            $arrData = array(
                $this->intDepartamentoId,
                $this->strNombre,
                $this->strDescripcion
            );
            $request_insert = $this->insert($query_insert, $arrData);
            return $request_insert;
        } else {
            return "exist";
        }
    }

    public function updatePuesto($idpuesto, $departamento_id, $nombre, $descripcion)
    {
        $this->intIdpuesto = $idpuesto;
        $this->intDepartamentoId = $departamento_id;
        $this->strNombre = $nombre;
        $this->strDescripcion = $descripcion;

        $sql = "SELECT * FROM cli_puestos WHERE (nombre = '$this->strNombre' OR descripcion = '$this->strDescripcion') AND id != $this->intIdpuesto";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $sql = "UPDATE cli_puestos SET departamento_id = ?, nombre = ?, descripcion = ? WHERE id = $this->intIdpuesto ";
            $arrData = array(
                $this->intDepartamentoId,
                $this->strNombre,
                $this->strDescripcion
            );
            $request = $this->update($sql, $arrData);
            return $request;
        } else {
            return "exist";
        }
    }

    public function selectOptionDepartamentos()
    {
        $sql = "SELECT * FROM  cli_departamentos 
                    WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }
}
