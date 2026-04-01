<?php

class Prv_detCuentaBancariaModel extends Mysql
{
    use Auditable;

    protected $table = 'prv_det_cuentas_bancarias';

    public function getTableName(): string 
    {
        return $this->table;
    }

    public function findByClabe(string $clabe): array
    {
        return $this->select_all(
            "SELECT
                id_cuenta_bancaria,
                id_proveedor,
                id_banco,
                clabe,
                cuenta,
                swift_bic,
                es_principal
            FROM {$this->table}
            WHERE clabe = ?
            ",
            [
                $clabe
            ]
        );
    }

    public function save(array $data): int
    {
        return $this->insert(
            "INSERT INTO {$this->table}
            (
                id_proveedor,
                id_banco,
                id_moneda,
                clabe,
                cuenta,
                swift_bic,
                es_principal
            )
            VALUES
            (?,?,?,?,?,?,?)",
            [
                $data['id_proveedor'],
                $data['id_banco'],
                $data['id_moneda_banco'],
                $data['clabe'],
                $data['cuenta'],
                $data['swift_bic'],
                $data['es_principal'],
            ]
        );
    }

    public function findBySupplierId(int $supplierId): array
    {
        return $this->select_all(
            "SELECT
            id_cuenta_bancaria,
            id_proveedor,
            id_banco,
            id_moneda,
            cuenta,
            swift_bic,
            clabe,
            iban,
            es_principal,
            estatus_aprobacion,
            created_by,
            updated_by,
            created_at,
            updated_at,
            deleted_at
            FROM {$this->table}
            WHERE id_proveedor = ?",
            [
                $supplierId,
            ]
        );
    }
}