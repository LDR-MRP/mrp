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
                direccion,
                estatus
            FROM cat_bancos
            WHERE estatus = 1;"
        );
    }

    /**
     * Valida la existencia del banco en el catálogo.
     */
    public function findById(string $idBanco): ?array
    {
        $sql = "SELECT id_banco, nombre_corto, razon_social, direccion FROM cat_bancos WHERE id_banco = ?";
        return $this->select($sql, [$idBanco]) ?: null;
    }

    /**
     * Inserta un nuevo banco en el catálogo (agregado por usuario).
     */
    public function insert(string $query, array $arrValues): int
    {
        return parent::insert($query, $arrValues);
    }
}