<?php

class Inv_kardexModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }

    public function selectProductos()
    {
        $sql = "SELECT idinventario, cve_articulo, descripcion
            FROM wms_inventario
            WHERE estado = 2 AND tipo_elemento IN ('P', 'H', 'C', 'R')";
        return $this->select_all($sql);
    }

    public function selectProductoKardex(int $inventarioid)
    {
        $sql = "SELECT 
            i.idinventario,
            i.cve_articulo,
            i.descripcion,
            i.unidad_salida,
            i.unidad_entrada,
            i.ubicacion,
            i.ultimo_costo,
            f.foto
        FROM wms_inventario i
        LEFT JOIN wms_fotos_inventario f 
            ON f.inventarioid = i.idinventario
        WHERE i.idinventario = $inventarioid
        AND i.estado = 2
        AND i.tipo_elemento IN ('P', 'H', 'C', 'R')
        LIMIT 1";

        return $this->select($sql);
    }

    public function selectResumenKardex(int $inventarioid)
    {
        $sql = "SELECT 
                MAX(CASE WHEN signo = 1 THEN fecha_movimiento END) AS fecha_ultima_compra,
                SUM(cantidad * signo) AS existencia,
                AVG(CASE WHEN signo = 1 THEN costo_cantidad END) AS costo_promedio
            FROM wms_movimientos_inventario
            WHERE inventarioid = $inventarioid
            AND estado = 2";

        return $this->select($sql);
    }



    public function selectKardex(
        int $inventarioid,
        int $almacen = 0,
        int $concepto = 0,
        string $fechaInicio = '',
        string $fechaFin = ''
    ) {

        $where = "WHERE m.inventarioid = $inventarioid AND m.estado = 2";

        if ($almacen > 0) {
            $where .= " AND m.almacenid = $almacen";
        }

        if ($concepto > 0) {
            $where .= " AND m.concepmovid = $concepto";
        }

        if (!empty($fechaInicio)) {
            $where .= " AND DATE(m.fecha_movimiento) >= '$fechaInicio'";
        }

        if (!empty($fechaFin)) {
            $where .= " AND DATE(m.fecha_movimiento) <= '$fechaFin'";
        }

        $sql = "SELECT 
        m.numero_movimiento,
        m.cantidad,
        m.costo_cantidad,
        SUM(m.cantidad * m.signo)
            OVER (ORDER BY m.fecha_movimiento, m.idmovinventario) AS existencia,
        c.descripcion AS concepto,
        a.descripcion AS almacen,
        m.signo,
        m.fecha_movimiento
    FROM wms_movimientos_inventario m
    INNER JOIN wms_conceptos_mov c 
        ON c.idconcepmov = m.concepmovid
    INNER JOIN wms_almacenes a
        ON a.idalmacen = m.almacenid
    $where
    ORDER BY m.fecha_movimiento, m.idmovinventario";

        return $this->select_all($sql);
    }

    public function selectAlmacenes()
    {
        $sql = "SELECT idalmacen, descripcion FROM wms_almacenes WHERE estado = 2";
        return $this->select_all($sql);
    }

    public function selectConceptos()
    {
        $sql = "SELECT idconcepmov, descripcion FROM wms_conceptos_mov WHERE estado = 2";
        return $this->select_all($sql);
    }

    public function selectTotalesKardex(int $inventarioid)
    {
        $sql = "SELECT
                SUM(cantidad * signo) AS total_existencia,
                SUM(CASE WHEN signo = 1 THEN cantidad ELSE 0 END) AS total_entradas,
                SUM(CASE WHEN signo = -1 THEN cantidad ELSE 0 END) AS total_salidas,
                SUM(CASE WHEN signo = 1 THEN costo_cantidad ELSE 0 END) AS total_compras
            FROM wms_movimientos_inventario
            WHERE inventarioid = $inventarioid
            AND estado = 2";

        return $this->select($sql);
    }

    public function selectFotosProducto(int $inventarioid)
    {
        $sql = "SELECT foto 
            FROM wms_fotos_inventario 
            WHERE inventarioid = $inventarioid";

        return $this->select_all($sql);
    }
}
