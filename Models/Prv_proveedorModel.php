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
            -- LEFT JOIN `prv_tra_onboarding`
            --     ON `prv_tra_onboarding`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
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
                rfc_activo,
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
     * Actualiza el estatus de onboarding y, si se aprueba, activa automáticamente la operación comercial.
     * 
     * @param int    $supplierId ID del proveedor.
     * @param string $status     'Aprobado', 'Rechazado', 'En Revision'.
     * @param int    $adminId    ID del administrador que ejecuta la acción.
     * @return bool
     */
    public function updateOnboardingStatus(int $supplierId, string $status, int $adminId): bool
    {
        // Usamos CASE WHEN para que la activación de estatus_operativo sea atómica
        // y ocurra directamente en el motor de base de datos de Hostinger.
        $sql = "UPDATE `prv_cat_proveedores` 
                SET estatus_onboarding = ?,
                    estatus_operativo = CASE WHEN ? = 'Aprobado' THEN 1 ELSE estatus_operativo END,
                    updated_by = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_proveedor = ?";

        // Pasamos los parámetros posicionales en el orden exacto de los '?'
        return (bool) $this->update($sql, [
            $status,
            $status, // Duplicamos para la condición CASE WHEN
            $adminId,
            $supplierId
        ]);
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

    /**
     * Extrae los datos consolidados de Onboarding y Datos Satélite para el Reporte del CEO.
     * Soporta filtrado opcional por planta (id_planta).
     */
    public function getOnboardingReportData(array $filters = []): array
    {
        $query = "SELECT 
                    p.id_proveedor,
                    p.razon_social,
                    p.nombre_comercial,
                    p.rfc,
                    p.id_tipo_persona,
                    p.origen,
                    p.estatus_onboarding,
                    p.estatus_operativo,
                    p.created_at,
                    
                    -- 1. Conteo de documentos aprobados en el expediente (Vía 1)
                    (SELECT COUNT(*) 
                     FROM prv_det_expediente de 
                     WHERE de.id_proveedor = p.id_proveedor 
                       AND de.estatus_validacion = 1 
                       AND de.deleted_at IS NULL
                    ) AS approved_docs_count,
                    
                    -- 2. Datos Satélite: Estatus de Cuentas Bancarias
                    (SELECT 
                        CASE 
                            WHEN COUNT(*) = 0 THEN 'SIN_REGISTRAR'
                            WHEN SUM(CASE WHEN cb.estatus_aprobacion = 'APROBADO' THEN 1 ELSE 0 END) > 0 THEN 'APROBADO'
                            ELSE 'PENDIENTE'
                        END
                     FROM prv_det_cuentas_bancarias cb 
                     WHERE cb.id_proveedor = p.id_proveedor 
                       AND cb.deleted_at IS NULL
                    ) AS estatus_bancario,
                    
                    -- 3. Datos Satélite: Dirección
                    (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END 
                     FROM prv_det_direcciones d 
                     WHERE d.id_proveedor = p.id_proveedor 
                       AND d.deleted_at IS NULL
                    ) AS tiene_direccion,
                    
                    -- 4. Datos Satélite: Contacto
                    (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END 
                     FROM prv_det_contactos c 
                     WHERE c.id_proveedor = p.id_proveedor 
                       AND c.deleted_at IS NULL
                    ) AS tiene_contacto,
                    
                    -- 5. Datos Satélite: Configuración Financiera
                    (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END 
                     FROM prv_det_config_financiera cf 
                     WHERE cf.id_proveedor = p.id_proveedor 
                       AND cf.deleted_at IS NULL
                    ) AS tiene_config_financiera

                  FROM prv_cat_proveedores p
                  WHERE p.deleted_at IS NULL";

        $params = [];

        // --- INICIO ADICIÓN: Filtro Dinámico por Planta ---
        if (!empty($filters['plantaid'])) {
            $query .= " AND p.id_planta = :plantaid";
            $params[':plantaid'] = (int)$filters['plantaid'];
        }

        if (!empty($filters['id_proveedor'])) {
            $query .= " AND p.id_proveedor = :id_proveedor";
            $params[':id_proveedor'] = $filters['id_proveedor'];
        }
        // --- FIN ADICIÓN ---

        $query .= " ORDER BY p.estatus_onboarding DESC, p.created_at DESC";

        return $this->select_all($query, $params) ?? [];
    }

    /**
     * Obtiene la línea de tiempo de eventos relevantes para un proveedor.
     * Filtra por el ID del proveedor en las tablas relacionadas.
     */
    public function getRecentActivity(int $supplierId, int $limit = 7): array
    {
        $query = "SELECT 
                    accion as evento,
                    comentario as detalle,
                    created_at,
                    nombre_tabla
                  FROM log_audit
                  WHERE 
                    -- 1. Logs directos de su registro maestro
                    (nombre_tabla = 'prv_cat_proveedores' AND resourceid = :id1)
                    
                    -- 2. Logs de sus Órdenes de Compra
                    OR (nombre_tabla = 'com_ordenes_compra' AND resourceid IN (
                        SELECT idcompra FROM com_ordenes_compra WHERE proveedorid = :id2
                    ))
                    
                    -- 3. Logs de sus Facturas (CXP)
                    OR (nombre_tabla = 'cxp_tra_facturas' AND resourceid IN (
                        SELECT id FROM cxp_tra_facturas WHERE id_proveedor = :id3
                    ))

                    -- 4. Logs de su Expediente / Documentos
                    OR (nombre_tabla = 'prv_det_expediente' AND resourceid IN (
                        SELECT id_proveedor FROM prv_det_expediente WHERE id_proveedor = :id4
                    ))
                  ORDER BY created_at DESC
                  LIMIT {$limit}";

        $params = [
            ':id1'   => $supplierId,
            ':id2'   => $supplierId,
            ':id3'   => $supplierId,
            ':id4'   => $supplierId
        ];

        return $this->select_all($query, $params);
    }

    /**
     * Realiza un pre-registro minimalista de un proveedor prospecto.
     * Permite obtener un ID válido para cumplir con la integridad referencial.
     * 
     * @param array $data ['razon_social', 'estatus_onboarding']
     * @return int ID del nuevo proveedor generado.
     */
    public function insertLite(array $data): int
    {
        $query = "INSERT INTO prv_cat_proveedores (
                    razon_social, 
                    rfc, 
                    estatus_onboarding, 
                    created_at
                ) VALUES (?, ?, ?, ?)";

        $params = [
            $data['razon_social'],
            $data['rfc'], // RFC temporal para evitar errores de esquema
            $data['estatus_onboarding'] ?? 'Prospecto',
            date('Y-m-d H:i:s')
        ];

        return (int)$this->insert($query, $params);
    }
}