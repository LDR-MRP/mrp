<?php

class CatBancoModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function all()
    {
        return $this->select_all(
            "SELECT * FROM cat_bancos;"
        );
    }
}