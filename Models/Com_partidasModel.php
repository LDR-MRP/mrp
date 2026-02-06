<?php

class Com_partidasModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function selectPartidasByCompraId(int $compraId)
    {
        return $this->select_all(
            "SELECT
                `wms_inventario`.`cve_articulo`,
                `wms_inventario`.`descripcion`,
                `com_compras_det`.`cantidad`,
                `com_compras_det`.`costo_unitario`,
                `com_compras_det`.`descuento_partida`,
                `com_compras_det`.`impuesto_partida`,
                `com_compras_det`.`subtotal_partida`
            FROM com_compras_det
            INNER JOIN wms_inventario
            ON com_compras_det.inventarioid = wms_inventario.idinventario
            WHERE compraid = ?;",
            [
                $compraId
            ],
        );
    }
}
