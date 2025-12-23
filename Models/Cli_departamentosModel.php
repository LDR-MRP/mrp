<?php

class Cli_departamentosModel extends Mysql
{
    public $intIddepartamento;
    public $strNombre;
    public $strDescripcion;
    public $intEstado;

    public function __construct()
    {
        parent::__construct();
    }

    public function selectDepartamentos()
    {
        $sql = "SELECT * FROM  cli_departamentos 
					WHERE estado != 0 ";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectDepartamento(int $iddepartamento)
    {
        $this->intIddepartamento = $iddepartamento;
        $sql = "SELECT * FROM cli_departamentos WHERE id = $this->intIddepartamento";
        $request = $this->select($sql);
        return $request;
    }

    public function deleteDepartamento(int $iddepartamento)
    {
        $this->intIddepartamento = $iddepartamento;
        $sql = "UPDATE cli_departamentos SET estado = ? WHERE id = $this->intIddepartamento ";
        $arrData = array(0);
        $request = $this->update($sql, $arrData);
        return $request;
    }

    public function insertDepartamento($nombre, $descripcion, $estado)
    {
        $this->strNombre = $nombre;
        $this->strDescripcion = $descripcion;
        $this->intEstado = $estado;

        $sql = "SELECT * FROM cli_departamentos WHERE nombre = '{$this->strNombre}' OR descripcion = '{$this->strDescripcion}'";
        $request = $this->select_all($sql);

        if (empty($request)) {

            $query_insert = "INSERT INTO cli_departamentos(nombre, descripcion, estado) VALUES(?,?,?)";
            $arrData = array(
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

    public function updateDepartamento($iddepartamento, $nombre, $descripcion, $estado)
    {
        $this->intIddepartamento = $iddepartamento;
        $this->strNombre = $nombre;
        $this->strDescripcion = $descripcion;
        $this->intEstado = $estado;

        $sql = "SELECT * FROM cli_departamentos WHERE (nombre = '$this->strNombre' OR descripcion = '$this->strDescripcion') AND id != $this->intIddepartamento";
        $request = $this->select_all($sql);
        if (empty($request)) {
            $sql = "UPDATE cli_departamentos SET nombre = ?, descripcion = ?, estado = ? WHERE id = $this->intIddepartamento ";
            $arrData = array(
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
    

}
