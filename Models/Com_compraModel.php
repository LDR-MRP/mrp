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



    public function suppliers()
    {
        return $this->select_all(
            "SELECT
                *
            FROM prv_proveedores
            ;"
        );
    }
}
