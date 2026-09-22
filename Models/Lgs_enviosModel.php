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
            'id_nodo_subida',
            'id_nodo_bajada',
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
        'lgs_envios_nodos' => [
            'id_nodo',
            'id_envio',
            'orden',
            'tipo_nodo',
            'id_ubicacion',
            'destino_nombre_libre',
            'km_tramo_anterior',
            'observaciones',
            'fecha_estimada',
            'created_at',
        ],
        'lgs_distancias' => [
            'id_distancia',
            'id_ubicacion_a',
            'id_ubicacion_b',
            'km',
            'created_at',
            'updated_at',
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
                    COALESCE(
                        (SELECT u0.nombre FROM lgs_envios_nodos n0 LEFT JOIN lgs_cat_ubicaciones u0 ON n0.id_ubicacion = u0.id_ubicacion WHERE n0.id_envio = e.id_envio AND n0.orden = 0 LIMIT 1),
                        o.nombre,
                        'Origen'
                    ) AS origen,
                    COALESCE(
                        (SELECT COALESCE(uLast.nombre, nLast.destino_nombre_libre) FROM lgs_envios_nodos nLast LEFT JOIN lgs_cat_ubicaciones uLast ON nLast.id_ubicacion = uLast.id_ubicacion WHERE nLast.id_envio = e.id_envio ORDER BY nLast.orden DESC LIMIT 1),
                        NULLIF(c.nombre_comercial, ''),
                        c.razon_social,
                        d.nombre,
                        e.destino_nombre_libre,
                        'Sin Destino'
                    ) AS destino,
                    e.km_total,
                    e.costo_total,
                    e.fecha_tentativa_envio,
                    e.id_estado,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins,
                    (SELECT COUNT(*) FROM lgs_envios_nodos WHERE id_envio = e.id_envio AND orden > 0) AS total_paradas,
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
                        COALESCE(u.nombre, n.destino_nombre_libre, 'Sin Nombre') 
                        ORDER BY n.orden ASC SEPARATOR ' ➔ '
                     )
                     FROM lgs_envios_nodos n
                     LEFT JOIN lgs_cat_ubicaciones u ON n.id_ubicacion = u.id_ubicacion
                     WHERE n.id_envio = e.id_envio) AS paradas_list
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
     * Elimina todas las asignaciones de VINs (acomodo) de un envío y libera las unidades
     */
    public function deleteAcomodoEnvio(PDO $db, int $idEnvio): void
    {
        // 1. Obtener los IDs de unidades afectadas
        $stmtVins = $db->prepare("SELECT id_unidad FROM lgs_envios_vins WHERE id_envio = ?");
        $stmtVins->execute([$idEnvio]);
        $unitIds = $stmtVins->fetchAll(PDO::FETCH_COLUMN);

        // 2. Eliminar asignaciones
        $stmt = $db->prepare("DELETE FROM lgs_envios_vins WHERE id_envio = ?");
        $stmt->execute([$idEnvio]);

        // 3. Revertir estado de unidades a Pendiente (1) si no están en otro envío activo
        if (!empty($unitIds)) {
            $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
            $sqlRelease = "UPDATE lgs_unidades 
                           SET id_estado_proceso = 1, updated_at = NOW() 
                           WHERE id_unidad IN ({$placeholders}) 
                             AND id_unidad NOT IN (
                                 SELECT ev.id_unidad 
                                 FROM lgs_envios_vins ev 
                                 INNER JOIN lgs_envios e ON ev.id_envio = e.id_envio 
                                 WHERE e.deleted_at IS NULL AND e.id_estado NOT IN (0, 7)
                             )";
            $stmtRel = $db->prepare($sqlRelease);
            $stmtRel->execute($unitIds);
        }
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

        // 4. Ubicaciones
        try {
            $ubicaciones = $this->select_all("SELECT id_ubicacion AS id, nombre, direccion, lat, lng, id_tipo_destino FROM lgs_cat_ubicaciones WHERE activo = 1 ORDER BY nombre ASC");
        } catch (Throwable $e) {
            $ubicaciones = [];
        }

        // Dividir orígenes y destinos según lo esperado (por retrocompatibilidad en el front)
        $origenes = array_filter($ubicaciones, function($u) {
            return $u['id_tipo_destino'] == 5; // Planta
        });
        
        $destinos = array_filter($ubicaciones, function($u) {
            return $u['id_tipo_destino'] != 5; // No es planta
        });

        // Aseguramos que los arrays estén indexados numéricamente
        $origenes = array_values($origenes);
        $destinos = array_values($destinos);

        return [
            'tipos_traslado' => $tiposTraslado,
            'motivos'        => $motivos,
            'proveedores'    => $proveedores,
            'origenes'       => $origenes,
            'destinos'       => $destinos,
            'ubicaciones'    => $ubicaciones
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
                    e.destino_nombre_libre,
                    COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social, d.nombre, e.destino_nombre_libre, 'Sin Destino') AS destino,
                    e.km_total,
                    e.costo_total,
                    e.fecha_tentativa_envio,
                    e.fecha_tentativa_llegada,
                    e.observaciones,
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
            // Excluir unidades asignadas a otros envíos que ya estén confirmados o en planeación/ejecución
            $sqlExclude = "SELECT ev.id_unidad 
                           FROM lgs_envios_vins ev
                           INNER JOIN lgs_envios e ON ev.id_envio = e.id_envio
                           WHERE e.deleted_at IS NULL AND e.id_estado IN (2, 3, 5, 6, 7, 8)";
            
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
                        v.id_nodo_subida,
                        v.id_nodo_bajada,
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
     * Obtiene los nodos (rutas/paradas) ordenadas de un envío
     */
    public function getParadasEnvio(int $idEnvio): array
    {
        // Intentar con columnas nuevas primero, fallback a columnas originales
        try {
            $sql = "SELECT 
                        n.id_nodo AS id_parada,
                        n.id_envio,
                        n.orden,
                        n.tipo_nodo,
                        n.id_ubicacion AS id_destino_cat,
                        COALESCE(u.nombre, n.destino_nombre_libre, 'Sin Nombre') AS destino_nombre,
                        n.destino_nombre_libre,
                        n.km_tramo_anterior AS km_tramo,
                        n.observaciones,
                        n.fecha_estimada
                    FROM lgs_envios_nodos n
                    LEFT JOIN lgs_cat_ubicaciones u ON n.id_ubicacion = u.id_ubicacion
                    WHERE n.id_envio = ? AND n.orden > 0
                    ORDER BY n.orden ASC";
            $res = $this->select_all($sql, [$idEnvio]);
            return $res ?: [];
        } catch (Throwable $e) {
            $sql = "SELECT 
                        n.id_nodo AS id_parada,
                        n.id_envio,
                        n.orden,
                        n.id_ubicacion AS id_destino_cat,
                        COALESCE(u.nombre, n.destino_nombre_libre, 'Sin Nombre') AS destino_nombre,
                        n.destino_nombre_libre,
                        n.km_tramo_anterior AS km_tramo,
                        n.observaciones
                    FROM lgs_envios_nodos n
                    LEFT JOIN lgs_cat_ubicaciones u ON n.id_ubicacion = u.id_ubicacion
                    WHERE n.id_envio = ? AND n.orden > 0
                    ORDER BY n.orden ASC";
            $res = $this->select_all($sql, [$idEnvio]);
            return $res ?: [];
        }
    }

    /**
     * Inserta un nodo de la ruta
     */
    public function insertParada(PDO $db, array $data): int
    {
        // En frontend envían formato de lgs_envios_paradas, lo transformamos a lgs_envios_nodos
        $nodoData = [
            'id_envio'             => $data['id_envio'] ?? null,
            'orden'                => $data['orden'] ?? 1,
            'tipo_nodo'            => $data['tipo_nodo'] ?? 'entrega',
            'id_ubicacion'         => $data['id_destino_cat'] ?? null,
            'destino_nombre_libre' => $data['destino_nombre_libre'] ?? null,
            'km_tramo_anterior'    => $data['km_tramo'] ?? 0.00,
            'observaciones'        => $data['observaciones'] ?? null,
            'fecha_estimada'       => !empty($data['fecha_estimada']) ? str_replace('T', ' ', $data['fecha_estimada']) : null,
        ];
        
        $campos = $this->prepararCampos(self::SCHEMA['lgs_envios_nodos'], $nodoData);
        $keys = implode(', ', array_keys($campos));
        $placeholders = ':' . implode(', :', array_keys($campos));
        $sql = "INSERT INTO lgs_envios_nodos ({$keys}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute($campos);
        return (int) $db->lastInsertId();
    }

    /**
     * Inserta el nodo de origen (orden 0)
     */
    public function insertNodoOrigen(PDO $db, int $idEnvio, int $idUbicacionOrigen): int
    {
        $nodoData = [
            'id_envio'          => $idEnvio,
            'orden'             => 0,
            'tipo_nodo'         => 'origen',
            'id_ubicacion'      => $idUbicacionOrigen,
            'km_tramo_anterior' => 0.00
        ];
        $campos = $this->prepararCampos(self::SCHEMA['lgs_envios_nodos'], $nodoData);
        $keys = implode(', ', array_keys($campos));
        $placeholders = ':' . implode(', :', array_keys($campos));
        $sql = "INSERT INTO lgs_envios_nodos ({$keys}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute($campos);
        return (int) $db->lastInsertId();
    }

    /**
     * Elimina todos los nodos de un envío
     */
    public function deleteParadasEnvio(PDO $db, int $idEnvio): void
    {
        $stmt = $db->prepare("DELETE FROM lgs_envios_nodos WHERE id_envio = ?");
        $stmt->execute([$idEnvio]);
    }

    /**
     * Recalcula km_total del envío sumando todos los km_tramo de sus nodos
     * y actualiza id_destino con la última parada
     */
    public function actualizarKmTotalDesdeParadas(PDO $db, int $idEnvio): void
    {
        // Nota: Mantenemos la lógica pero basándonos en nodos.
        $sql = "UPDATE lgs_envios
                SET km_total   = COALESCE((SELECT SUM(km_tramo_anterior) FROM lgs_envios_nodos WHERE id_envio = ?), 0),
                    id_destino = COALESCE((SELECT id_ubicacion FROM lgs_envios_nodos WHERE id_envio = ? ORDER BY orden DESC LIMIT 1), id_destino)
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

    // ──────────────────────────────────────────────────────────────
    // MEMORIA PROGRESIVA DE DISTANCIAS (lgs_distancias)
    // ──────────────────────────────────────────────────────────────

    /**
     * Asegura de forma idempotente que la tabla lgs_distancias existe
     */
    public function ensureDistanciasTable(?PDO $db = null): void
    {
        static $checked = false;
        if ($checked) return;

        if ($db === null) $db = $this->getConexion();
        
        // En MySQL, los comandos DDL (CREATE, ALTER) causan un commit implícito,
        // lo cual destruye cualquier transacción activa (Ej. al guardar un envío).
        if ($db->inTransaction()) {
            return;
        }

        $checked = true;

        try {
            $sql = "CREATE TABLE IF NOT EXISTS `lgs_distancias` (
                `id_distancia`    INT AUTO_INCREMENT PRIMARY KEY,
                `id_ubicacion_a`  INT NOT NULL,
                `id_ubicacion_b`  INT NOT NULL,
                `km`              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_distancia_par` (`id_ubicacion_a`, `id_ubicacion_b`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $db->exec($sql);
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS `lgs_envios_nodos` (
                    `id_nodo` BIGINT AUTO_INCREMENT PRIMARY KEY,
                    `id_envio` BIGINT NOT NULL,
                    `orden` INT NOT NULL DEFAULT 1,
                    `tipo_nodo` VARCHAR(20) NOT NULL DEFAULT 'entrega',
                    `id_ubicacion` BIGINT NULL,
                    `destino_nombre_libre` VARCHAR(255) NULL,
                    `km_tramo_anterior` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `observaciones` TEXT NULL,
                    `fecha_estimada` DATETIME NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    KEY `idx_envio_orden` (`id_envio`, `orden`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` MODIFY COLUMN `fecha_tentativa_envio` DATETIME NULL");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` MODIFY COLUMN `fecha_tentativa_llegada` DATETIME NULL");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios_nodos` ADD COLUMN `tipo_nodo` VARCHAR(20) DEFAULT 'entrega' AFTER `orden`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios_nodos` ADD COLUMN `fecha_estimada` DATETIME NULL AFTER `observaciones`");
            } catch (Throwable $e) {}
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS `lgs_envios_tramos_costos` (
                    `id_tramo_costo` BIGINT AUTO_INCREMENT PRIMARY KEY,
                    `id_envio` BIGINT NOT NULL,
                    `id_madrina` BIGINT NULL,
                    `id_chofer` BIGINT NULL,
                    `id_nodo_origen` BIGINT NOT NULL,
                    `id_nodo_destino` BIGINT NOT NULL,
                    `km_tramo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `vins_ligeros` INT DEFAULT 0,
                    `vins_medianos` INT DEFAULT 0,
                    `vins_pesados` INT DEFAULT 0,
                    `vins_especiales` INT DEFAULT 0,
                    `factor_aplicado` DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
                    `costo_estimado` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                    `tarifa_usada_id` BIGINT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    KEY `idx_envio` (`id_envio`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios_vins` ADD COLUMN `id_nodo_subida` BIGINT NULL AFTER `id_parada`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios_vins` ADD COLUMN `id_nodo_bajada` BIGINT NULL AFTER `id_nodo_subida`");
            } catch (Throwable $e) {}
        } catch (Throwable $e) {
            // Silencioso si ya existe o no hay permisos DDL
        }
    }

    /**
     * Obtiene la distancia en KM entre dos ubicaciones (Bidireccional: busca con min y max)
     */
    public function getDistanciaEntre(int $idLocA, int $idLocB, ?PDO $db = null): ?float
    {
        if ($idLocA <= 0 || $idLocB <= 0 || $idLocA === $idLocB) {
            return ($idLocA === $idLocB && $idLocA > 0) ? 0.0 : null;
        }

        if ($db === null) $db = $this->getConexion();
        $this->ensureDistanciasTable($db);

        $idMin = min($idLocA, $idLocB);
        $idMax = max($idLocA, $idLocB);

        // 1. Buscar en memoria progresiva
        try {
            $stmt = $db->prepare("SELECT km FROM lgs_distancias WHERE id_ubicacion_a = ? AND id_ubicacion_b = ? LIMIT 1");
            $stmt->execute([$idMin, $idMax]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && floatval($row['km']) > 0) {
                return (float)$row['km'];
            }
        } catch (Throwable $e) {}

        // 2. Si no está en memoria, buscar en lgs_costos_rutas (Tarifario existente)
        try {
            $stmtTarifa = $db->prepare("SELECT km FROM lgs_costos_rutas 
                                        WHERE ((id_origen = ? AND id_destino = ?) OR (id_origen = ? AND id_destino = ?)) 
                                          AND km > 0 AND activo != 0 
                                        LIMIT 1");
            $stmtTarifa->execute([$idLocA, $idLocB, $idLocB, $idLocA]);
            $rowTarifa = $stmtTarifa->fetch(PDO::FETCH_ASSOC);
            if ($rowTarifa && floatval($rowTarifa['km']) > 0) {
                $km = (float)$rowTarifa['km'];
                // Guardar automáticamente en memoria progresiva para reutilización futura
                $this->saveDistancia($idMin, $idMax, $km, $db);
                return $km;
            }
        } catch (Throwable $e) {}

        return null;
    }

    /**
     * Guarda o actualiza la distancia en KM entre dos ubicaciones en la memoria progresiva
     */
    public function saveDistancia(int $idLocA, int $idLocB, float $km, ?PDO $db = null): bool
    {
        if ($idLocA <= 0 || $idLocB <= 0 || $idLocA === $idLocB || $km <= 0) {
            return false;
        }

        if ($db === null) $db = $this->getConexion();
        $this->ensureDistanciasTable($db);

        $idMin = min($idLocA, $idLocB);
        $idMax = max($idLocA, $idLocB);

        try {
            $stmt = $db->prepare("INSERT INTO lgs_distancias (id_ubicacion_a, id_ubicacion_b, km) 
                                  VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE km = VALUES(km), updated_at = NOW()");
            return $stmt->execute([$idMin, $idMax, round($km, 2)]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Verifica una cadena de nodos secuenciales.
     * Retorna si todas las distancias consecutivas son conocidas y la lista de tramos faltantes.
     */
    public function verificarDistanciasRuta(array $nodos, ?PDO $db = null): array
    {
        if ($db === null) $db = $this->getConexion();
        $this->ensureDistanciasTable($db);

        $faltantes = [];
        $tramos = [];
        $kmTotal = 0.0;

        // Obtener nombres de ubicaciones para el reporte
        $ubicacionesMap = [];
        try {
            $rows = $this->select_all("SELECT id_ubicacion, nombre FROM lgs_cat_ubicaciones");
            foreach ($rows as $r) {
                $ubicacionesMap[(int)$r['id_ubicacion']] = $r['nombre'];
            }
        } catch (Throwable $e) {}

        for ($i = 1; $i < count($nodos); $i++) {
            $nodoPrev = $nodos[$i - 1];
            $nodoCurr = $nodos[$i];

            $idA = intval($nodoPrev['id_ubicacion'] ?? $nodoPrev['id_destino_cat'] ?? 0);
            $idB = intval($nodoCurr['id_ubicacion'] ?? $nodoCurr['id_destino_cat'] ?? 0);

            $nomA = $ubicacionesMap[$idA] ?? ($nodoPrev['destino_nombre_libre'] ?? "Ubicación #{$idA}");
            $nomB = $ubicacionesMap[$idB] ?? ($nodoCurr['destino_nombre_libre'] ?? "Ubicación #{$idB}");

            $km = null;
            if ($idA > 0 && $idB > 0 && $idA === $idB) {
                // Si origen y destino del tramo son la misma ubicación, la distancia es 0 y no es faltante
                $km = 0.00;
            } elseif (!empty($nodoCurr['km_tramo']) && floatval($nodoCurr['km_tramo']) > 0) {
                $km = floatval($nodoCurr['km_tramo']);
                if ($idA > 0 && $idB > 0) {
                    $this->saveDistancia($idA, $idB, $km, $db);
                }
            } else {
                $km = ($idA > 0 && $idB > 0) ? $this->getDistanciaEntre($idA, $idB, $db) : null;
            }

            if (($km === null || $km <= 0) && ($idA !== $idB || $idA === 0 || $idB === 0)) {
                if ($idA !== $idB) {
                    $faltantes[] = [
                        'tramo_indice' => $i,
                        'id_ubicacion_a' => $idA,
                        'id_ubicacion_b' => $idB,
                        'nombre_a' => $nomA,
                        'nombre_b' => $nomB,
                    ];
                }
            } else {
                $kmTotal += ($km ?: 0.00);
            }

            $tramos[] = [
                'tramo_indice' => $i,
                'id_ubicacion_a' => $idA,
                'id_ubicacion_b' => $idB,
                'nombre_a' => $nomA,
                'nombre_b' => $nomB,
                'km' => $km ?: 0.00
            ];
        }

        return [
            'completo'  => empty($faltantes),
            'faltantes' => $faltantes,
            'tramos'    => $tramos,
            'km_total'  => round($kmTotal, 2)
        ];
    }

    /**
     * Obtiene TODOS los nodos (incluyendo el orden 0) de un envío
     */
    public function getNodosEnvio(int $idEnvio): array
    {
        // Intentar con columnas nuevas primero, fallback a columnas originales
        try {
            $sql = "SELECT 
                        n.id_nodo,
                        n.id_envio,
                        n.orden,
                        n.tipo_nodo,
                        n.id_ubicacion,
                        COALESCE(u.nombre, n.destino_nombre_libre, 'Sin Nombre') AS nombre,
                        u.direccion,
                        u.id_tipo_destino,
                        n.destino_nombre_libre,
                        n.km_tramo_anterior AS km_tramo,
                        n.observaciones,
                        n.fecha_estimada
                    FROM lgs_envios_nodos n
                    LEFT JOIN lgs_cat_ubicaciones u ON n.id_ubicacion = u.id_ubicacion
                    WHERE n.id_envio = ?
                    ORDER BY n.orden ASC";
            $res = $this->select_all($sql, [$idEnvio]);
            return $res ?: [];
        } catch (Throwable $e) {
            $sql = "SELECT 
                        n.id_nodo,
                        n.id_envio,
                        n.orden,
                        n.id_ubicacion,
                        COALESCE(u.nombre, n.destino_nombre_libre, 'Sin Nombre') AS nombre,
                        u.direccion,
                        u.id_tipo_destino,
                        n.destino_nombre_libre,
                        n.km_tramo_anterior AS km_tramo,
                        n.observaciones
                    FROM lgs_envios_nodos n
                    LEFT JOIN lgs_cat_ubicaciones u ON n.id_ubicacion = u.id_ubicacion
                    WHERE n.id_envio = ?
                    ORDER BY n.orden ASC";
            $res = $this->select_all($sql, [$idEnvio]);
            return $res ?: [];
        }
    }
}

