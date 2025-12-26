<?php

class Inv_concepmovinventariosModel extends Mysql
{
    public $intidconcepmov;
    public $strClave;
    public $strCvePrecio;
    public $strFecha;
    public $intEstatus;
    public $strDescripcion;
    public $intImpuesto;


    public function __construct()
    {
        parent::__construct();
    }



    public function insertConcepto($clave, $descripcion, $cpn, $tipo, $signo, $estado)
    {
        $sql = "SELECT * FROM wms_conceptos_mov WHERE cve_concep_mov = ?";
        $request = $this->select_all($sql, [$clave]);

        if (empty($request)) {
            $sql = "INSERT INTO wms_conceptos_mov
                (cve_concep_mov,descripcion,cpn,tipo_movimiento,signo,estado)
                VALUES (?,?,?,?,?,?)";
            return $this->insert($sql, [
                $clave,
                $descripcion,
                $cpn,
                $tipo,
                $signo,
                $estado
            ]);
        }
        return "exist";
    }

    public function selectConceptos()
    {
        return $this->select_all("SELECT * FROM wms_conceptos_mov WHERE estado != 0");
    }



    public function selectPrecios()
    {
        $sql = "SELECT * FROM  wms_precios 
					WHERE estado != 0 ";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectOptionPrecios()
    {
        $sql = "SELECT * FROM  wms_precios 
					WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectPrecio(int $idconcepmov)
    {
        $this->intidconcepmov = $idconcepmov;
        $sql = "SELECT * FROM wms_precios
					WHERE idconcepmov = $this->intidconcepmov";
        $request = $this->select($sql);
        return $request;
    }

    public function deletePrecio(int $idconcepmov)
    {
        $this->intidconcepmov = $idconcepmov;
        $sql = "UPDATE wms_precios SET estado = ? WHERE idconcepmov = $this->intidconcepmov ";
        $arrData = array(0);
        $request = $this->update($sql, $arrData);
        return $request;
    }


    public function updatePrecio($idconcepmov, $cve_concep_mov, $descripcion, $impuesto, $estado)
    {
        $this->intidconcepmov = $idconcepmov;
        $this->strCvePrecio = $cve_concep_mov;
        $this->strDescripcion = $descripcion;
        $this->intImpuesto = $impuesto;
        $this->intEstatus = $estado;

        $sql = "SELECT * FROM wms_precios WHERE cve_concep_mov = '{$this->strCvePrecio}' AND idconcepmov != {$this->intidconcepmov}";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $sql = "UPDATE wms_precios SET cve_concep_mov = ?, descripcion = ?, con_impuesto = ?, estado = ?  WHERE idconcepmov = $this->intidconcepmov ";
            $arrData = array(
                $this->strCvePrecio,
                $this->strDescripcion,
                $this->intImpuesto,
                $this->intEstatus
            );
            $request = $this->update($sql, $arrData);
        } else {
            $request = "exist";
        }
        return $request;
    }
}
