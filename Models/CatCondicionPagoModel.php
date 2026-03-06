<?php

class CatCondicionPagoModel extends Mysql
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
                id_condicion  AS id,
                descripcion AS nombre
            FROM cat_condiciones_pago"
        );
    }
}