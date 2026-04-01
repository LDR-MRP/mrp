<?php

class CatBancoModel extends Mysql
{
    protected $table = 'cat_bancos';

    public function __construct()
    {
        parent::__construct();
    }

    public function all()
    {
        return $this->select_all(
            "SELECT
                id_banco,
                nombre_corto,
                razon_social,
                estatus
            FROM cat_bancos;"
        );
    }

    public function findById(string $id): array
    {
        return $this->select_all(
            "SELECT
                id_banco,
                nombre_corto,
                razon_social,
                estatus
            FROM {$this->table}
            WHERE id_banco = ?",
            [
                $id
            ]
        );
    }
}