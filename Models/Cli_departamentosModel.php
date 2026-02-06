<?php

class Cli_departamentosModel extends Mysql
{
    public $intIddepartamento;
    public $strNombre;
    public $strDescripcion;

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

    public function insertDepartamento($nombre, $descripcion)
    {
        $this->strNombre = $nombre;
        $this->strDescripcion = $descripcion;

        $sql = "SELECT * FROM cli_departamentos WHERE nombre = '{$this->strNombre}' OR descripcion = '{$this->strDescripcion}'";
        $request = $this->select_all($sql);

        if (empty($request)) {

            $query_insert = "INSERT INTO cli_departamentos(nombre, descripcion) VALUES(?,?)";
            $arrData = array(
                $this->strNombre,
                $this->strDescripcion,
            );
            $request_insert = $this->insert($query_insert, $arrData);
            return $request_insert;
        } else {
            return "exist";
        }
    }

    public function updateDepartamento($iddepartamento, $nombre, $descripcion)
    {
        $this->intIddepartamento = $iddepartamento;
        $this->strNombre = $nombre;
        $this->strDescripcion = $descripcion;

        $sql = "SELECT * FROM cli_departamentos WHERE (nombre = '$this->strNombre' OR descripcion = '$this->strDescripcion') AND id != $this->intIddepartamento";
        $request = $this->select_all($sql);
        if (empty($request)) {
            $sql = "UPDATE cli_departamentos SET nombre = ?, descripcion = ? WHERE id = $this->intIddepartamento ";
            $arrData = array(
                $this->strNombre,
                $this->strDescripcion,
            );
            $request = $this->update($sql, $arrData);
            return $request;
        } else {
            return "exist";
        }
    }
}
