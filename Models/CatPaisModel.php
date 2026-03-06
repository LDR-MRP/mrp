<?php

class CatPaisModel extends Mysql
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
               id_pais AS id 
            FROM cat_paises"
        );
    }
}