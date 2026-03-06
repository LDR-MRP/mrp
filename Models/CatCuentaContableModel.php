<?php

class CatCuentaContableModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function all()
    {
        return $this->select_all(
            "SELECT
                *,
                id_cuenta_contable AS id,
                nombre_cuenta AS nombre
            FROM cat_cuentas_contables"
        );
    }
}