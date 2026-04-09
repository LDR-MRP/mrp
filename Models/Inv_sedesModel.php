<?php

class Inv_sedesModel extends Mysql
{
    public $intsede;
    public $strCveSede;
    public $strFecha;
    public $intEstatus;
    public $strDescripcion;


    public function __construct()
    {
        parent::__construct();
    }

    public function inserSede($cve_sede, $descripcion, $fecha_creacion, $intEstatus)
    {
        $this->strCveSede = $cve_sede;
        $this->strDescripcion = $descripcion;
        $this->strFecha = $fecha_creacion;
        $this->intEstatus = $intEstatus;

        $sql = "SELECT * FROM wms_sedes WHERE cve_sede = '{$this->strCveSede}'";
        $request = $this->select_all($sql);

        if (empty($request)) {

            $query_insert = "INSERT INTO wms_sedes(cve_sede, descripcion, fecha_creacion, estado) 
                         VALUES(?,?,?,?)";

            $arrData = array(
                $this->strCveSede,
                $this->strDescripcion,
                $this->strFecha,
                $this->intEstatus
            );

            return $this->insert($query_insert, $arrData);
        }

        return "exist";
    }


    public function selectSedes()
    {
        $sql = "SELECT 
                idsede,
                cve_sede,
                descripcion,
                fecha_creacion,
                estado
            FROM wms_sedes
            ORDER BY descripcion";
        return $this->select_all($sql);
    }

    public function selectSede(int $idsede)
    {
        $this->intsede = $idsede;
        $sql = "SELECT * FROM wms_sedes WHERE idsede = $this->intsede";
        return $this->select($sql);
    }


    public function deleteSede(int $idsede)
    {
        $this->intsede = $idsede;
        $sql = "UPDATE wms_sedes SET estado = ? WHERE idsede = $this->intsede";
        return $this->update($sql, [0]);
    }



    public function updateSede($idsede, $cve_sede, $descripcion, $estado)
    {
        $this->intsede = $idsede;
        $this->strCveSede = $cve_sede;
        $this->strDescripcion = $descripcion;
        $this->intEstatus = $estado;

        $sql = "SELECT * FROM wms_sedes 
            WHERE cve_sede = '{$this->strCveSede}' 
            AND idsede != {$this->intsede}";
        $request = $this->select_all($sql);

        if (empty($request)) {

            $sql = "UPDATE wms_sedes 
                SET cve_sede=?, descripcion=?, estado=? 
                WHERE idsede = $this->intsede";

            $arrData = array(
                $this->strCveSede,
                $this->strDescripcion,
                $this->intEstatus
            );

            return $this->update($sql, $arrData);
        }

        return "exist";
    }

    public function all(array $filters = [])
    {
        $query ="SELECT
                    wms_sedes.*
                FROM wms_sedes
            WHERE true
            ";

        if(array_key_exists('idsede', $filters)) {
            $query .= "AND wms_sedes.idsede = '{$filters['idsede']}'";
        }

        return $this->select_all($query);
    }
}
