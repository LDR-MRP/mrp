<?php

class Prv_proveedorModel extends Mysql
{
    use Auditable;

    protected string $table = 'prv_cat_proveedores';

    const SCHEMA = [
        'prv_cat_proveedores' => [
            'id_proveedor',
            'id_empresa',
            'rfc',
            'razon_social',
            'nombre_comercial',
            'id_tipo_persona',
            'id_regimen_fiscal',
            'origen',
            'estatus_onboarding',
            'estatus_operativo',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
        'prv_det_direcciones' => [
            'id_direccion',
            'id_proveedor',
            'tipo',
            'calle',
            'num_ext',
            'num_int',
            'colonia',
            'cp',
            'municipio',
            'ciudad',
            'estado',
            'es_principal',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
        'prv_det_config_financiera' => [
            'id_config_financiera',
            'id_proveedor',
            'id_condicion_pago',
            'cuenta_contable',
            'limite_credito',
            'id_moneda_defecto',
            'tasa_iva_default',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
        'prv_det_contactos' => [
            'id_contacto',
            'id_proveedor',
            'nombre',
            'puesto',
            'email',
            'telefono',
            'notificar_compras',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ]
    ];

    public function getTableName(): string 
    {
        return $this->table;
    }

    public function findByCriteria(array $filters = [])
    {
        $sql = "SELECT
                -- Master
                `prv_cat_proveedores`.`id_proveedor` AS id,
                `prv_cat_proveedores`.`id_empresa`,
                `prv_cat_proveedores`.`id_tipo_persona`,
                `prv_cat_proveedores`.`id_regimen_fiscal`,
                `prv_cat_proveedores`.`rfc`,
                `prv_cat_proveedores`.`razon_social`,
                `prv_cat_proveedores`.`nombre_comercial`,
                `prv_cat_proveedores`.`origen`,
                `prv_cat_proveedores`.`estatus_onboarding`,
                `prv_cat_proveedores`.`estatus_operativo`,
                `prv_cat_proveedores`.`created_at`,
                `prv_cat_proveedores`.`created_by`,
                -- Addresses Columns
                `prv_det_direcciones`.`tipo`,
                `prv_det_direcciones`.`calle`,
                `prv_det_direcciones`.`num_ext`,
                `prv_det_direcciones`.`num_int`,
                `prv_det_direcciones`.`colonia`,
                `prv_det_direcciones`.`cp`,
                `prv_det_direcciones`.`municipio`,
                `prv_det_direcciones`.`ciudad`,
                `prv_det_direcciones`.`estado`,
                `prv_det_direcciones`.`es_principal`,
                -- Contacts Columns
                `prv_det_contactos`.`nombre`,
                `prv_det_contactos`.`puesto`,
                `prv_det_contactos`.`email`,
                `prv_det_contactos`.`telefono`,
                `prv_det_contactos`.`notificar_compras`,
                -- Financial Config Columns
                `prv_det_config_financiera`.`id_config_financiera`,
                `prv_det_config_financiera`.`id_proveedor`,
                `prv_det_config_financiera`.`id_condicion_pago`,
                `prv_det_config_financiera`.`cuenta_contable`,
                `prv_det_config_financiera`.`limite_credito`,
                `prv_det_config_financiera`.`id_moneda_defecto`,
                `prv_det_config_financiera`.`tasa_iva_default`,
                `cat_condiciones_pago`.`descripcion`
            FROM `prv_cat_proveedores`
            -- Addresses JOIN
            LEFT JOIN `prv_det_direcciones`
                ON `prv_det_direcciones`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
            -- Contacts JOIN
            LEFT JOIN `prv_det_contactos`
                ON `prv_det_contactos`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
            -- Financial Config JOIN
            LEFT JOIN `prv_det_config_financiera`
                ON `prv_det_config_financiera`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
            -- Financial Config JOIN
            LEFT JOIN `cat_condiciones_pago`
                ON `cat_condiciones_pago`.`id_condicion` = `prv_det_config_financiera`.`id_condicion_pago`
            -- Onboarding Information JOIN
            LEFT JOIN `prv_tra_onboarding`
                ON `prv_tra_onboarding`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
            WHERE true\n";

        if(array_key_exists('id_proveedor', $filters)){ $sql .= "AND `prv_cat_proveedores`.`id_proveedor` = '{$filters['id_proveedor']}'"; }
        if(array_key_exists('estado', $filters)){ $sql .= "AND ``estado` = '{$filters['estado']}'"; }
        if(array_key_exists('rfc', $filters)){ $sql .= "AND `rfc` = '{$filters['rfc']}'"; }

        return $this->select_all($sql);
    }

    /**
     * 
     */
    public function getKpi(array $filters)
    {
        $where = "";
        $params = [];

        // Filtro por Usuario
        if (!empty($filters['created_by'])) {
            $where .= " AND created_by = ? ";
            $params[] = $filters['created_by'];
        }

        // Filtro por Planta
        if (array_key_exists('id_planta', $filters)) {
            $where .= " AND id_planta = ? ";
            $params[] = $filters['id_planta'];
        }

        $query = "SELECT 
                lower(IFNULL(estatus_onboarding, 'total')) AS estatus,
                count(id_proveedor) as cantidad
            FROM prv_cat_proveedores
            $where
            GROUP BY estatus_onboarding WITH ROLLUP;
            ";

        return $this->select_all($query, $params);
    }

    public function destroy(int $supplierId)
    {
        $query = sprintf(
            "UPDATE prv_cat_proveedores SET estatus = 0, deleted_at = NOW() WHERE idproveedor = %d;",
            $supplierId
        );
        return $this->delete($query);
    }

    public function insertSupplier(array $h, int $userId): int
    {
        return $this->insert(
            "INSERT INTO prv_cat_proveedores (
                -- id_empresa,
                rfc,
                razon_social,
                nombre_comercial,
                id_tipo_persona,
                id_regimen_fiscal,
                origen,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                // $h['id_empresa'],
                $h['rfc'],
                $h['razon_social'], 
                $h['nombre_comercial'],
                $h['id_tipo_persona'], 
                $h['id_regimen_fiscal'],
                $h['origen'],
                $userId,
            ]
        );
    }

    public function insertAddress(array $d, int $supplierId, int $userId): int
    {
        return $this->insert(
            "INSERT INTO prv_det_direcciones (
                id_proveedor,tipo,
                calle,
                num_ext,
                num_int,
                colonia,
                cp,
                municipio,
                ciudad,
                estado,
                es_principal,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $supplierId,
                $d['tipo'],
                $d['calle'],
                $d['num_ext'], 
                $d['num_int'],
                $d['colonia'],
                $d['cp'],
                $d['municipio'], 
                $d['ciudad'], 
                $d['estado'], 
                $d['es_principal'],
                $userId,
            ]
        );
    }

    public function insertFinancialConfig(array $f, int $supplierId, int $userId): int
    {
        return $this->insert(
            "INSERT INTO prv_det_config_financiera (
                    id_proveedor,
                    id_condicion_pago,
                    cuenta_contable,
                    limite_credito,
                    id_moneda_defecto,
                    tasa_iva_default,
                    created_by
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $supplierId,
                    $f['id_condicion_pago'],
                    $f['cuenta_contable'], 
                    $f['limite_credito'],
                    $f['id_moneda_defecto'],
                    $f['tasa_iva_default'],
                    $userId,
                ]
            );
    }

    public function insertContact(array $c, int $supplierId, int $userId): int
    {
        return $this->insert(
            "INSERT INTO prv_det_contactos (
                id_proveedor,
                nombre,
                puesto,
                email,
                telefono,
                notificar_compras,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $supplierId,
                $c['nombre'],
                $c['puesto'], 
                $c['email'],
                $c['telefono'],
                $c['notificar_compras'],
                $userId,
            ]
        );
    }

    public function insertOnboarding(int $supplierId, int $userId): int
    {
        return $this->insert(
            "INSERT INTO prv_tra_onboarding (
                id_proveedor,
                paso_actual,
                created_by
            ) VALUES (?, 1, ?)",
            [
                $supplierId,
                $userId,
            ]
        );
    }

    /**
     * Realiza una actualización dinámica sobre una tabla específica.
     * Construye la sentencia SET basada en los campos "sucios" (dirty data).
     * 
     * @param string $table Nombre de la tabla satélite.
     * @param array $tableData Array asociativo [columna => valor].
     * @param int $supplierId ID del proveedor para el WHERE.
     */
    public function updateDynamic(string $table, array $tableData, int $supplierId): bool
    {
        // 1. Construir el set de columnas: "col1 = ?, col2 = ?"
        $colNames = array_keys($tableData);
        $setClause = implode(', ', array_map(fn($col) => "{$col} = ?", $colNames));

        // 2. Preparar el query
        $query = "UPDATE {$table} SET {$setClause} WHERE id_proveedor = ?;";

        // 3. Unificar valores: [valor1, valor2, ..., supplierId]
        // El orden es CRÍTICO: primero los valores del SET, al final el del WHERE.
        $params = array_values($tableData);
        $params[] = $supplierId;

        // 4. Ejecutar usando tu método base de la clase Mysql
        return $this->update($query, $params);
    }

    public function getFinancialConfig(int $proveedorId): array
    {
        return [];
    }

    /**
     * Actualiza el estatus de madurez del proveedor en el flujo de Onboarding.
     * Esta transición permite que el proveedor sea visible para el módulo de Compras.
     *
     * @param int    $supplierId ID del proveedor.
     * @param string $status     Nuevo estado ('Prospecto', 'En Revision', 'Aprobado', 'Rechazado').
     * @param int    $adminId    ID del administrador que autoriza el cambio.
     * @return bool True si se actualizó correctamente.
     */
    public function updateOnboardingStatus(int $supplierId, string $status, int $adminId): bool
    {
        $sql = "UPDATE `{$this->table}` 
                SET estatus_onboarding = ?, 
                    updated_by = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id_proveedor = ? 
                  AND deleted_at IS NULL";

        // Usamos el método update de tu clase base Mysql
        $rowCount = $this->update($sql, [
            $status, 
            $adminId, 
            $supplierId
        ]);

        return $rowCount > 0;
    }

    /**
     * Obtiene los datos maestros de un proveedor específico por su identificador.
     * Este método es vital para validar el perfil fiscal (Tipo Persona/Origen) 
     * durante el proceso de Onboarding.
     *
     * @param int $id ID único del proveedor en la tabla prv_cat_proveedores.
     * @return array|null Retorna el registro del proveedor o null si no existe o fue eliminado.
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT 
                    id_proveedor,
                    id_empresa,
                    rfc,
                    razon_social,
                    nombre_comercial,
                    id_tipo_persona, -- 'F' o 'M'
                    id_regimen_fiscal,
                    tipo,           -- 'Interno' o 'Externo'
                    origen,         -- 'Nacional' o 'Extranjero'
                    estatus_onboarding,
                    estatus_operativo,
                    id_planta,        -- Para validaciones de Scope (IDOR)
                    created_at
                FROM `{$this->table}` 
                WHERE id_proveedor = ? 
                  AND deleted_at IS NULL 
                LIMIT 1";

        $result = $this->select($sql, [$id]);

        // Retornamos null si el objeto Mysql devuelve false o vacío
        return $result ?: null;
    }
}