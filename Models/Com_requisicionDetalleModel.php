<?php

class Com_requisicionDetalleModel extends Mysql{
    public function __construct()
    {
        parent::__construct();
    }

    public function detailCreate(int $requisitionId, array $item)
    {
         return $this->insert(
            "INSERT INTO com_requisiciones_detalle
            (requisicionid,
            inventarioid,
            cantidad,
            precio_unitario_estimado,
            notas)
            VALUES
            (?,?,?,?,?)",
            [
                $requisitionId,
                $item['inventarioid'],
                $item['cantidad'],
                $item['precio_unitario_estimado'],
                $item['notas'] ?? '',
            ]
        );
    }

    public function details(int $requisitionId = null)
    {
         return $this->select_all(
            "SELECT * FROM com_requisiciones_detalle
            LEFT JOIN wms_inventario
            ON wms_inventario.idinventario = com_requisiciones_detalle.inventarioid
            WHERE requisicionid = ?;",
            [
                $requisitionId,
            ]
        );
    }
}