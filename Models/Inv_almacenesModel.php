<?php

class Inv_almacenesModel extends Mysql
{

    public $intidalmacen;
    public $strcve_almacen;
    public $strdirecion;
    public $intelefono;
    public $strencargado;
    public $strcorreo;
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
        string $correo,
        string $listaprecioid,
        string $fecha,
        int $estado,
        ?int $plantaid = null
    ) {

        $sql = "SELECT * FROM wms_almacenes 
            WHERE cve_almacen = '$cve_almacen'";
        $request = $this->select_all($sql);

        if (!empty($request)) {
            return "exist";
        }

        $query_insert = "INSERT INTO wms_almacenes
        (cve_almacen, descripcion, direccion, encargado, telefono, correo, listaprecioid, plantaid, fecha_creacion, estado)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ";

        return $this->insert($query_insert, [
            $cve_almacen,
            $descripcion,
            $direccion,
            $encargado,
            $telefono,
            $correo,
            $listaprecioid,
            $plantaid,
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
            alm.correo,
            IFNULL(pre.descripcion,'SIN LISTA') AS lista_precio,
            alm.plantaid,
            pl.cve_planta,
            pl.nombre_planta,
            alm.fecha_creacion,
            alm.estado
        FROM wms_almacenes alm
        LEFT JOIN wms_precios pre 
    ON alm.listaprecioid = pre.idprecio
        LEFT JOIN mrp_planta pl ON pl.idplanta = alm.plantaid
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
                alm.correo,
                alm.listaprecioid,
                IFNULL(pre.descripcion,'SIN LISTA') AS lista_precio,
                alm.plantaid,
                pl.cve_planta,
                pl.nombre_planta,
                alm.fecha_creacion,
                alm.estado
            FROM wms_almacenes alm
            LEFT JOIN wms_precios pre 
                ON alm.listaprecioid = pre.idprecio
            LEFT JOIN mrp_planta pl ON pl.idplanta = alm.plantaid
            WHERE alm.idalmacen = $this->intidalmacen";

        return $this->select($sql);
    }


    /** Plantas activas para el select del formulario. */
    public function selectPlantas()
    {
        $sql = "SELECT idplanta, cve_planta, nombre_planta
                FROM mrp_planta
                WHERE estado = 2
                ORDER BY cve_planta, nombre_planta";
        return $this->select_all($sql);
    }

    /** true si la planta existe y está activa. */
    public function existePlantaActiva(int $plantaid): bool
    {
        $sql = "SELECT idplanta FROM mrp_planta WHERE idplanta = ? AND estado = 2 LIMIT 1";
        return !empty($this->select_all($sql, [$plantaid]));
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
        string $correo,
        string $listaprecioid,
        int $estado,
        ?int $plantaid = null
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
                correo = ?,
                listaprecioid = ?,
                plantaid = ?,
                estado = ?
            WHERE idalmacen = ?";

        return $this->update($sql, [
            $cve_almacen,
            $descripcion,
            $direccion,
            $encargado,
            $telefono,
            $correo,
            $listaprecioid,
            $plantaid,
            $estado,
            $idalmacen
        ]);
    }
}
