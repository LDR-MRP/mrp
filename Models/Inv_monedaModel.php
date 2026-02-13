<?php

class Inv_monedaModel extends Mysql
{
    public $intidmoneda;
    public $strClave;
    public $strCvePrecio;
    public $strFecha;
    public $intEstatus;
    public $strDescripcion;
    public $strSimbolo;
    public $strtipoCambio;


    public function __construct()
    {
        parent::__construct();
    }





    public function inserMoneda($cve_moneda, $descripcion, $simbolo, $cambio_moneda, $fecha_creacion, $intEstatus)
    {
        $this->strCvePrecio = $cve_moneda;
        $this->strDescripcion = $descripcion;
        $this->strSimbolo = $simbolo;
        $this->strFecha = $fecha_creacion;
        $this->strtipoCambio = $cambio_moneda;
        $this->intEstatus = $intEstatus;

        $sql = "SELECT * FROM wms_moneda WHERE cve_moneda = '{$this->strCvePrecio}'";
        $request = $this->select_all($sql);

        if (empty($request)) {

            $query_insert = "INSERT INTO wms_moneda(cve_moneda, descripcion, simbolo, tipo_cambio, fecha_creacion, estado) 
                         VALUES(?,?,?,?,?,?)";

            $arrData = array(
                $this->strCvePrecio,
                $this->strDescripcion,
                $this->strSimbolo,
                $this->strtipoCambio,
                $this->strFecha,
                $this->intEstatus
            );

            return $this->insert($query_insert, $arrData);
        }

        return "exist";
    }


    public function selectMonedas()
    {
        $sql = "SELECT 
                idmoneda,
                cve_moneda,
                descripcion,
                simbolo,
                tipo_cambio,
                fecha_creacion,
                estado
            FROM wms_moneda
            ORDER BY descripcion";
        return $this->select_all($sql);
    }


    public function selectOptionPrecios()
    {
        $sql = "SELECT * FROM  wms_precios 
					WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectMoneda(int $idmoneda)
    {
        $this->intidmoneda = $idmoneda;
        $sql = "SELECT * FROM wms_moneda WHERE idmoneda = $this->intidmoneda";
        return $this->select($sql);
    }


    public function deleteoneda(int $idmoneda)
    {
        $this->intidmoneda = $idmoneda;
        $sql = "UPDATE wms_moneda SET estado = ? WHERE idmoneda = $this->intidmoneda";
        return $this->update($sql, [0]);
    }



    public function updateMoneda($idmoneda, $cve_moneda, $descripcion, $simbolo, $cambio_moneda, $estado)
    {
        $this->intidmoneda = $idmoneda;
        $this->strCvePrecio = $cve_moneda;
        $this->strDescripcion = $descripcion;
        $this->strSimbolo = $simbolo;
        $this->strtipoCambio = $cambio_moneda;
        $this->intEstatus = $estado;

        $sql = "SELECT * FROM wms_moneda 
            WHERE cve_moneda = '{$this->strCvePrecio}' 
            AND idmoneda != {$this->intidmoneda}";
        $request = $this->select_all($sql);

        if (empty($request)) {

            $sql = "UPDATE wms_moneda 
                SET cve_moneda=?, descripcion=?, simbolo=?, tipo_cambio=?, estado=? 
                WHERE idmoneda = $this->intidmoneda";

            $arrData = array(
                $this->strCvePrecio,
                $this->strDescripcion,
                $this->strSimbolo,
                $this->strtipoCambio,
                $this->intEstatus
            );

            return $this->update($sql, $arrData);
        }

        return "exist";
    }

    public function all(array $filters = [])
    {
        $query ="SELECT
                    wms_moneda.*
                FROM wms_moneda
            WHERE true
            ";

        if(array_key_exists('idmoneda', $filters)) {
            $query .= "AND wms_moneda.idmoneda = '{$filters['idmoneda']}'";
        }

        return $this->select_all($query);
    }
}
