<?php

class Cli_marcasModel extends Mysql
{
    public $intIdmarca;
    public $strNombre;
    public $strCodigo;

    public function __construct()
    {
        parent::__construct();
    }

    public function selectMarcas()
    {
        $sql = "SELECT * FROM  cli_marcas 
					WHERE estado != 0 ";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectMarca(int $idmarca)
    {
        $this->intIdmarca = $idmarca;
        $sql = "SELECT * FROM cli_marcas WHERE id = $this->intIdmarca";
        $request = $this->select($sql);
        return $request;
    }

    public function deleteMarca(int $idmarca)
    {
        $this->intIdmarca = $idmarca;
        $sql = "UPDATE cli_marcas SET estado = ? WHERE id = $this->intIdmarca ";
        $arrData = array(0);
        $request = $this->update($sql, $arrData);
        return $request;
    }

    public function insertMarca($marca, $codigo)
    {
        $this->strNombre = $marca;
        $this->strCodigo = $codigo;

        $sql = "SELECT * FROM cli_marcas WHERE nombre = '{$this->strNombre}' OR codigo = '{$this->strCodigo}'";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $query_insert = "INSERT INTO cli_marcas(nombre, codigo) VALUES(?,?)";
            $arrData = array(
                $this->strNombre,
                $this->strCodigo,
            );
            $request_insert = $this->insert($query_insert, $arrData);
            return $request_insert;
        } else {
            return "exist";
        }
    }

    public function updateMarca($idmarca, $marca, $codigo)
    {
        $this->intIdmarca = $idmarca;
        $this->strNombre = $marca;
        $this->strCodigo = $codigo;

        $sql = "SELECT * FROM cli_marcas 
        WHERE (nombre = '$this->strNombre' 
        OR codigo = '$this->strCodigo') 
        AND id != $this->intIdmarca";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $sql = "UPDATE cli_marcas 
            SET nombre = ?, 
                codigo = ? 
            WHERE id = $this->intIdmarca ";
            $arrData = array(
                $this->strNombre,
                $this->strCodigo,
            );
            $request = $this->update($sql, $arrData);
            return $request;
        } else {
            return "exist";
        }
    }
}
