<?php

class Cli_puestosModel extends Mysql
{
    public $intIdpuesto;
    public $intDepartamentoId;
    public $strNombre;
    public $strDescripcion;
    public $intEstado;

    public function __construct()
    {
        parent::__construct();
    }

    public function selectPuestos()
    {
        $sql = "SELECT * FROM  cli_puestos 
					WHERE estado != 0 ";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectPuesto(int $idpuesto)
    {
        $this->intIdpuesto = $idpuesto;
        $sql = "SELECT * FROM cli_puestos WHERE id = $this->intIdpuesto";
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

    public function insertPuesto($departamento_id, $nombre, $descripcion, $estado)
    {
        $this->intDepartamentoId = $departamento_id;
        $this->strNombre = $nombre;
        $this->strDescripcion = $descripcion;
        $this->intEstado = $estado;

        $sql = "SELECT * FROM cli_puestos WHERE nombre = '{$this->strNombre}' OR descripcion = '{$this->strDescripcion}'";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $query_insert = "INSERT INTO cli_puestos(departamento_id, nombre, descripcion, estado) VALUES(?,?,?,?)";
            $arrData = array(
                $this->intDepartamentoId,
                $this->strNombre,
                $this->strDescripcion,
                $this->intEstado
            );
            $request_insert = $this->insert($query_insert, $arrData);
            return $request_insert;
        } else {
            return "exist";
        }
    }

    public function updatePuesto($idpuesto, $departamento_id, $nombre, $descripcion, $estado)
    {
        $this->intIdpuesto = $idpuesto;
        $this->intDepartamentoId = $departamento_id;
        $this->strNombre = $nombre;
        $this->strDescripcion = $descripcion;
        $this->intEstado = $estado;

        $sql = "SELECT * FROM cli_puestos WHERE (nombre = '$this->strNombre' OR descripcion = '$this->strDescripcion') AND id != $this->intIdpuesto";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $sql = "UPDATE cli_puestos SET departamento_id = ?, nombre = ?, descripcion = ?, estado = ? WHERE id = $this->intIdpuesto ";
            $arrData = array(
                $this->intDepartamentoId,
                $this->strNombre,
                $this->strDescripcion,
                $this->intEstado
            );
            $request = $this->update($sql, $arrData);
            return $request;
        } else {
            return "exist";
        }
    }

    public function selectOptionPuestos()
    {
        $sql = "SELECT * FROM  cli_puestos 
                    WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }
}
