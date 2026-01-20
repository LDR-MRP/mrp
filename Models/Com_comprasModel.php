<?php

class Com_comprasModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function selectCompras()
    {
        return $this->select_all(
            "SELECT
                `com_compras`.`idcompra`,
                `com_compras`.`tipo_documento`,
                `com_compras`.`status`,
                `com_compras`.`enlazado`,
                `com_compras`.`clv_documento`,
                `com_compras`.`cantidad_total`,
                `wms_proveedores`.`nombre_comercial`,
                `com_compras`.`fecha_documento`,
                `com_compras`.`fecha_elaboracion`
            FROM com_compras
            INNER JOIN wms_proveedores
            ON com_compras.proveedorid = wms_proveedores.idproveedor;"
        );
    }

    public function insertCompra(array $data)
    {
        return $this->insert(
            "INSERT INTO com_compras(
                `clv_documento`,
                `proveedorid`,
                `almacenid`,
                `monedaid`,
                `fecha_documento`,
                `serieid`,
                `cantidad_total`
            ) VALUES(
                ?,?,?,?,?,?,?
            );",
            [
                rand(),
                $data['proveedor'],
                $data['almacen'],
                $data['moneda'],
                $data['fecha_documento'],
                $data['serieid'],
                $data['cantidad_total'],
            ],
        );
    }

    public function insertDetalle(int $idCompra, array $item)
    {
        return $this->insert(
            "INSERT INTO com_compras_det(
                `compraid`,
                `inventarioid`,
                `cantidad`,
                `costo_unitario`,
                `impuesto_partida`,
                `subtotal_partida`
            ) VALUES(
                ?,?,?,?,?,?
            );",
            [
                $idCompra,
                $item['inventario'],
                $item['cantidad'],
                $item['costo_unitario'],
                $item['impuesto_partida'],
                $item['subtotal_partida'],
            ],
        );
    }
}
