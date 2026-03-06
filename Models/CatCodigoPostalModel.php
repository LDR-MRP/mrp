<?php

class CatCodigoPostalModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function findByCP(mixed $cp)
    {
        return $this->select_all(
            "SELECT cp, asentamiento, tipo_asentamiento, municipio, estado, ciudad
                FROM cat_codigos_postales 
                WHERE cp = ?",
            [$cp]
        );
    }
}