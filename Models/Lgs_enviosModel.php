<?php

class Lgs_enviosModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_envios';

    public function getTableName(): string {
        return $this->table;
    }

    public function getConexion(): PDO {
        return $this->conexion;
    }

    const SCHEMA = [
        'lgs_envios' => [
            'id_envio',
            'folio',
            'id_tipo_traslado',
            'id_motivo',
            'id_proveedor',
            'id_origen',
            'id_destino',
            'destino_nombre_libre',
            'km_total',
            'costo_total',
            'fecha_tentativa_envio',
            'fecha_tentativa_llegada',
            'fecha_salida_real',
            'fecha_llegada_real',
            'observaciones',
            'id_estado',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
        'lgs_envios_vins' => [
            'id',
            'id_envio',
            'id_unidad',
            'id_destino',
            'id_parada',
            'destino_nombre_libre',
            'id_madrina',
            'id_chofer',
            'posicion_acomodo',
            'costo_unidad',
            'fecha_entrega_real',
            'recibe_nombre',
            'id_estado',
            'created_at',
        ],
        'lgs_envios_paradas' => [
            'id_parada',
            'id_envio',
            'orden',
            'id_destino_cat',
            'destino_nombre_libre',
            'km_tramo',
            'observaciones',
        ]
    ];

    /**
     * Genera un nuevo folio transaccional EN-000001
     */
    public function generarFolioTransaccional(PDO $db): string
    {
        $sql = "SELECT folio FROM lgs_envios ORDER BY id_envio DESC LIMIT 1 FOR UPDATE";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $ultimo = $stmt->fetchColumn();
        
        if (!$ultimo) {
            return 'EN-000001';
        }
        
        $num = intval(substr($ultimo, 3)) + 1;
        return 'EN-' . str_pad($num, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene todos los envíos activos para el Datatable
     */
    public function getEnviosDataTable(): array
    {
        $sql = "SELECT 
                    e.id_envio, 
                    e.folio, 
                    tt.nombre AS tipo_traslado,
                    mo.descripcion AS motivo,
                    pr.razon_social AS trasladista,
                    o.nombre AS origen,
                    COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social, d.nombre, e.destino_nombre_libre, 'Sin Destino') AS destino,
                    e.km_total,
                    e.costo_total,
                    e.fecha_tentativa_envio,
                    e.id_estado,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins,
                    (SELECT COUNT(*) FROM lgs_envios_paradas WHERE id_envio = e.id_envio) AS total_paradas,
                    COALESCE(
                        (SELECT GROUP_CONCAT(u.vin SEPARATOR ', ') 
                         FROM lgs_envios_vins ev 
                         INNER JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad 
                         WHERE ev.id_envio = e.id_envio),
                        (SELECT GROUP_CONCAT(ut.clave SEPARATOR ', ') 
                         FROM lgs_envios_vins ev 
                         INNER JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad 
                         WHERE ev.id_envio = e.id_envio),
                        ''
                    ) AS vins_list,
                    (SELECT GROUP_CONCAT(
                        COALESCE(
                            NULLIF(pc.nombre_comercial, ''), 
                            pc.razon_social, 
                            pd.nombre, 
                            p.destino_nombre_libre, 
                            'Sin Nombre'
                        ) 
                        ORDER BY p.orden ASC SEPARATOR ' ➔ '
                     )
                     FROM lgs_envios_paradas p
                     LEFT JOIN cli_clientes pc ON p.id_destino_cat = pc.idcliente
                     LEFT JOIN lgs_cat_destinos pd ON p.id_destino_cat = pd.id_destino
                     WHERE p.id_envio = e.id_envio) AS paradas_list
                FROM lgs_envios e
                LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                LEFT JOIN lgs_cat_motivo_envio mo ON e.id_motivo = mo.id_motivo
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN cli_clientes c ON e.id_destino = c.idcliente
                LEFT JOIN lgs_cat_destinos d ON e.id_destino = d.id_destino
                WHERE e.deleted_at IS NULL
                ORDER BY e.id_envio DESC";
        
        $request = $this->select_all($sql);
        return $request ?: [];
    }

    /**
     * Inserta la cabecera del envío
     */
    public function insertEnvio(PDO $db, array $data): int
    {
        $campos = $this->prepararCampos(self::SCHEMA['lgs_envios'], $data);
        $keys = implode(', ', array_keys($campos));
        $placeholders = ':' . implode(', :', array_keys($campos));
        
        $sql = "INSERT INTO lgs_envios ({$keys}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute($campos);
        
        return $db->lastInsertId();
    }

    /**
     * Inserta un VIN al envío
     */
    public function insertVin(PDO $db, array $data): int
    {
        $campos = $this->prepararCampos(self::SCHEMA['lgs_envios_vins'], $data);
        $keys = implode(', ', array_keys($campos));
        $placeholders = ':' . implode(', :', array_keys($campos));
        
        $sql = "INSERT INTO lgs_envios_vins ({$keys}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute($campos);
        
        return $db->lastInsertId();
    }

    /**
     * Elimina todas las asignaciones de VINs (acomodo) de un envío
     */
    public function deleteAcomodoEnvio(PDO $db, int $idEnvio): void
    {
        $stmt = $db->prepare("DELETE FROM lgs_envios_vins WHERE id_envio = ?");
        $stmt->execute([$idEnvio]);
    }

    /**
     * Obtiene los catálogos para alimentar los selects del modal/formulario
     */
    public function getSelectCatalogos(): array
    {
        // 1. Tipos de Traslado
        try {
            $tiposTraslado = $this->select_all("SELECT id_tipo_traslado AS id, nombre FROM lgs_cat_tipo_traslado WHERE activo = 1");
            if (empty($tiposTraslado)) {
                $tiposTraslado = [
                    ['id' => 1, 'nombre' => 'Madrina'],
                    ['id' => 2, 'nombre' => 'Chofer (Rodando)']
                ];
            }
        } catch (Throwable $e) {
            $tiposTraslado = [
                ['id' => 1, 'nombre' => 'Madrina'],
                ['id' => 2, 'nombre' => 'Chofer (Rodando)']
            ];
        }

        // 2. Motivos
        try {
            $motivos = $this->select_all("SELECT id_motivo AS id, descripcion AS nombre FROM lgs_cat_motivo_envio WHERE activo = 1 ORDER BY descripcion ASC");
            if (empty($motivos)) {
                $motivos = [
                    ['id' => 1, 'nombre' => 'Entrega a Distribuidor'],
                    ['id' => 2, 'nombre' => 'Traslado a Carrocería'],
                    ['id' => 3, 'nombre' => 'Traslado entre Almacenes'],
                    ['id' => 4, 'nombre' => 'Traslado a Planta'],
                    ['id' => 5, 'nombre' => 'Devolución de Unidad'],
                    ['id' => 6, 'nombre' => 'Otro motivo']
                ];
            }
        } catch (Throwable $e) {
            $motivos = [
                ['id' => 1, 'nombre' => 'Entrega a Distribuidor'],
                ['id' => 2, 'nombre' => 'Traslado a Carrocería'],
                ['id' => 3, 'nombre' => 'Traslado entre Almacenes'],
                ['id' => 4, 'nombre' => 'Traslado a Planta'],
                ['id' => 5, 'nombre' => 'Devolución de Unidad'],
                ['id' => 6, 'nombre' => 'Otro motivo']
            ];
        }

        // 3. Proveedores / Trasladistas
        try {
            $sqlProv = "SELECT p.id_proveedor AS id, CONCAT(p.razon_social, ' (', p.rfc, ')') AS nombre 
                        FROM prv_cat_proveedores p
                        INNER JOIN prv_rel_proveedores_actividades r ON r.id_proveedor = p.id_proveedor
                        INNER JOIN prv_cat_actividades a ON a.id_actividad = r.id_actividad
                        WHERE a.cve_actividad = 'TRASLADO_UNIDADES' AND p.deleted_at IS NULL
                        ORDER BY p.razon_social ASC";
            $proveedores = $this->select_all($sqlProv);
            if (empty($proveedores)) {
                $proveedores = $this->select_all("SELECT id_proveedor AS id, razon_social AS nombre FROM prv_cat_proveedores WHERE deleted_at IS NULL ORDER BY razon_social ASC");
            }
        } catch (Throwable $e) {
            try {
                $proveedores = $this->select_all("SELECT id_proveedor AS id, razon_social AS nombre FROM prv_cat_proveedores WHERE deleted_at IS NULL ORDER BY razon_social ASC");
            } catch (Throwable $e2) {
                $proveedores = [];
            }
        }

        // 4. Orígenes
        try {
            $origenes = $this->select_all("SELECT id_origen AS id, nombre, direccion, lat, lng FROM lgs_cat_origenes WHERE activo = 1 ORDER BY nombre ASC");
        } catch (Throwable $e) {
            $origenes = [];
        }

        // 5. Destinos y Distribuidores Unificados
        try {
            $this->sincronizarDistribuidoresDestinos();
            $sqlDest = "SELECT d.id_destino AS id, 
                               d.nombre, 
                               COALESCE(d.direccion, '') AS direccion, 
                               d.lat, 
                               d.lng,
                               COALESCE(td.descripcion, 'Distribuidor / Destino') AS tipo_destino,
                               COALESCE(d.id_tipo_destino, 1) AS id_tipo_destino
                        FROM lgs_cat_destinos d
                        LEFT JOIN lgs_cat_tipo_destino td ON d.id_tipo_destino = td.id_tipo_destino
                        WHERE d.activo = 1 
                        ORDER BY d.id_tipo_destino ASC, d.nombre ASC";
            $destinos = $this->select_all($sqlDest) ?: [];
        } catch (Throwable $e) {
            $destinos = [];
        }

        return [
            'tipos_traslado' => $tiposTraslado,
            'motivos'        => $motivos,
            'proveedores'    => $proveedores,
            'origenes'       => $origenes,
            'destinos'       => $destinos
        ];
    }

    /**
     * Sincroniza automáticamente los distribuidores de la bandeja y clientes en lgs_cat_destinos
     */
    public function sincronizarDistribuidoresDestinos(): void
    {
        try {
            // 1. Distribuidores de la bandeja de salida (lgs_unidades y lgs_unidades_envios)
            $sqlBandeja = "SELECT DISTINCT TRIM(destino_descripcion) AS nombre FROM lgs_unidades WHERE destino_descripcion IS NOT NULL AND TRIM(destino_descripcion) <> ''
                           UNION
                           SELECT DISTINCT TRIM(destino) AS nombre FROM lgs_unidades_envios WHERE destino IS NOT NULL AND TRIM(destino) <> ''";
            $distribs = $this->select_all($sqlBandeja) ?: [];

            // 2. Clientes y Distribuidores de cli_clientes
            $sqlCli = "SELECT DISTINCT COALESCE(NULLIF(TRIM(c.nombre_comercial), ''), TRIM(c.razon_social)) AS nombre,
                              CONCAT_WS(' ', d.calle, d.numero_exterior, d.colonia, d.municipio, d.estado_republica) AS direccion
                       FROM cli_clientes c
                       LEFT JOIN cli_direcciones d ON c.idcliente = d.idcliente
                       WHERE c.estado <> 0";
            $cliRows = $this->select_all($sqlCli) ?: [];

            $lista = [];
            foreach ($distribs as $d) {
                $n = trim($d['nombre'] ?? '');
                if (!empty($n) && !isset($lista[mb_strtolower($n)])) {
                    $lista[mb_strtolower($n)] = ['nombre' => $n, 'direccion' => null, 'tipo' => 1];
                }
            }
            foreach ($cliRows as $c) {
                $n = trim($c['nombre'] ?? '');
                if (!empty($n)) {
                    $key = mb_strtolower($n);
                    if (!isset($lista[$key])) {
                        $lista[$key] = ['nombre' => $n, 'direccion' => $c['direccion'] ?? null, 'tipo' => 1];
                    } elseif (!empty($c['direccion']) && empty($lista[$key]['direccion'])) {
                        $lista[$key]['direccion'] = $c['direccion'];
                    }
                }
            }

            // 3. Insertar los faltantes en lgs_cat_destinos
            foreach ($lista as $item) {
                $nom  = $item['nombre'];
                $dir  = $item['direccion'];
                $tipo = $item['tipo'];
                $exist = $this->select("SELECT id_destino FROM lgs_cat_destinos WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1", [$nom]);
                if (empty($exist)) {
                    $this->insert("INSERT INTO lgs_cat_destinos (nombre, id_tipo_destino, direccion, activo) VALUES (?, ?, ?, 1)", [$nom, $tipo, $dir]);
                }
            }
        } catch (Throwable $e) {
            // Manejo silencioso
        }
    }

    /**
     * Helper para filtrar los campos según el SCHEMA
     */
    private function prepararCampos(array $schema, array $data): array
    {
        $campos = [];
        foreach ($schema as $campo) {
            if (array_key_exists($campo, $data)) {
                $campos[$campo] = $data[$campo];
            }
        }
        return $campos;
    }

    /**
     * Obtiene la cabecera completa de un envío por su ID
     */
    public function getEnvioCabecera(int $idEnvio): array
    {
        $sql = "SELECT 
                    e.id_envio,
                    e.folio,
                    e.id_tipo_traslado,
                    e.id_motivo,
                    e.id_proveedor,
                    pr.razon_social AS trasladista,
                    e.id_origen,
                    o.nombre AS origen,
                    e.id_destino,
                    COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social, d.nombre, e.destino_nombre_libre, 'Sin Destino') AS destino,
                    e.km_total,
                    e.costo_total,
                    e.id_estado
                FROM lgs_envios e
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN cli_clientes c ON e.id_destino = c.idcliente
                LEFT JOIN lgs_cat_destinos d ON e.id_destino = d.id_destino
                WHERE e.id_envio = ? AND e.deleted_at IS NULL";
        $res = $this->select($sql, [$idEnvio]);
        return $res ?: [];
    }

    /**
     * Obtiene las madrinas activas pertenecientes al proveedor del envío
     */
    public function getMadrinasPorProveedor(int $idProveedor): array
    {
        $sql = "SELECT 
                    m.id_madrina,
                    m.numero_economico,
                    m.placas,
                    m.placa_caja,
                    m.marca,
                    m.modelo,
                    m.capacidad_vehiculos,
                    (SELECT CONCAT(c.nombre, ' ', c.apellidos) 
                     FROM prv_det_madrina_chofer_historial h
                     INNER JOIN prv_det_choferes c ON c.id_chofer = h.id_chofer
                     WHERE h.id_madrina = m.id_madrina AND h.activo = 1 LIMIT 1) AS chofer_asignado
                FROM prv_det_madrinas m
                WHERE (m.id_proveedor = ? OR ? = 0) AND m.deleted_at IS NULL
                ORDER BY m.numero_economico ASC";
        $res = $this->select_all($sql, [$idProveedor, $idProveedor]);
        return $res ?: [];
    }

    /**
     * Obtiene los choferes activos pertenecientes al proveedor del envío
     */
    public function getChoferesPorProveedor(int $idProveedor): array
    {
        $sql = "SELECT 
                    c.id_chofer,
                    CONCAT(c.nombre, ' ', c.apellidos) AS nombre_completo,
                                       c.tipo_licencia
                FROM prv_det_choferes c
                WHERE (c.id_proveedor = ? OR ? = 0) AND c.deleted_at IS NULL
                ORDER BY c.nombre ASC";
        $res = $this->select_all($sql, [$idProveedor, $idProveedor]);
        return $res ?: [];
    }

    /**
     * Obtiene VINs disponibles en el origen que no estén asignados a otros envíos activos
     */
    public function getVinsDisponiblesOrigen(int $idOrigen = 0, int $idEnvioActual = 0): array
    {
        $origenNombre = '';

        if ($idEnvioActual > 0) {
            $envio = $this->getEnvioCabecera($idEnvioActual);
            if (!empty($envio)) {
                $idOrigen = intval($envio['id_origen'] ?? $idOrigen);
                $origenNombre = trim($envio['origen'] ?? '');
            }
        }

        if (empty($origenNombre) && $idOrigen > 0) {
            $origRow = $this->select("SELECT nombre FROM lgs_cat_origenes WHERE id_origen = ?", [$idOrigen]);
            if (!empty($origRow)) {
                $origenNombre = trim($origRow['nombre'] ?? '');
            }
        }

        try {
            // Excluir unidades asignadas a otros envíos activos no eliminados
            $sqlExclude = "SELECT ev.id_unidad 
                           FROM lgs_envios_vins ev
                           INNER JOIN lgs_envios e ON ev.id_envio = e.id_envio
                           WHERE e.deleted_at IS NULL AND e.id_estado <> 0";
            
            // Excluir también las que ya están en el acomodo de este envío para no duplicarlas en el pool disponible
            $sqlExcludeThis = ($idEnvioActual > 0) 
                ? "SELECT ev2.id_unidad FROM lgs_envios_vins ev2 WHERE ev2.id_envio = " . intval($idEnvioActual)
                : "SELECT 0";

            // 1. Consultar unidades desde la bandeja operativa (lgs_unidades) combinando con lgs_unidades_envios y mrp_unidades_terminadas
            $sql = "SELECT 
                        COALESCE(u.id_unidad, lu.id_unidad, ut.idunidad) AS id_unidad,
                        COALESCE(u.vin, ut.clave, CONCAT('VIN-', lu.id_unidad)) AS vin,
                        COALESCE(u.num_serie, ut.num_unidad, 'S/N') AS num_serie,
                        COALESCE(u.modelo, 'Unidad Terminada') AS modelo,
                        COALESCE(u.origen, 'Planta Lagos de Moreno') AS origen,
                        COALESCE(NULLIF(TRIM(lu.destino_descripcion), ''), NULLIF(TRIM(u.destino), ''), 'Sin Asignar') AS destino
                    FROM lgs_unidades lu
                    LEFT JOIN lgs_unidades_envios u ON u.id_unidad = lu.id_unidad
                    LEFT JOIN mrp_unidades_terminadas ut ON ut.idunidad = lu.id_unidad
                    WHERE (lu.id_estado_proceso = 1 OR lu.id_estado_proceso IS NULL)
                      AND lu.id_unidad NOT IN ({$sqlExclude})
                      AND lu.id_unidad NOT IN ({$sqlExcludeThis})
                    ORDER BY lu.id_lgs_unidad ASC";

            $res = $this->select_all($sql) ?: [];

            // 2. Si no hay en lgs_unidades, buscar en lgs_unidades_envios
            if (empty($res)) {
                $sql2 = "SELECT 
                            u.id_unidad,
                            u.vin,
                            u.num_serie,
                            u.modelo,
                            COALESCE(u.origen, 'Planta Lagos de Moreno') AS origen,
                            COALESCE(NULLIF(TRIM(u.destino), ''), 'Sin Asignar') AS destino
                        FROM lgs_unidades_envios u
                        WHERE u.id_unidad NOT IN ({$sqlExclude})
                          AND u.id_unidad NOT IN ({$sqlExcludeThis})
                        ORDER BY u.id_unidad ASC";
                $res = $this->select_all($sql2) ?: [];
            }

            return $res;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Obtiene las asignaciones/acomodo existentes en un envío
     */
    public function getAcomodoExistenteEnvio(int $idEnvio): array
    {
        try {
            $sql = "SELECT 
                        v.id,
                        v.id_envio,
                        v.id_unidad,
                        COALESCE(u.vin, ut.clave, CONCAT('VIN-', v.id_unidad)) AS vin,
                        COALESCE(u.num_serie, ut.num_unidad, 'S/N') AS num_serie,
                        COALESCE(u.modelo, 'Unidad Terminada') AS modelo,
                        COALESCE(u.origen, 'Origen') AS origen,
                        COALESCE(
                            NULLIF(TRIM(v.destino_nombre_libre), ''), 
                            NULLIF(TRIM(lu.destino_descripcion), ''), 
                            NULLIF(TRIM(u.destino), ''), 
                            'Destino'
                        ) AS destino,
                        v.id_madrina,
                        v.id_chofer,
                        v.id_parada,
                        v.posicion_acomodo,
                        m.numero_economico AS madrina_nombre,
                        CONCAT(c.nombre, ' ', c.apellidos) AS chofer_nombre
                    FROM lgs_envios_vins v
                    LEFT JOIN lgs_unidades_envios u ON v.id_unidad = u.id_unidad
                    LEFT JOIN lgs_unidades lu ON lu.id_unidad = v.id_unidad
                    LEFT JOIN mrp_unidades_terminadas ut ON v.id_unidad = ut.idunidad
                    LEFT JOIN prv_det_madrinas m ON v.id_madrina = m.id_madrina
                    LEFT JOIN prv_det_choferes c ON v.id_chofer = c.id_chofer
                    WHERE v.id_envio = ?
                    ORDER BY v.id_madrina ASC, v.id_chofer ASC, v.posicion_acomodo ASC";
            $res = $this->select_all($sql, [$idEnvio]);
            return $res ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Sincroniza el motivo del envío si es necesario
     */
    public function actualizarMotivoDesdeVins(PDO $db, int $idEnvio): void
    {
        // Motivo es seleccionado manualmente en el envío
    }

    // ──────────────────────────────────────────────────────────────
    // PARADAS / MULTI-DESTINO
    // ──────────────────────────────────────────────────────────────

    /**
     * Obtiene las paradas ordenadas de un envío
     */
    public function getParadasEnvio(int $idEnvio): array
    {
        $sql = "SELECT 
                    p.id_parada,
                    p.id_envio,
                    p.orden,
                    p.id_destino_cat,
                    COALESCE(
                        NULLIF(c.nombre_comercial, ''), 
                        c.razon_social, 
                        d.nombre, 
                        p.destino_nombre_libre, 
                        'Sin Nombre'
                    ) AS destino_nombre,
                    p.destino_nombre_libre,
                    p.km_tramo,
                    p.observaciones
                FROM lgs_envios_paradas p
                LEFT JOIN cli_clientes c ON p.id_destino_cat = c.idcliente
                LEFT JOIN lgs_cat_destinos d ON p.id_destino_cat = d.id_destino
                WHERE p.id_envio = ?
                ORDER BY p.orden ASC";
        $res = $this->select_all($sql, [$idEnvio]);
        return $res ?: [];
    }

    /**
     * Inserta una parada de envío
     */
    public function insertParada(PDO $db, array $data): int
    {
        $campos = $this->prepararCampos(self::SCHEMA['lgs_envios_paradas'], $data);
        $keys = implode(', ', array_keys($campos));
        $placeholders = ':' . implode(', :', array_keys($campos));
        $sql = "INSERT INTO lgs_envios_paradas ({$keys}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute($campos);
        return (int) $db->lastInsertId();
    }

    /**
     * Elimina todas las paradas de un envío (para re-insertar)
     */
    public function deleteParadasEnvio(PDO $db, int $idEnvio): void
    {
        $stmt = $db->prepare("DELETE FROM lgs_envios_paradas WHERE id_envio = ?");
        $stmt->execute([$idEnvio]);
    }

    /**
     * Recalcula km_total del envío sumando todos los km_tramo de sus paradas
     * y actualiza id_destino con la última parada
     */
    public function actualizarKmTotalDesdeParadas(PDO $db, int $idEnvio): void
    {
        $sql = "UPDATE lgs_envios
                SET km_total   = COALESCE((SELECT SUM(km_tramo) FROM lgs_envios_paradas WHERE id_envio = ?), 0),
                    id_destino = COALESCE((SELECT id_destino_cat FROM lgs_envios_paradas WHERE id_envio = ? ORDER BY orden DESC LIMIT 1), id_destino)
                WHERE id_envio = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idEnvio, $idEnvio, $idEnvio]);
    }

    /**
     * Reabre / desbloquea un envío regresándolo a estado 1 (Creado / Borrador)
     */
    public function reabrirEnvio(int $idEnvio): bool
    {
        $sql = "UPDATE lgs_envios SET id_estado = 1 WHERE id_envio = ?";
        return $this->update($sql, [$idEnvio]);
    }
}

