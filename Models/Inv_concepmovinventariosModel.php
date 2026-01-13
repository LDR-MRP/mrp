<?php

class Inv_concepmovinventariosModel extends Mysql
{
    public $intidconcepmov;
    public $strClave;
    public $strCveConcepto;
    public $intEstatus;
    public $strDescripcion;
    public $strcpn;


    public function __construct()
    {
        parent::__construct();
    }





    public function insertConcepto(
        string $cve,
        string $descripcion,
        string $cpn,
        string $tipo_mov,
        int $estado,
        int $signo
    ) {
        $sql = "INSERT INTO wms_conceptos_mov
    (cve_concep_mov, descripcion, cpn, tipo_movimiento, estado, signo)
    VALUES (?,?,?,?,?,?)";

        $arrData = [
            $cve,
            $descripcion,
            $cpn,
            $tipo_mov,
            $estado,
            $signo
        ];

        return $this->insert($sql, $arrData);
    }



    public function selectConceptos()
    {
        $sql = "SELECT * FROM  wms_conceptos_mov 
					WHERE estado != 0 ";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectOptionConceptos()
    {
        $sql = "SELECT * FROM  wms_conceptos_mov 
					WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectConcepto(int $idconcepmov)
    {
        $this->intidconcepmov = $idconcepmov;
        $sql = "SELECT * FROM wms_conceptos_mov
					WHERE idconcepmov = $this->intidconcepmov";
        $request = $this->select($sql);
        return $request;
    }

    public function deleteConcepto(int $idconcepmov)
    {
        $this->intidconcepmov = $idconcepmov;
        $sql = "UPDATE wms_conceptos_mov SET estado = ? WHERE idconcepmov = $this->intidconcepmov ";
        $arrData = array(0);
        $request = $this->update($sql, $arrData);
        return $request;
    }


    public function updateConcepto(
        int $idconcepmov,
        string $cve,
        string $descripcion,
        string $cpn,
        string $tipo_mov,
        int $estado,
        int $signo
    ) {
        $sql = "UPDATE wms_conceptos_mov 
            SET cve_concep_mov = ?, 
                descripcion = ?, 
                cpn = ?, 
                tipo_movimiento = ?, 
                estado = ?, 
                signo = ?
            WHERE idconcepmov = $idconcepmov";

        $arrData = [
            $cve,
            $descripcion,
            $cpn,
            $tipo_mov,
            $estado,
            $signo
        ];

        return $this->update($sql, $arrData);
    }
}
