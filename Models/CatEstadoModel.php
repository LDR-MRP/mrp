<?php

class CatEstadoModel extends Mysql
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
                id_estado AS id,
            FROM cat_estados_mx"
        );
    }
}