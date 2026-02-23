<?php

class Com_compraModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data, int $userId)
    {
        return $this->insert(
            "INSERT INTO com_compras(
                requisicionid,
                proveedorid,
                almacenid,
                usuarioid,
                estatus,
                moneda,
                tipo_cambio,
                subtotal,
                iva,
                total
            ) VALUES(
                ?,?,?,?,?,?,?,?,?,?
            );",
            [
                $data['requisicionid'],
                $data['proveedorid'],
                $data['almacenid'],
                $userId,
                'emitida',
                $data['moneda'],
                $data['tipo_cambio'],
                $data['monto_estimado'],
                $data['iva'],
                $data['total'],
            ],
        );
    }

    public function findByCriteria(int $id)
    {
        return $this->select(
            "SELECT *
            FROM com_compras
            LEFT JOIN com_requisiciones
            ON com_requisiciones.idrequisicion = com_compras.requisicionid
            LEFT JOIN prv_proveedores
            ON prv_proveedores.idproveedor = com_compras.proveedorid
            LEFT JOIN wms_almacenes
            ON wms_almacenes.idalmacen = com_compras.almacenid
            LEFT JOIN usuarios
            ON usuarios.idusuario = com_compras.usuarioid = ?",
            [$id]
        );
    }
}
