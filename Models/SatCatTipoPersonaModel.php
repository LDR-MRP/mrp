<?php

class SatCatTipoPersonaModel extends Mysql
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
                id_tipo_persona AS id, 
                descripcion AS nombre
            FROM sat_cat_tipo_persona;
            "
        );
    }
}