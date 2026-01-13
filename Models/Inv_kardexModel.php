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
            WHERE estado = 2 AND tipo_elemento = 'P'";
        return $this->select_all($sql);
    }

    public function selectProductoKardex(int $inventarioid)
    {
        $sql = "SELECT 
                idinventario,
                cve_articulo,
                descripcion,
                unidad_salida,
                unidad_entrada,
                control_almacen,
                ultimo_costo
            FROM wms_inventario
            WHERE idinventario = $inventarioid
            AND estado = 2
            AND tipo_elemento = 'P'";

        return $this->select($sql);
    }

    public function selectResumenKardex(int $inventarioid)
    {
        $sql = "SELECT 
                MAX(CASE WHEN signo = 1 THEN fecha_movimiento END) AS fecha_ultima_compra,
                SUM(cantidad * signo) AS existencia,
                AVG(CASE WHEN signo = 1 THEN costo END) AS costo_promedio
            FROM wms_movimientos_inventario
            WHERE inventarioid = $inventarioid
            AND estado = 2";

        return $this->select($sql);
    }



    public function selectKardex(int $inventarioid)
    {
        $sql = "SELECT 
                m.numero_movimiento,
                m.cantidad,
                m.costo,
                SUM(m.cantidad * m.signo)
                    OVER (ORDER BY m.fecha_movimiento, m.idmovinventario) AS existencia,
                c.descripcion AS concepto,
                m.costo_cantidad,
                m.signo,
                m.fecha_movimiento
            FROM wms_movimientos_inventario m
            INNER JOIN wms_conceptos_mov c 
                ON c.idconcepmov = m.concepmovid
            WHERE m.inventarioid = $inventarioid
            AND m.estado = 2
            ORDER BY m.fecha_movimiento, m.idmovinventario";

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

}
