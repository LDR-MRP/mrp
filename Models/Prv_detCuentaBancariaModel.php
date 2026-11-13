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
     * Busca una cuenta por CLABE incluyendo registros dados de baja.
     * Útil para detectar cuentas susceptibles de reactivación.
     */
    public function findByClabeIncludingDeleted(string $clabe): ?array
    {
        $logFile = 'Logs/mrp-' . date('Y-m-d') . '.log';
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::findByClabeIncludingDeleted] Buscando CLABE: {$clabe}\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        $sql = "SELECT
                    id_cuenta_bancaria,
                    id_proveedor,
                    id_banco,
                    id_moneda,
                    cuenta,
                    clabe,
                    swift_bic,
                    iban,
                    url_pdf,
                    es_principal,
                    estatus_aprobacion,
                    deleted_at,
                    deleted_by,
                    created_at
                FROM `{$this->table}`
                WHERE clabe = ?
                ORDER BY deleted_at DESC LIMIT 1";

        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::findByClabeIncludingDeleted] SQL: {$sql}\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        $result = $this->select($sql, [$clabe]);

        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::findByClabeIncludingDeleted] Resultado: " . json_encode($result) . "\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        return $result ?: null;
    }

    /**
     * Obtiene una cuenta por su ID (incluyendo registros dados de baja).
     */
    public function findById(int $id): ?array
    {
        $logFile = 'Logs/mrp-' . date('Y-m-d') . '.log';
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::findById] Buscando cuenta con ID: {$id}\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        $sql = "SELECT * FROM `{$this->table}` WHERE id_cuenta_bancaria = ?";
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::findById] SQL: {$sql}\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        $result = $this->select($sql, [$id]);

        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::findById] Resultado: " . json_encode($result) . "\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        return $result ?: null;
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

    /**
     * Reactiva una cuenta bancaria dada de baja.
     * 
     * @param int $id ID de la cuenta.
     * @param array $data Datos opcionales a actualizar (url_pdf, swift_bic, iban).
     * @param int $userId Usuario que ejecuta.
     * @return bool
     */
    public function reactivate(int $id, array $data, int $userId): bool
    {
        $logFile = 'Logs/mrp-' . date('Y-m-d') . '.log';
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::reactivate] Iniciando reactivación. ID: {$id}, UserId: {$userId}\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::reactivate] Data recibida: " . json_encode($data) . "\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        $fields = [
            'deleted_at' => null,
            'deleted_by' => null,
            'estatus_aprobacion' => 'PENDIENTE', // Resetear a pendiente para validación
            'updated_at' => 'NOW()',
            'updated_by' => $userId
        ];

        // Campos opcionales que pueden actualizarse durante reactivación
        if (isset($data['url_pdf'])) {
            $fields['url_pdf'] = $data['url_pdf'];
        }
        if (isset($data['swift_bic'])) {
            $fields['swift_bic'] = $data['swift_bic'];
        }
        if (isset($data['iban'])) {
            $fields['iban'] = $data['iban'];
        }

        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::reactivate] Fields a actualizar: " . json_encode($fields) . "\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        $setClause = [];
        $values = [];

        foreach ($fields as $key => $value) {
            if (strtoupper($value) === 'NOW()') {
                $setClause[] = "$key = NOW()";
                $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::reactivate] Campo {$key} = NOW()\n";
                file_put_contents($logFile, $logMsg, FILE_APPEND);
            } elseif ($value === null) {
                // Usar SQL literal NULL en lugar de parámetro
                $setClause[] = "$key = NULL";
                $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::reactivate] Campo {$key} = NULL (SQL literal)\n";
                file_put_contents($logFile, $logMsg, FILE_APPEND);
            } else {
                $setClause[] = "$key = ?";
                $values[] = $value;
                $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::reactivate] Campo {$key} = ? (valor: {$value})\n";
                file_put_contents($logFile, $logMsg, FILE_APPEND);
            }
        }

        $sql = "UPDATE `{$this->table}`
                SET " . implode(', ', $setClause) . "
                WHERE id_cuenta_bancaria = ?";

        $values[] = $id;

        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::reactivate] SQL completo: " . $sql . "\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::reactivate] Valores a bind: " . json_encode($values) . "\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        $result = $this->update($sql, $values);

        $logMsg = "[" . date('Y-m-d H:i:s') . "] [Prv_detCuentaBancariaModel::reactivate] Resultado de update: " . ($result ? 'EXITOSO' : 'FALLIDO') . "\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        return $result;
    }
}