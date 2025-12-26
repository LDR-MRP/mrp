<?php

class Inv_almacenesModel extends Mysql
{

    public $intidalmacen;
    public $strcve_almacen;
    public $strdirecion;
    public $intelefono;
    public $strencargado;
    public $strFecha;
    public $intEstatus;
    public $intPrecio;
    public $strDescripcion;


    public function __construct()
    {
        parent::__construct();
    }



    public function inserAlmacen(
        string $cve_almacen,
        string $descripcion,
        string $direccion,
        string $encargado,
        string $telefono,
        string $listaprecioid,
        string $fecha,
        int $estado
    ) {

        $sql = "SELECT * FROM wms_almacenes 
            WHERE cve_almacen = '$cve_almacen'";
        $request = $this->select_all($sql);

        if (!empty($request)) {
            return "exist";
        }

        $query_insert = "INSERT INTO wms_almacenes
        (cve_almacen, descripcion, direccion, encargado, telefono, listaprecioid, fecha_creacion, estado)
        VALUES (?,?,?,?,?,?,?,?)
    ";

        return $this->insert($query_insert, [
            $cve_almacen,
            $descripcion,
            $direccion,
            $encargado,
            $telefono,
            $listaprecioid,
            $fecha,
            $estado
        ]);
    }




    public function selectAlmacenes()
    {
        $sql = "SELECT 
            alm.idalmacen,
            alm.cve_almacen,
            alm.descripcion,
            alm.direccion,
            alm.encargado,
            alm.telefono,
            IFNULL(pre.descripcion,'SIN LISTA') AS lista_precio,
            alm.fecha_creacion,
            alm.estado
        FROM wms_almacenes alm
        LEFT JOIN wms_precios pre 
    ON alm.listaprecioid = pre.idprecio
        WHERE alm.estado != 0
    ";
        return $this->select_all($sql);
    }


    public function selectOptionAlmacenes($idalmacen)
    {
        $this->intPrecio = intval($idalmacen);

        $sql = "SELECT * FROM wms_almacenes WHERE estado = 2";

        if ($this->intPrecio > 0) {
            $sql .= " AND listaprecioid = {$this->intPrecio}";
        }

        return $this->select_all($sql);
    }


    public function selectAlmacen(int $idalmacen)
    {
        $this->intidalmacen = $idalmacen;

        $sql = "SELECT 
                alm.idalmacen,
                alm.cve_almacen,
                alm.descripcion,
                alm.direccion,
                alm.encargado,
                alm.telefono,
                alm.listaprecioid,
                IFNULL(pre.descripcion,'SIN LISTA') AS lista_precio,
                alm.fecha_creacion,
                alm.estado
            FROM wms_almacenes alm
            LEFT JOIN wms_precios pre 
                ON alm.listaprecioid = pre.idprecio
            WHERE alm.idalmacen = $this->intidalmacen";

        return $this->select($sql);
    }


    public function deleteAlmacen(int $idalmacen)
    {
        $this->intidalmacen = $idalmacen;
        $sql = "UPDATE wms_almacenes SET estado = ? WHERE idalmacen = $this->intidalmacen ";
        $arrData = array(0);
        $request = $this->update($sql, $arrData);
        return $request;
    }


    public function updateAlmacen(
        int $idalmacen,
        string $cve_almacen,
        string $descripcion,
        string $direccion,
        string $encargado,
        string $telefono,
        string $listaprecioid,
        int $estado
    ) {
        // VALIDAR DUPLICADO (SIN ?)
        $sql = "SELECT * FROM wms_almacenes 
            WHERE cve_almacen = '$cve_almacen'
              AND idalmacen != $idalmacen";
        $request = $this->select_all($sql);

        if (!empty($request)) {
            return "exist";
        }

        // UPDATE (ESTE SÍ ESTÁ BIEN)
        $sql = "UPDATE wms_almacenes SET
                cve_almacen = ?,
                descripcion = ?,
                direccion = ?,
                encargado = ?,
                telefono = ?,
                listaprecioid = ?,
                estado = ?
            WHERE idalmacen = ?";

        return $this->update($sql, [
            $cve_almacen,
            $descripcion,
            $direccion,
            $encargado,
            $telefono,
            $listaprecioid,
            $estado,
            $idalmacen
        ]);
    }
}
