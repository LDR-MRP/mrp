<?php

namespace Scripts;

use Mysql;

class MassSupplierMigrator extends \Mysql
{
    private string $table = 'prv_cat_proveedores';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Verifica si un RFC ya existe de forma activa en la base de datos de Hostinger.
     */
    public function rfcExists(string $rfc): bool
    {
        $query = "SELECT id_proveedor FROM {$this->table} WHERE rfc = ? AND deleted_at IS NULL LIMIT 1";
        $request = $this->select($query, [$rfc]);
        
        return !empty($request);
    }

    /**
     * Verifica si una CLABE ya existe de forma activa en la base de datos de Hostinger.
     */
    public function clabeExists(string $clabe): bool
    {
        $query = "SELECT id_cuenta_bancaria FROM prv_det_cuentas_bancarias WHERE clabe = ? AND deleted_at IS NULL LIMIT 1";
        $request = $this->select($query, [$clabe]);
        
        return !empty($request);
    }
}