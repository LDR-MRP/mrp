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

    public function inserMoneda($cve_moneda, $descripcion, $simbolo, $fecha_creacion, $intEstatus)
    {
        $this->strCvePrecio = $cve_moneda;
        $this->strDescripcion = $descripcion;
        $this->strSimbolo = $simbolo;
        $this->strFecha = $fecha_creacion;
        $this->intEstatus = $intEstatus;

        $sql = "SELECT * FROM wms_moneda WHERE cve_moneda = '{$this->strCvePrecio}'";
        $request = $this->select_all($sql);

        if (empty($request)) {

            $query_insert = "INSERT INTO wms_moneda(cve_moneda, descripcion, simbolo, fecha_creacion, estado)
                                VALUES(?,?,?,?,?)";

            $arrData = array(
                $this->strCvePrecio,
                $this->strDescripcion,
                $this->strSimbolo,
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



    public function updateMoneda($idmoneda, $cve_moneda, $descripcion, $simbolo, $estado)
    {
        $this->intidmoneda = $idmoneda;
        $this->strCvePrecio = $cve_moneda;
        $this->strDescripcion = $descripcion;
        $this->strSimbolo = $simbolo;
        $this->intEstatus = $estado;

        // 🔥 Obtener registro actual
        $sql = "SELECT cve_moneda FROM wms_moneda WHERE idmoneda = {$this->intidmoneda}";
        $actual = $this->select($sql);

        // 🔥 SOLO validar duplicado si cambió la clave
        if ($actual['cve_moneda'] != $this->strCvePrecio) {

            $sql = "SELECT * FROM wms_moneda 
                WHERE cve_moneda = '{$this->strCvePrecio}'";
            $request = $this->select_all($sql);

            if (!empty($request)) {
                return "exist";
            }
        }

        // 🔥 Actualizar
        $sql = "UPDATE wms_moneda 
            SET cve_moneda=?, descripcion=?, simbolo=?, estado=?
            WHERE idmoneda = $this->intidmoneda";

        $arrData = array(
            $this->strCvePrecio,
            $this->strDescripcion,
            $this->strSimbolo,
            $this->intEstatus
        );

        return $this->update($sql, $arrData);
    }

    public function all(array $filters = [])
    {
        $query = "SELECT
                    wms_moneda.*,
                    wms_moneda.simbolo AS id,
                    CONCAT(wms_moneda.simbolo, ' - ', wms_moneda.descripcion) AS nombre
                FROM wms_moneda
            WHERE true
            ";

        if (array_key_exists('idmoneda', $filters)) {
            $query .= "AND wms_moneda.idmoneda = '{$filters['idmoneda']}'";
        }

        return $this->select_all($query);
    }
}
