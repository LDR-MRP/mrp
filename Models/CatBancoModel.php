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

    /**
     * Valida la existencia del banco en el catálogo.
     */
    public function findById(string $idBanco): ?array
    {
        $sql = "SELECT id_banco, nombre_corto FROM cat_bancos WHERE id_banco = ?";
        return $this->select($sql, [$idBanco]) ?: null;
    }
}