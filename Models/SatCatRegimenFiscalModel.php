<?php

class SatCatRegimenFiscalModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function byFisica()
    {
        return $this->select_all(
            "SELECT
                *,
                id_regimen_fiscal AS id,
                descripcion AS nombre
            FROM sat_cat_regimen_fiscal
            WHERE aplica_fisica = 1;"
        );
    }

    public function byMoral()
    {
        return $this->select_all(
            "SELECT
                *,
                id_regimen_fiscal AS id,
                descripcion AS nombre
            FROM sat_cat_regimen_fiscal
            WHERE aplica_moral = 1;"
        );
    }

    public function byId(int $id)
    {
        return $this->select_all(
            query:
                "SELECT
                    *,
                    id_regimen_fiscal AS id,
                    descripcion AS nombre
                FROM sat_cat_regimen_fiscal
                WHERE id_regimen_fiscal = ?;",
            arrValues: [
                $id
            ]
        );
    }
}