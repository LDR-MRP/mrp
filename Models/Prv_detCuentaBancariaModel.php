<?php

class Prv_detCuentaBancariaModel extends Mysql
{
    use Auditable;

    protected $table = 'prv_det_cuentas_bancarias';

    public function getTableName(): string 
    {
        return $this->table;
    }

    /**
     * Busca una cuenta por su CLABE para detectar duplicidad entre proveedores.
     * Herramienta Antifraude de tu FormRequest.
     */
    public function findByClabe(string $clabe): ?array
    {
        $sql = "SELECT id_proveedor, id_cuenta_bancaria 
                FROM `{$this->table}` 
                WHERE clabe = ? AND deleted_at IS NULL LIMIT 1";
        return $this->select($sql, [$clabe]) ?: null;
    }

    /**
     * Mantenimiento de la tabla (Insert) con campos internacionales.
     */
    public function save(array $data): int
    {
        $sql = "INSERT INTO `{$this->table}` (
                    id_proveedor, id_banco, id_moneda, cuenta, clabe, 
                    swift_bic, iban, url_pdf, es_principal, estatus_aprobacion, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return $this->insert($sql, [
            $data['id_proveedor'], $data['id_banco'], $data['id_moneda'],
            $data['cuenta'], $data['clabe'], $data['swift_bic'],
            $data['iban'], $data['url_pdf'], $data['es_principal'], $data['estatus_aprobacion'],
            $data['created_by']
        ]);
    }

    /**
     * Obtiene todas las cuentas activas de un proveedor, resolviendo el nombre del banco.
     * 
     * @param int $supplierId
     * @return array
     */
    public function findBySupplierId(int $supplierId): array
    {
        $sql = "SELECT
                    cb.id_cuenta_bancaria,
                    cb.id_proveedor,
                    cb.id_banco,
                    b.nombre_corto AS nombre_banco, -- Resolvemos el nombre para la UI
                    cb.id_moneda,
                    cb.cuenta,
                    cb.swift_bic,
                    cb.clabe,
                    cb.iban,
                    cb.url_pdf,
                    cb.es_principal,
                    cb.estatus_aprobacion,
                    cb.created_at
                FROM `{$this->table}` cb
                LEFT JOIN cat_bancos b ON cb.id_banco = b.id_banco
                WHERE cb.id_proveedor = ? 
                  AND cb.deleted_at IS NULL -- CRÍTICO: No mostrar registros eliminados
                ORDER BY cb.es_principal DESC, cb.created_at DESC";

        $result = $this->select_all($sql, [$supplierId]);
        
        return $result ?: [];
    }

    /**
     * Actualiza el estatus de aprobación de una cuenta bancaria.
     */
    public function updateApprovalStatus(int $id, string $status, int $userId): bool
    {
        $sql = "UPDATE `{$this->table}` 
                SET estatus_aprobacion = ?, 
                    updated_by = ?, 
                    updated_at = NOW() 
                WHERE id_cuenta_bancaria = ?";
                
        return $this->update($sql, [$status, $userId, $id]);
    }

    /**
     * Resetea el flag de 'cuenta principal' para todas las cuentas activas de un proveedor.
     * Se utiliza para garantizar la regla de negocio de "Única Cuenta Principal" 
     * antes de asignar una nueva.
     *
     * @param int $supplierId ID del proveedor cuyas cuentas se van a resetear.
     * @return bool True si la consulta se ejecutó correctamente.
     */
    public function resetPrincipalAccounts(int $supplierId): bool
    {
        // Solo actualizamos las cuentas que actualmente son principales (es_principal = 1)
        // para reducir el bloqueo de filas en la base de datos.
        $sql = "UPDATE `{$this->table}` 
                SET es_principal = 0, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id_proveedor = ? 
                  AND es_principal = 1 
                  AND deleted_at IS NULL";

        return $this->update($sql, [$supplierId]);
    }

    /**
     * Aplica borrado lógico (Soft Delete) a una cuenta bancaria.
     * 
     * @param int $id     ID de la cuenta.
     * @param int $userId Usuario que ejecuta.
     * @return bool
     */
    public function softDelete(int $id, int $userId): bool
    {
        $sql = "UPDATE `{$this->table}` 
                SET deleted_at = NOW(), 
                    deleted_by = ? 
                WHERE id_cuenta_bancaria = ?";
                
        return $this->update($sql, [$userId, $id]);
    }
}