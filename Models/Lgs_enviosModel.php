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
            'es_preenvio',
            'id_tipo_traslado',
            'tipo_servicio',
            'modalidad_calculo',
            'es_monto_abierto',
            'monto_pactado',
            'motivo_monto_abierto',
            'is_lowboy',
            'madrina_con_telemetria',
            'km_real_telemetria',
            'confirmacion_cita_cliente',
            'contacto_confirmacion_cliente',
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
            'is_pausado',
            'motivo_pausa',
            'fecha_pausa',
            'pausado_by',
            'fecha_reanudacion',
            'reanudado_by',
            'notas_reanudacion',
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
            'id_numeros_serie',
            'id_destino',
            'id_parada',
            'id_nodo_subida',
            'id_nodo_bajada',
            'destino_nombre_libre',
            'id_madrina',
            'id_plataforma',
            'id_chofer',
            'posicion_acomodo',
            'costo_unidad',
            'tipo_servicio',
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
            'id_distribuidor',
            'id_origen_referencia',
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
     * Genera un nuevo folio transaccional EN-000001 (o PE-000001 para Pre-envíos)
     */
    public function generarFolioTransaccional(PDO $db, bool $esPreEnvio = false): string
    {
        $prefix = $esPreEnvio ? 'PE-' : 'EN-';
        $sql = "SELECT folio FROM lgs_envios WHERE folio LIKE ? ORDER BY id_envio DESC LIMIT 1 FOR UPDATE";
        $stmt = $db->prepare($sql);
        $stmt->execute([$prefix . '%']);
        $ultimo = $stmt->fetchColumn();
        
        if (!$ultimo) {
            return $prefix . '000001';
        }
        
        preg_match('/\d+$/', $ultimo, $matches);
        $num = !empty($matches[0]) ? (intval($matches[0]) + 1) : 1;
        return $prefix . str_pad((string)$num, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene todos los envíos (o pre-envíos si $esPreEnvio = 1) activos para el Datatable
     */
    public function getEnviosDataTable(int $esPreEnvio = 0): array
    {
        $this->ensureDistanciasTable();
        $esPreEnvio = ($esPreEnvio === 1) ? 1 : 0;

        try {
            $sql = "SELECT 
                        e.id_envio, 
                        e.folio, 
                        COALESCE(e.es_preenvio, 0) AS es_preenvio,
                        tt.nombre AS tipo_traslado,
                        mo.descripcion AS motivo,
                        pr.razon_social AS trasladista,
                        e.modalidad_calculo,
                        e.tipo_servicio,
                        e.es_monto_abierto,
                        e.monto_pactado,
                        e.motivo_monto_abierto,
                        COALESCE(
                            (SELECT COALESCE(u0.nombre, n0.destino_nombre_libre) FROM lgs_envios_nodos n0 LEFT JOIN lgs_cat_ubicaciones u0 ON n0.id_ubicacion = u0.id_ubicacion WHERE n0.id_envio = e.id_envio AND n0.orden = 0 LIMIT 1),
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
                        CASE 
                            WHEN (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) = 0 THEN 0.00 
                            ELSE COALESCE(e.costo_total, 0.00) 
                        END AS costo_total,
                        e.fecha_tentativa_envio,
                        e.created_at,
                        e.id_estado,
                        COALESCE(e.is_pausado, 0) AS is_pausado,
                        e.motivo_pausa,
                        e.fecha_pausa,
                        e.fecha_reanudacion,
                        e.notas_reanudacion,
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
                      AND COALESCE(e.es_preenvio, 0) = ?
                    ORDER BY e.id_envio DESC";
            
            $request = $this->select_all($sql, [$esPreEnvio]);
            return $this->attachSolicitudesPreEnvio($request ?: [], $esPreEnvio);
        } catch (Throwable $e) {
            error_log("Fallo consulta principal getEnviosDataTable: " . $e->getMessage() . " -> Ejecutando fallback de compatibilidad");
            $sqlFallback = "SELECT 
                        e.id_envio, 
                        e.folio, 
                        CASE WHEN e.folio LIKE 'PE-%' THEN 1 ELSE 0 END AS es_preenvio,
                        tt.nombre AS tipo_traslado,
                        mo.descripcion AS motivo,
                        pr.razon_social AS trasladista,
                        e.modalidad_calculo,
                        e.tipo_servicio,
                        e.es_monto_abierto,
                        e.monto_pactado,
                        e.motivo_monto_abierto,
                        COALESCE(
                            (SELECT COALESCE(u0.nombre, n0.destino_nombre_libre) FROM lgs_envios_nodos n0 LEFT JOIN lgs_cat_ubicaciones u0 ON n0.id_ubicacion = u0.id_ubicacion WHERE n0.id_envio = e.id_envio AND n0.orden = 0 LIMIT 1),
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
                        CASE 
                            WHEN (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) = 0 THEN 0.00 
                            ELSE COALESCE(e.costo_total, 0.00) 
                        END AS costo_total,
                        e.fecha_tentativa_envio,
                        e.created_at,
                        e.id_estado,
                        0 AS is_pausado,
                        NULL AS motivo_pausa,
                        NULL AS fecha_pausa,
                        NULL AS fecha_reanudacion,
                        NULL AS notas_reanudacion,
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
                      AND (CASE WHEN e.folio LIKE 'PE-%' THEN 1 ELSE 0 END) = ?
                    ORDER BY e.id_envio DESC";
            $resFallback = $this->select_all($sqlFallback, [$esPreEnvio]);
            return $this->attachSolicitudesPreEnvio($resFallback ?: [], $esPreEnvio);
        }
    }

    /**
     * Adjunta la última solicitud de autorización excepcional (si existe) a los registros de Pre-envíos / Envíos.
     */
    private function attachSolicitudesPreEnvio(array $rows, int $esPreEnvio = 0): array
    {
        if (empty($rows)) {
            return $rows;
        }
        try {
            $this->ensureSolicitudesPreEnvioTable();
            $ids = array_values(array_filter(array_map(fn($r) => intval($r['id_envio'] ?? 0), $rows)));
            if (empty($ids)) {
                return $rows;
            }
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $sols = $this->select_all("
                SELECT s.*
                FROM lgs_solicitudes_preenvio_envio s
                INNER JOIN (
                    SELECT id_envio, MAX(id_solicitud) AS max_id
                    FROM lgs_solicitudes_preenvio_envio
                    WHERE id_envio IN ({$ph})
                    GROUP BY id_envio
                ) latest ON latest.max_id = s.id_solicitud
            ", $ids) ?: [];
            $mapSol = [];
            foreach ($sols as $s) {
                $mapSol[intval($s['id_envio'])] = $s;
            }
            foreach ($rows as &$r) {
                $idE = intval($r['id_envio'] ?? 0);
                $s = $mapSol[$idE] ?? null;
                $r['solicitud_id']            = $s ? intval($s['id_solicitud']) : 0;
                $r['solicitud_folio']         = $s ? (string)$s['folio_solicitud'] : '';
                $r['solicitud_estatus']       = $s ? (string)$s['estatus'] : '';
                $r['solicitud_obs_aprobador'] = $s ? (string)($s['obs_aprobador'] ?? '') : '';
            }
            unset($r);
        } catch (Throwable $e) {
            // Silencioso si no existe tabla aún
        }
        return $rows;
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
                    ['id' => 2, 'nombre' => 'Chofer (Rodando)'],
                    ['id' => 3, 'nombre' => 'Plataforma'],
                ];
            }
        } catch (Throwable $e) {
            $tiposTraslado = [
                ['id' => 1, 'nombre' => 'Madrina'],
                ['id' => 2, 'nombre' => 'Chofer (Rodando)'],
                ['id' => 3, 'nombre' => 'Plataforma'],
            ];
        }

        // 2. Motivos
        try {
            $motivos = $this->select_all("SELECT id_motivo AS id, descripcion AS nombre FROM lgs_cat_motivo_envio WHERE activo = 1 ORDER BY descripcion ASC");
            if (empty($motivos)) {
                $motivos = [
                    ['id' => 1, 'nombre' => 'Entrega a Distribuidor'],
                    ['id' => 2, 'nombre' => 'Entrega a Cliente Final'],
                    ['id' => 3, 'nombre' => 'Traslado a Carrocería'],
                    ['id' => 4, 'nombre' => 'Traslado entre Almacenes'],
                    ['id' => 5, 'nombre' => 'Traslado a Planta'],
                    ['id' => 6, 'nombre' => 'Marketing / Exposición'],
                    ['id' => 7, 'nombre' => 'Devolución de Unidad'],
                    ['id' => 8, 'nombre' => 'Otro motivo']
                ];
            }
        } catch (Throwable $e) {
            $motivos = [
                ['id' => 1, 'nombre' => 'Entrega a Distribuidor'],
                ['id' => 2, 'nombre' => 'Entrega a Cliente Final'],
                ['id' => 3, 'nombre' => 'Traslado a Carrocería'],
                ['id' => 4, 'nombre' => 'Traslado entre Almacenes'],
                ['id' => 5, 'nombre' => 'Traslado a Planta'],
                ['id' => 6, 'nombre' => 'Marketing / Exposición'],
                ['id' => 7, 'nombre' => 'Devolución de Unidad'],
                ['id' => 8, 'nombre' => 'Otro motivo']
            ];
        }

        // 3. Proveedores / Trasladistas
        try {
            $sqlProv = "SELECT p.id_proveedor AS id, CONCAT(p.razon_social, ' (', p.rfc, ')') AS nombre, p.modalidad_envio_default 
                        FROM prv_cat_proveedores p
                        INNER JOIN prv_rel_proveedores_actividades r ON r.id_proveedor = p.id_proveedor
                        INNER JOIN prv_cat_actividades a ON a.id_actividad = r.id_actividad
                        WHERE a.cve_actividad = 'TRASLADO_UNIDADES' AND p.deleted_at IS NULL
                        ORDER BY p.razon_social ASC";
            $proveedores = $this->select_all($sqlProv);
            if (empty($proveedores)) {
                $proveedores = $this->select_all("SELECT id_proveedor AS id, razon_social AS nombre, modalidad_envio_default FROM prv_cat_proveedores WHERE deleted_at IS NULL ORDER BY razon_social ASC");
            }
        } catch (Throwable $e) {
            try {
                $proveedores = $this->select_all("SELECT id_proveedor AS id, razon_social AS nombre, modalidad_envio_default FROM prv_cat_proveedores WHERE deleted_at IS NULL ORDER BY razon_social ASC");
            } catch (Throwable $e2) {
                $proveedores = [];
            }
        }

        // 4. Ubicaciones y sincronización maestra de Clientes / Sedes
        try {
            if (class_exists('Lgs_clientesSyncService')) {
                (new Lgs_clientesSyncService($this->getConexion()))->sincronizarDistribuidoresYSedes();
            }
            $this->sincronizarPlantasYAlmacenes();
            $ubicaciones = $this->select_all("
                SELECT u.id_ubicacion AS id, u.nombre, u.direccion, u.lat, u.lng,
                       u.id_tipo_destino, u.id_distribuidor, u.id_cliente, u.id_sucursal_cli, u.id_direccion_cli,
                       COALESCE(a.plantaid, u.id_planta_mrp) AS id_planta_mrp,
                       u.id_almacen_wms,
                       a.cve_almacen,
                       COALESCE(a.pt, 0) AS almacen_pt,
                       mp.cve_planta,
                       mp.nombre_planta
                FROM lgs_cat_ubicaciones u
                LEFT JOIN wms_almacenes a ON a.idalmacen = u.id_almacen_wms
                LEFT JOIN mrp_planta mp ON mp.idplanta = COALESCE(a.plantaid, u.id_planta_mrp)
                WHERE u.activo = 1
                ORDER BY u.nombre ASC
            ") ?: [];
        } catch (Throwable $e) {
            $ubicaciones = [];
        }

        // Plantas y sus Subalmacenes PT (wms_almacenes.pt = 1) para selección en cascada
        $plantasWmsMap = [];
        foreach ($ubicaciones as $u) {
            if (intval($u['id_almacen_wms'] ?? 0) <= 0 || intval($u['almacen_pt'] ?? 0) !== 1) {
                continue;
            }
            $idPlanta = intval($u['id_planta_mrp'] ?? 0);
            if (!isset($plantasWmsMap[$idPlanta])) {
                $plantasWmsMap[$idPlanta] = [
                    'id_planta'     => $idPlanta,
                    'cve_planta'    => (string)($u['cve_planta'] ?? ($idPlanta > 0 ? 'PL' . $idPlanta : 'PT')),
                    'nombre_planta' => (string)($u['nombre_planta'] ?? ($idPlanta > 0 ? ('Planta #' . $idPlanta) : 'Almacenes PT Generales')),
                    'subalmacenes'  => [],
                ];
            }
            $plantasWmsMap[$idPlanta]['subalmacenes'][] = [
                'id_ubicacion'   => intval($u['id']),
                'id_almacen_wms' => intval($u['id_almacen_wms']),
                'cve_almacen'    => (string)($u['cve_almacen'] ?? ''),
                'nombre'         => (string)$u['nombre'],
                'direccion'      => (string)($u['direccion'] ?? ''),
                'pt'             => 1,
            ];
        }
        $plantasWms = array_values($plantasWmsMap);
        usort($plantasWms, static function (array $a, array $b): int {
            return strcmp(($a['cve_planta'] ?? '') . ($a['nombre_planta'] ?? ''), ($b['cve_planta'] ?? '') . ($b['nombre_planta'] ?? ''));
        });

        // Orígenes PT: almacenes activos con pt = 1 en wms_almacenes
        $origenes = array_filter($ubicaciones, function($u) {
            return intval($u['id_almacen_wms'] ?? 0) > 0 && intval($u['almacen_pt'] ?? 0) === 1;
        });
        
        // Destinos: TODAS las ubicaciones (incluyendo Plantas para traslados interplanta, Almacenes y Distribuidores)
        $destinos = $ubicaciones;

        // Aseguramos que los arrays estén indexados numéricamente
        $origenes = array_values(!empty($origenes) ? $origenes : $ubicaciones);
        $destinos = array_values($destinos);

        // 5. Distribuidores (reutiliza consulta deduplicada de Lgs_ubicacionesModel)
        try {
            $ubicModel = new Lgs_ubicacionesModel();
            $distribuidores = $ubicModel->getDistribuidores();
        } catch (Throwable $e) {
            $distribuidores = [];
        }

        // 6. Catálogo de Clientes maestros (`cli_clientes`) enlazados a Logística
        try {
            $clientes = $this->select_all("
                SELECT c.idcliente AS id_cliente,
                       c.codigo_cliente,
                       c.razon_social,
                       c.nombre_comercial,
                       COALESCE(NULLIF(TRIM(c.razon_social), ''), NULLIF(TRIM(c.nombre_comercial), ''), c.codigo_cliente) AS nombre,
                       d.id_distribuidor
                FROM cli_clientes c
                LEFT JOIN lgs_cat_distribuidores d ON d.id_cliente = c.idcliente AND d.activo = 1
                WHERE c.estado = 2
                  AND (c.idtipo_cliente = 1 OR d.id_distribuidor IS NOT NULL)
                ORDER BY nombre ASC
            ") ?: [];
        } catch (Throwable $e) {
            $clientes = [];
        }

        return [
            'tipos_traslado' => $tiposTraslado,
            'motivos'        => $motivos,
            'proveedores'    => $proveedores,
            'origenes'       => $origenes,
            'plantas_wms'    => $plantasWms,
            'destinos'       => $destinos,
            'ubicaciones'    => $ubicaciones,
            'distribuidores' => $distribuidores ?: [],
            'clientes'       => $clientes,
        ];
    }

    /**
     * Sincroniza automáticamente las plantas (mrp_planta) y almacenes (wms_almacenes) en lgs_cat_ubicaciones
     */
    public function sincronizarPlantasYAlmacenes(): void
    {
        try {
            $db = $this->getConexion();
            
            // 1. Asegurar columnas de relación en lgs_cat_ubicaciones
            $col1 = $db->query("SHOW COLUMNS FROM lgs_cat_ubicaciones LIKE 'id_planta_mrp'")->fetch();
            if (!$col1) {
                $db->exec("ALTER TABLE lgs_cat_ubicaciones ADD COLUMN id_planta_mrp INT NULL AFTER id_distribuidor");
            }
            $col2 = $db->query("SHOW COLUMNS FROM lgs_cat_ubicaciones LIKE 'id_almacen_wms'")->fetch();
            if (!$col2) {
                $db->exec("ALTER TABLE lgs_cat_ubicaciones ADD COLUMN id_almacen_wms INT NULL AFTER id_planta_mrp");
            }

            // 2. Sincronizar mrp_planta (Tipo 5: Planta) sin mezclar almacenes pt=0
            $db->exec("
                UPDATE lgs_cat_ubicaciones u
                INNER JOIN wms_almacenes a ON a.idalmacen = u.id_almacen_wms
                SET u.id_almacen_wms = NULL
                WHERE u.id_tipo_destino = 5 AND COALESCE(a.pt, 0) = 0
            ");
            $plantas = $this->select_all("SELECT idplanta, nombre_planta, direccion FROM mrp_planta WHERE estado != 0") ?: [];
            foreach ($plantas as $p) {
                $nom = trim($p['nombre_planta'] ?? '');
                if (empty($nom)) continue;
                $exist = $this->select("SELECT id_ubicacion FROM lgs_cat_ubicaciones WHERE id_planta_mrp = ? AND id_tipo_destino = 5 LIMIT 1", [$p['idplanta']]);
                if (!$exist) {
                    $exist = $this->select("SELECT id_ubicacion FROM lgs_cat_ubicaciones WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) AND id_almacen_wms IS NULL LIMIT 1", [$nom]);
                }
                if ($exist) {
                    $db->prepare("UPDATE lgs_cat_ubicaciones SET nombre = ?, direccion = COALESCE(NULLIF(?, ''), direccion), id_planta_mrp = ?, id_tipo_destino = 5, activo = 1 WHERE id_ubicacion = ?")
                       ->execute([$nom, $p['direccion'] ?? null, $p['idplanta'], $exist['id_ubicacion']]);
                } else {
                    $db->prepare("INSERT INTO lgs_cat_ubicaciones (nombre, direccion, id_tipo_destino, id_planta_mrp, activo) VALUES (?, ?, 5, ?, 1)")
                       ->execute([$nom, $p['direccion'] ?? null, $p['idplanta']]);
                }
            }
            // Ocultar plantas desactivadas en mrp_planta
            $db->exec("
                UPDATE lgs_cat_ubicaciones u
                INNER JOIN mrp_planta p ON p.idplanta = u.id_planta_mrp
                SET u.activo = 0
                WHERE u.id_tipo_destino = 5
                  AND u.id_planta_mrp IS NOT NULL
                  AND p.estado = 0
            ");

            // 3. Sincronizar únicamente wms_almacenes activos de Producto Terminado (pt = 1) ligados a su plantaid (Tipo 4: Almacén)
            $almacenes = $this->select_all("
                SELECT idalmacen, plantaid, descripcion, direccion
                FROM wms_almacenes
                WHERE estado = 2 AND pt = 1
            ") ?: [];
            foreach ($almacenes as $a) {
                $nom = trim($a['descripcion'] ?? '');
                if (empty($nom)) continue;
                $plantaIdAlm = !empty($a['plantaid']) ? intval($a['plantaid']) : null;
                $exist = $this->select("SELECT id_ubicacion FROM lgs_cat_ubicaciones WHERE id_almacen_wms = ? LIMIT 1", [$a['idalmacen']]);
                if (!$exist) {
                    $exist = $this->select("SELECT id_ubicacion FROM lgs_cat_ubicaciones WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) AND id_distribuidor IS NULL LIMIT 1", [$nom]);
                }
                if ($exist) {
                    $db->prepare("UPDATE lgs_cat_ubicaciones SET nombre = ?, direccion = COALESCE(NULLIF(?, ''), direccion), id_almacen_wms = ?, id_planta_mrp = ?, id_tipo_destino = 4, activo = 1 WHERE id_ubicacion = ?")
                       ->execute([$nom, $a['direccion'] ?? null, $a['idalmacen'], $plantaIdAlm, $exist['id_ubicacion']]);
                } else {
                    $db->prepare("INSERT INTO lgs_cat_ubicaciones (nombre, direccion, id_tipo_destino, id_planta_mrp, id_almacen_wms, activo) VALUES (?, ?, 4, ?, ?, 1)")
                       ->execute([$nom, $a['direccion'] ?? null, $plantaIdAlm, $a['idalmacen']]);
                }
            }
            // Ocultar del selector ubicaciones que apuntaban a almacenes no-PT (pt=0), inactivos o eliminados de wms_almacenes
            $db->exec("
                UPDATE lgs_cat_ubicaciones u
                LEFT JOIN wms_almacenes a ON a.idalmacen = u.id_almacen_wms
                SET u.activo = 0
                WHERE u.id_almacen_wms IS NOT NULL
                  AND u.id_tipo_destino = 4
                  AND (a.idalmacen IS NULL OR a.estado <> 2 OR COALESCE(a.pt, 0) = 0)
            ");
        } catch (Throwable $e) {
            // Manejo silencioso para no interrumpir
        }
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

            // 3. Sincronizar Plantas y Almacenes (mrp_planta) tanto en Destinos (tipo 5) como en Orígenes
            $sqlPlantas = "SELECT idplanta, nombre_planta, direccion FROM mrp_planta WHERE nombre_planta IS NOT NULL AND TRIM(nombre_planta) <> ''";
            $plantasRows = $this->select_all($sqlPlantas) ?: [];
            foreach ($plantasRows as $pl) {
                $nomPlanta = trim($pl['nombre_planta']);
                $dirPlanta = trim($pl['direccion'] ?? '');
                
                // Asegurar en lgs_cat_origenes
                $existOrig = $this->select("SELECT id_origen FROM lgs_cat_origenes WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1", [$nomPlanta]);
                if (empty($existOrig)) {
                    $this->insert("INSERT INTO lgs_cat_origenes (nombre, direccion, activo) VALUES (?, ?, 1)", [$nomPlanta, $dirPlanta]);
                }

                // Agregar a lista de destinos con tipo 5 = PLANTA
                $keyPlanta = mb_strtolower($nomPlanta);
                if (!isset($lista[$keyPlanta])) {
                    $lista[$keyPlanta] = ['nombre' => $nomPlanta, 'direccion' => $dirPlanta, 'tipo' => 5];
                }
            }

            // 4. Insertar los faltantes en lgs_cat_destinos
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
    public function getPlaneacionEstadoByEnvio(int $idEnvio): ?int
    {
        $sql = "SELECT p.id_estado 
                FROM lgs_planeaciones p 
                INNER JOIN lgs_planeaciones_envios pe ON p.id_planeacion = pe.id_planeacion 
                WHERE pe.id_envio = ? 
                LIMIT 1";
        $db = $this->getConexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([$idEnvio]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res && isset($res['id_estado'])) {
            return intval($res['id_estado']);
        }
        return null;
    }

    public function getEnvioCabecera(int $idEnvio): array
    {
        try {
            $sql = "SELECT 
                        e.id_envio,
                        e.folio,
                        COALESCE(e.es_preenvio, 0) AS es_preenvio,
                        e.id_tipo_traslado,
                        e.tipo_servicio,
                        e.modalidad_calculo,
                        e.es_monto_abierto,
                        e.monto_pactado,
                        e.motivo_monto_abierto,
                        e.is_lowboy,
                        e.id_motivo,
                        e.id_proveedor,
                        pr.razon_social AS trasladista,
                        e.id_origen,
                        COALESCE(
                            (SELECT COALESCE(u0.nombre, n0.destino_nombre_libre) FROM lgs_envios_nodos n0 LEFT JOIN lgs_cat_ubicaciones u0 ON n0.id_ubicacion = u0.id_ubicacion WHERE n0.id_envio = e.id_envio AND n0.orden = 0 LIMIT 1),
                            o.nombre,
                            'Sin Origen'
                        ) AS origen,
                        e.id_destino,
                        e.destino_nombre_libre,
                        COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social, d.nombre, e.destino_nombre_libre, 'Sin Destino') AS destino,
                        e.km_total,
                        e.costo_total,
                        e.fecha_tentativa_envio,
                        e.fecha_tentativa_llegada,
                        e.observaciones,
                        e.id_estado,
                        COALESCE(e.is_pausado, 0) AS is_pausado,
                        e.motivo_pausa,
                        e.fecha_pausa,
                        e.pausado_by,
                        e.fecha_reanudacion,
                        e.reanudado_by,
                        e.notas_reanudacion,
                        COALESCE(e.madrina_con_telemetria, 0) AS madrina_con_telemetria,
                        e.km_real_telemetria,
                        COALESCE(e.confirmacion_cita_cliente, 0) AS confirmacion_cita_cliente,
                        e.contacto_confirmacion_cliente
                    FROM lgs_envios e
                    LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                    LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                    LEFT JOIN cli_clientes c ON e.id_destino = c.idcliente
                    LEFT JOIN lgs_cat_destinos d ON e.id_destino = d.id_destino
                    WHERE e.id_envio = ? AND e.deleted_at IS NULL";
            $res = $this->select($sql, [$idEnvio]);
            return $res ?: [];
        } catch (Throwable $e) {
            $sqlFallback = "SELECT 
                        e.id_envio,
                        e.folio,
                        CASE WHEN e.folio LIKE 'PE-%' THEN 1 ELSE 0 END AS es_preenvio,
                        e.id_tipo_traslado,
                        e.tipo_servicio,
                        e.modalidad_calculo,
                        e.es_monto_abierto,
                        e.monto_pactado,
                        e.motivo_monto_abierto,
                        e.is_lowboy,
                        e.id_motivo,
                        e.id_proveedor,
                        pr.razon_social AS trasladista,
                        e.id_origen,
                        COALESCE(
                            (SELECT COALESCE(u0.nombre, n0.destino_nombre_libre) FROM lgs_envios_nodos n0 LEFT JOIN lgs_cat_ubicaciones u0 ON n0.id_ubicacion = u0.id_ubicacion WHERE n0.id_envio = e.id_envio AND n0.orden = 0 LIMIT 1),
                            o.nombre,
                            'Sin Origen'
                        ) AS origen,
                        e.id_destino,
                        e.destino_nombre_libre,
                        COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social, d.nombre, e.destino_nombre_libre, 'Sin Destino') AS destino,
                        e.km_total,
                        e.costo_total,
                        e.fecha_tentativa_envio,
                        e.fecha_tentativa_llegada,
                        e.observaciones,
                        e.id_estado,
                        0 AS is_pausado,
                        NULL AS motivo_pausa,
                        NULL AS fecha_pausa,
                        NULL AS fecha_reanudacion,
                        NULL AS notas_reanudacion,
                        COALESCE(e.madrina_con_telemetria, 0) AS madrina_con_telemetria,
                        e.km_real_telemetria,
                        COALESCE(e.confirmacion_cita_cliente, 0) AS confirmacion_cita_cliente,
                        e.contacto_confirmacion_cliente
                    FROM lgs_envios e
                    LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                    LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                    LEFT JOIN cli_clientes c ON e.id_destino = c.idcliente
                    LEFT JOIN lgs_cat_destinos d ON e.id_destino = d.id_destino
                    WHERE e.id_envio = ? AND e.deleted_at IS NULL";
            $res = $this->select($sqlFallback, [$idEnvio]);
            return $res ?: [];
        }
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
     * Obtiene las plataformas activas pertenecientes al proveedor del envío
     */
    public function getPlataformasPorProveedor(int $idProveedor): array
    {
        $sql = "SELECT 
                    m.id_plataforma,
                    m.numero_economico,
                    m.placas,
                    m.marca,
                    m.modelo,
                    m.capacidad_vehiculos,
                    (SELECT CONCAT(c.nombre, ' ', c.apellidos) 
                     FROM prv_det_plataforma_chofer_historial h
                     INNER JOIN prv_det_choferes c ON c.id_chofer = h.id_chofer
                     WHERE h.id_plataforma = m.id_plataforma AND h.activo = 1 LIMIT 1) AS chofer_asignado
                FROM prv_det_plataformas m
                WHERE (m.id_proveedor = ? OR ? = 0) AND m.deleted_at IS NULL
                ORDER BY m.numero_economico ASC";
        $res = $this->select_all($sql, [$idProveedor, $idProveedor]);
        return $res ?: [];
    }

    /**
     * Obtiene VINs disponibles para Acomodo:
     * - Si es Pre-envío (PE-): muestra TODAS las unidades PT vigentes sin bloquear entre pre-envíos.
     * - Si es Envío Oficial (EN-): muestra las unidades en Bandeja Operativa (autorizadas y no autorizadas)
     *   más las unidades PT facturadas o con autorización de movimiento/excepción aprobada.
     *   Cada unidad incluye semáforo `se_envia` (1/0) y motivo detallado.
     */
    public function getVinsDisponiblesOrigen(int $idOrigen = 0, int $idEnvioActual = 0): array
    {
        try {
            $cab = $idEnvioActual > 0 ? $this->getEnvioCabecera($idEnvioActual) : [];
            $esPreEnvio = (intval($cab['es_preenvio'] ?? 0) === 1 || strpos((string)($cab['folio'] ?? ''), 'PE-') === 0);

            // 1. Obtener los VINs ya acomodados en algún vehículo dentro de ESTE mismo envío/pre-envío para excluirlos del pool
            $yaEnEsteEnvioUnidad = [];
            $yaEnEsteEnvioVin    = [];
            if ($idEnvioActual > 0) {
                $rowsActual = $this->select_all("
                    SELECT ev.id_unidad, UPPER(TRIM(COALESCE(u.vin, ns.numero_serie, ''))) AS vin
                    FROM lgs_envios_vins ev
                    INNER JOIN lgs_envios e ON e.id_envio = ev.id_envio
                    LEFT JOIN lgs_unidades_envios u ON u.id_unidad = ev.id_unidad
                    LEFT JOIN wms_numeros_series ns ON ns.id_numeros_serie = ev.id_numeros_serie
                    WHERE ev.id_envio = ?
                      AND (ev.id_madrina IS NOT NULL OR ev.id_plataforma IS NOT NULL OR ev.id_chofer IS NOT NULL OR e.id_tipo_traslado = 3)
                ", [$idEnvioActual]) ?: [];
                foreach ($rowsActual as $ra) {
                    if (!empty($ra['id_unidad'])) {
                        $yaEnEsteEnvioUnidad[intval($ra['id_unidad'])] = true;
                    }
                    $vk = strtoupper(trim((string)($ra['vin'] ?? '')));
                    if ($vk !== '') {
                        $yaEnEsteEnvioVin[$vk] = true;
                    }
                }
            }

            $vinsExcepcionAprobada = $idEnvioActual > 0 ? $this->getVinsAprobadosExcepcion($idEnvioActual) : [];

            // 2. Pool unificado desde Bandeja / WMS PT
            if (file_exists("Models/Lgs_bandejaModel.php")) {
                require_once "Models/Lgs_bandejaModel.php";
                $bandejaModel = new Lgs_bandejaModel();
                $ptRows = $bandejaModel->getUnidadesPTGlobales([]);

                $res = [];
                foreach ($ptRows as $pt) {
                    $vinClean = strtoupper(trim((string)($pt['vin'] ?? '')));
                    $idU      = intval($pt['id_unidad'] ?? 0);
                    if ($vinClean === '') continue;
                    if (($idU > 0 && isset($yaEnEsteEnvioUnidad[$idU])) || isset($yaEnEsteEnvioVin[$vinClean])) {
                        continue;
                    }

                    $enBandeja      = !empty($pt['en_bandeja']) || intval($pt['id_lgs_unidad'] ?? 0) > 0;
                    $estadoProc     = intval($pt['id_estado_proceso'] ?? 0);
                    $cumpleFactura  = !empty($pt['cumple_factura']);
                    $cumpleAut      = !empty($pt['cumple_autorizacion']) || intval($pt['activado_comercial'] ?? 0) === 1;
                    $autExcepcion   = isset($vinsExcepcionAprobada[$vinClean]);
                    $envIdOf        = intval($pt['envio_id'] ?? 0);

                    // En Envíos Oficiales (EN-) excluimos PT vírgenes sin bandeja, sin factura ni autorización
                    if (!$esPreEnvio) {
                        $enBandejaDisponible = ($enBandeja && $estadoProc <= 1);
                        $yaEnEsteOficial     = ($envIdOf > 0 && $envIdOf === $idEnvioActual);
                        if (!$enBandejaDisponible && !$yaEnEsteOficial && !$cumpleFactura && !$cumpleAut && !$autExcepcion) {
                            continue;
                        }
                    }

                    $asignadoFolio = ($envIdOf > 0 && $envIdOf !== $idEnvioActual)
                        ? (string)($pt['envio_folio'] ?? ('EN-' . $envIdOf))
                        : null;

                    $origenTxt = trim((string)($pt['planta_nombre'] ?? ''));
                    if (!empty($pt['almacen_nombre'])) {
                        $origenTxt = ($origenTxt !== '' ? $origenTxt . ' · ' : '') . $pt['almacen_nombre'];
                    }
                    $destTxt = trim((string)($pt['sede_nombre'] ?: $pt['cliente_nombre'] ?: $pt['destino_sugerido'] ?: 'Sin Asignar'));

                    $seEnvia = !empty($pt['se_envia']) || $autExcepcion;
                    $motivoEnvio = $autExcepcion && empty($pt['se_envia'])
                        ? 'Autorizada por Solicitud Excepcional'
                        : (string)($pt['estado_envio_motivo'] ?? 'Sin Factura ni Aut. Movimiento');

                    $res[] = [
                        'id_unidad'                => $idU,
                        'id_numeros_serie'         => intval($pt['id_numeros_serie'] ?? 0),
                        'id_lgs_unidad'            => intval($pt['id_lgs_unidad'] ?? 0),
                        'en_bandeja'               => $enBandeja ? 1 : 0,
                        'id_estado_proceso'        => $estadoProc,
                        'vin'                      => $vinClean,
                        'num_serie'                => (string)($pt['num_serie'] ?? ''),
                        'modelo'                   => (string)($pt['modelo'] ?? 'Unidad PT'),
                        'origen'                   => $origenTxt !== '' ? $origenTxt : 'Planta / PT',
                        'destino'                  => $destTxt,
                        'id_cliente'               => intval($pt['id_cliente'] ?? 0),
                        'cliente_nombre'           => (string)($pt['cliente_nombre'] ?? ''),
                        'sede_nombre'              => (string)($pt['sede_nombre'] ?? ''),
                        'folio_pedido'             => (string)($pt['folio_pedido'] ?? ''),
                        'factura'                  => (string)($pt['factura'] ?? ''),
                        'facturado'                => intval($pt['facturado'] ?? 0),
                        'estatus_pago_cod'         => intval($pt['estatus_pago_cod'] ?? -1),
                        'estatus_pago_texto'       => (string)($pt['estatus_pago_texto'] ?? 'Sin Facturar'),
                        'estatus_finanzas_bandeja' => (string)($pt['estatus_finanzas_bandeja'] ?? ''),
                        'activado_comercial'       => intval($pt['activado_comercial'] ?? 0),
                        'cumple_factura'           => !empty($pt['cumple_factura']) ? 1 : 0,
                        'cumple_pago'              => !empty($pt['cumple_pago']) ? 1 : 0,
                        'cumple_autorizacion'      => $cumpleAut ? 1 : 0,
                        'autorizado_excepcion'     => $autExcepcion ? 1 : 0,
                        'se_envia'                 => $seEnvia ? 1 : 0,
                        'estado_envio_motivo'      => $motivoEnvio,
                        'preenvio_folios'          => (string)($pt['preenvio_folios'] ?? ''),
                        'asignado_folio'           => $asignadoFolio,
                    ];
                }
                return $res;
            }
            return [];
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
                        COALESCE(v.id_numeros_serie, u.id_numeros_serie, 0) AS id_numeros_serie,
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
                        v.id_plataforma,
                        v.id_chofer,
                        v.id_parada,
                        v.id_nodo_subida,
                        v.id_nodo_bajada,
                        v.posicion_acomodo,
                        v.costo_unidad,
                        v.tipo_servicio,
                        m.numero_economico AS madrina_nombre,
                        COALESCE(p.numero_economico, IF(e.id_tipo_traslado = 3, 'Plataforma Subcontratada', NULL)) AS plataforma_nombre,
                        CONCAT(c.nombre, ' ', c.apellidos) AS chofer_nombre
                    FROM lgs_envios_vins v
                    INNER JOIN lgs_envios e ON v.id_envio = e.id_envio
                    LEFT JOIN lgs_unidades_envios u ON v.id_unidad = u.id_unidad
                    LEFT JOIN lgs_unidades lu ON lu.id_unidad = v.id_unidad
                    LEFT JOIN mrp_unidades_terminadas ut ON v.id_unidad = ut.idunidad
                    LEFT JOIN prv_det_madrinas m ON v.id_madrina = m.id_madrina
                    LEFT JOIN prv_det_plataformas p ON v.id_plataforma = p.id_plataforma
                    LEFT JOIN prv_det_choferes c ON v.id_chofer = c.id_chofer
                    WHERE v.id_envio = ?
                      AND (v.id_madrina IS NOT NULL OR v.id_plataforma IS NOT NULL OR v.id_chofer IS NOT NULL OR e.id_tipo_traslado = 3)
                    ORDER BY v.id_madrina ASC, v.id_plataforma ASC, v.id_chofer ASC, v.posicion_acomodo ASC";
            $res = $this->select_all($sql, [$idEnvio]) ?: [];
            if (!empty($res) && file_exists("Models/Lgs_bandejaModel.php")) {
                require_once "Models/Lgs_bandejaModel.php";
                $bandejaModel = new Lgs_bandejaModel();
                $ptRows = $bandejaModel->getUnidadesPTGlobales([]);
                $ptByVin = [];
                foreach ($ptRows as $pt) {
                    $vk = strtoupper(trim((string)($pt['vin'] ?? '')));
                    if ($vk !== '') $ptByVin[$vk] = $pt;
                }
                $vinsExc = $this->getVinsAprobadosExcepcion($idEnvio);
                foreach ($res as &$row) {
                    $vk = strtoupper(trim((string)($row['vin'] ?? '')));
                    $pt = $ptByVin[$vk] ?? null;
                    $autExc = isset($vinsExc[$vk]);
                    if ($pt) {
                        $row['id_numeros_serie']    = intval($row['id_numeros_serie'] ?: ($pt['id_numeros_serie'] ?? 0));
                        $row['cliente_nombre']      = (string)($pt['cliente_nombre'] ?? '');
                        $row['folio_pedido']        = (string)($pt['folio_pedido'] ?? '');
                        $row['factura']             = (string)($pt['factura'] ?? '');
                        $row['estatus_pago_cod']    = intval($pt['estatus_pago_cod'] ?? -1);
                        $row['estatus_pago_texto']  = (string)($pt['estatus_pago_texto'] ?? 'Sin Facturar');
                        $row['activado_comercial']  = intval($pt['activado_comercial'] ?? 0);
                        $row['cumple_factura']      = intval($pt['cumple_factura'] ?? 0);
                        $row['cumple_pago']         = intval($pt['cumple_pago'] ?? 0);
                        $row['cumple_autorizacion'] = intval($pt['cumple_autorizacion'] ?? 0);
                        $row['autorizado_excepcion']= $autExc ? 1 : 0;
                        $row['se_envia']            = (!empty($pt['se_envia']) || $autExc) ? 1 : 0;
                        $row['estado_envio_motivo'] = $autExc && empty($pt['se_envia']) ? 'Autorizada por Solicitud Excepcional' : (string)($pt['estado_envio_motivo'] ?? '');
                        $row['preenvio_folios']     = (string)($pt['preenvio_folios'] ?? '');
                    } else {
                        $row['autorizado_excepcion'] = $autExc ? 1 : 0;
                        $row['se_envia'] = $autExc ? 1 : 0;
                        $row['estado_envio_motivo'] = $autExc ? 'Autorizada por Solicitud Excepcional' : 'Sin información PT/Factura';
                    }
                }
                unset($row);
            }
            return $res;
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
                        COALESCE(u.id_distribuidor, n.id_distribuidor) AS id_distribuidor,
                        d.nombre AS distribuidor_nombre,
                        COALESCE(u.nombre, n.destino_nombre_libre, 'Sin Nombre') AS destino_nombre,
                        n.destino_nombre_libre,
                        n.km_tramo_anterior AS km_tramo,
                        n.observaciones,
                        n.fecha_estimada
                    FROM lgs_envios_nodos n
                    LEFT JOIN lgs_cat_ubicaciones u ON n.id_ubicacion = u.id_ubicacion
                    LEFT JOIN lgs_cat_distribuidores d ON d.id_distribuidor = COALESCE(u.id_distribuidor, n.id_distribuidor)
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
        $locId = !empty($data['id_destino_cat']) ? (int)$data['id_destino_cat'] : (!empty($data['id_ubicacion']) ? (int)$data['id_ubicacion'] : null);
        $nodoData = [
            'id_envio'             => $data['id_envio'] ?? null,
            'orden'                => $data['orden'] ?? 1,
            'tipo_nodo'            => $data['tipo_nodo'] ?? 'entrega',
            'id_ubicacion'         => $locId > 0 ? $locId : null,
            'id_distribuidor'      => !empty($data['id_distribuidor']) ? (int)$data['id_distribuidor'] : null,
            'id_origen_referencia' => !empty($data['id_origen_referencia']) ? (int)$data['id_origen_referencia'] : null,
            'destino_nombre_libre' => !empty($data['destino_nombre_libre']) ? trim($data['destino_nombre_libre']) : null,
            'km_tramo_anterior'    => !empty($data['km_tramo']) ? floatval($data['km_tramo']) : (!empty($data['km_tramo_anterior']) ? floatval($data['km_tramo_anterior']) : 0.00),
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
                    id_destino = (SELECT id_ubicacion FROM lgs_envios_nodos WHERE id_envio = ? ORDER BY orden DESC LIMIT 1),
                    destino_nombre_libre = (SELECT destino_nombre_libre FROM lgs_envios_nodos WHERE id_envio = ? ORDER BY orden DESC LIMIT 1)
                WHERE id_envio = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idEnvio, $idEnvio, $idEnvio, $idEnvio]);
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
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `is_lowboy` TINYINT(1) NOT NULL DEFAULT 0 AFTER `id_destino`");
            } catch (Throwable $e) {}
            // Asegurar que existan los 3 tipos de traslado
            try {
                $db->exec("INSERT IGNORE INTO `lgs_cat_tipo_traslado` (id_tipo_traslado, nombre, activo) VALUES (1,'Madrina',1),(2,'Chofer (Rodando)',1),(3,'Plataforma',1)");
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
            try {
                $db->exec("ALTER TABLE `lgs_envios_vins` ADD COLUMN `tipo_servicio` VARCHAR(20) NOT NULL DEFAULT 'FORANEO' AFTER `costo_unidad`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `modalidad_calculo` ENUM('ESCALONADO', 'DIRECTO') NOT NULL DEFAULT 'ESCALONADO' AFTER `tipo_servicio`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios_nodos` ADD COLUMN `id_origen_referencia` BIGINT NULL DEFAULT NULL AFTER `id_ubicacion`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios_nodos` ADD COLUMN `id_distribuidor` BIGINT NULL DEFAULT NULL AFTER `id_ubicacion`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `is_pausado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `id_estado`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `motivo_pausa` TEXT NULL AFTER `is_pausado`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `fecha_pausa` DATETIME NULL AFTER `motivo_pausa`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `pausado_by` BIGINT NULL AFTER `fecha_pausa`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `fecha_reanudacion` DATETIME NULL AFTER `pausado_by`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `reanudado_by` BIGINT NULL AFTER `fecha_reanudacion`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `notas_reanudacion` TEXT NULL AFTER `reanudado_by`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `madrina_con_telemetria` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_lowboy`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `km_real_telemetria` DECIMAL(10,2) NULL DEFAULT NULL AFTER `madrina_con_telemetria`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `confirmacion_cita_cliente` TINYINT(1) NOT NULL DEFAULT 0 AFTER `km_real_telemetria`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `contacto_confirmacion_cliente` VARCHAR(150) NULL DEFAULT NULL AFTER `confirmacion_cita_cliente`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios_vins` ADD COLUMN `odometro_inicial` INT NULL DEFAULT NULL AFTER `recibe_nombre`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios_vins` ADD COLUMN `odometro_final` INT NULL DEFAULT NULL AFTER `odometro_inicial`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `prv_cat_proveedores` ADD COLUMN `modalidad_envio_default` ENUM('ESCALONADO', 'DIRECTO') NOT NULL DEFAULT 'DIRECTO' AFTER `origen`");
            } catch (Throwable $e) {}
            try {
                $db->exec("ALTER TABLE `lgs_envios` ADD COLUMN `es_preenvio` TINYINT(1) NOT NULL DEFAULT 0 AFTER `folio`");
            } catch (Throwable $e) {}
            // Índice UNIQUE preventivo en nombre de distribuidores (la gestión maestra de sedes vive en cli_* y Lgs_clientesSyncService)
            try {
                $idxUq = $db->query("SHOW INDEX FROM `lgs_cat_distribuidores` WHERE Key_name = 'uq_distribuidor_nombre'")->fetch();
                if (!$idxUq) {
                    $db->exec("UPDATE `lgs_cat_distribuidores` SET `nombre` = TRIM(`nombre`) WHERE `nombre` <> TRIM(`nombre`)");
                    $db->exec("ALTER TABLE `lgs_cat_distribuidores` ADD UNIQUE KEY `uq_distribuidor_nombre` (`nombre`)");
                }
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
     * Verifica una ruta de nodos contra la memoria progresiva de distancias (lgs_distancias).
     * En modalidad DIRECTO (Reparto), evalúa la distancia desde el Origen inicial a cada Parada.
     * En modalidad ESCALONADO, evalúa tramo a tramo consecutivo.
     * Retorna si todas las distancias son conocidas y la lista de tramos faltantes.
     */
    public function verificarDistanciasRuta(array $nodos, ?PDO $db = null, string $modalidad = 'ESCALONADO'): array
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

        $esDirecto = (strtoupper($modalidad) === 'DIRECTO');
        $nodoOrigenInicial = $nodos[0] ?? null;

        for ($i = 1; $i < count($nodos); $i++) {
            // En DIRECTO (reparto) la referencia de distancia es el punto de recolección / origen de partida
            $nodoPrev = ($esDirecto && $nodoOrigenInicial) ? $nodoOrigenInicial : $nodos[$i - 1];
            $nodoCurr = $nodos[$i];

            $idA = intval($nodoPrev['id_ubicacion'] ?? $nodoPrev['id_destino_cat'] ?? 0);
            $idB = intval($nodoCurr['id_ubicacion'] ?? $nodoCurr['id_destino_cat'] ?? 0);

            $nomA = $ubicacionesMap[$idA] ?? (!empty($nodoPrev['destino_nombre_libre']) ? $nodoPrev['destino_nombre_libre'] : "Ubicación #{$idA}");
            $nomB = $ubicacionesMap[$idB] ?? (!empty($nodoCurr['destino_nombre_libre']) ? $nodoCurr['destino_nombre_libre'] : "Ubicación #{$idB}");

            $esAbiertoCurr = empty($idB) && !empty($nodoCurr['destino_nombre_libre']);
            $esAbiertoPrev = empty($idA) && !empty($nodoPrev['destino_nombre_libre']);

            $km = null;
            if ($idA > 0 && $idB > 0 && $idA === $idB) {
                // Si origen y destino del tramo son la misma ubicación, la distancia es 0 y no es faltante
                $km = 0.00;
            } elseif ($esAbiertoCurr || $esAbiertoPrev) {
                // Destino abierto (fuera de catálogo) o tramo que parte de un destino abierto
                // Se utiliza exclusivamente el KM manual capturado en el nodo para el costeo de este envío.
                // NO se persiste en lgs_distancias ni se registra en memoria general.
                if (!empty($nodoCurr['km_tramo']) && floatval($nodoCurr['km_tramo']) > 0) {
                    $km = floatval($nodoCurr['km_tramo']);
                } else {
                    $km = null;
                }
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
                        'es_abierto' => ($esAbiertoCurr || $esAbiertoPrev)
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
                'km' => $km ?: 0.00,
                'es_abierto' => ($esAbiertoCurr || $esAbiertoPrev)
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
                        COALESCE(u.id_distribuidor, n.id_distribuidor) AS id_distribuidor,
                        COALESCE(u.id_cliente, d.id_cliente) AS id_cliente,
                        COALESCE(a.plantaid, u.id_planta_mrp) AS id_planta_mrp,
                        u.id_almacen_wms,
                        COALESCE(a.pt, 0) AS almacen_pt,
                        d.nombre AS distribuidor_nombre,
                        n.id_origen_referencia,
                        COALESCE(u.nombre, n.destino_nombre_libre, 'Sin Nombre') AS nombre,
                        u.direccion,
                        u.id_tipo_destino,
                        n.destino_nombre_libre,
                        n.km_tramo_anterior AS km_tramo,
                        n.observaciones,
                        n.fecha_estimada
                    FROM lgs_envios_nodos n
                    LEFT JOIN lgs_cat_ubicaciones u ON n.id_ubicacion = u.id_ubicacion
                    LEFT JOIN wms_almacenes a ON a.idalmacen = u.id_almacen_wms
                    LEFT JOIN lgs_cat_distribuidores d ON d.id_distribuidor = COALESCE(u.id_distribuidor, n.id_distribuidor)
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
                        n.id_origen_referencia,
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

    /**
     * Pausa un envío debido a incidencias operativas o en ruta
     */
    public function pausarEnvio(int $idEnvio, string $motivo, int $userId): bool
    {
        $sql = "UPDATE lgs_envios 
                SET is_pausado = 1, 
                    motivo_pausa = :motivo, 
                    fecha_pausa = NOW(), 
                    pausado_by = :user_id,
                    updated_at = NOW()
                WHERE id_envio = :id_envio";
        
        $stmt = $this->getConexion()->prepare($sql);
        return $stmt->execute([
            'motivo'   => $motivo,
            'user_id'  => $userId,
            'id_envio' => $idEnvio
        ]);
    }

    /**
     * Reanuda un envío previamente pausado
     */
    public function reanudarEnvio(int $idEnvio, string $notas, int $userId): bool
    {
        $sql = "UPDATE lgs_envios 
                SET is_pausado = 0, 
                    fecha_reanudacion = NOW(), 
                    reanudado_by = :user_id, 
                    notas_reanudacion = :notas,
                    updated_at = NOW()
                WHERE id_envio = :id_envio";
        
        $stmt = $this->getConexion()->prepare($sql);
        return $stmt->execute([
            'notas'    => $notas,
            'user_id'  => $userId,
            'id_envio' => $idEnvio
        ]);
    }

    /**
     * Garantiza la existencia de la tabla de solicitudes de autorización excepcional PE- -> EN-.
     */
    public function ensureSolicitudesPreEnvioTable(?PDO $db = null): void
    {
        $conn = $db ?: $this->getConexion();
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS `lgs_solicitudes_preenvio_envio` (
                `id_solicitud` int NOT NULL AUTO_INCREMENT,
                `folio_solicitud` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
                `id_envio` int NOT NULL,
                `folio_preenvio` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
                `folio_envio_generado` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `estatus` enum('PENDIENTE','APROBADA','RECHAZADA','CANCELADA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
                `motivo_solicitud` text COLLATE utf8mb4_unicode_ci NOT NULL,
                `detalle_observaciones_json` longtext COLLATE utf8mb4_unicode_ci,
                `vins_snapshot_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `total_vins` int NOT NULL DEFAULT '0',
                `vins_con_observacion` int NOT NULL DEFAULT '0',
                `solicitado_by` int NOT NULL,
                `solicitado_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `resuelto_by` int DEFAULT NULL,
                `resuelto_at` datetime DEFAULT NULL,
                `obs_aprobador` text COLLATE utf8mb4_unicode_ci,
                PRIMARY KEY (`id_solicitud`),
                KEY `idx_lgs_sol_pe_envio` (`id_envio`, `estatus`),
                KEY `idx_lgs_sol_pe_estatus` (`estatus`, `solicitado_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (Throwable $e) {
            // Silencioso si no tiene DDL o ya existe
        }
    }

    /**
     * Devuelve el mapa [VIN_UPPER => true] de VINs cubiertos por una solicitud APROBADA vigente para un envío.
     */
    public function getVinsAprobadosExcepcion(int $idEnvio): array
    {
        if ($idEnvio <= 0) {
            return [];
        }
        try {
            $this->ensureSolicitudesPreEnvioTable();
            $sol = $this->select("
                SELECT detalle_observaciones_json
                FROM lgs_solicitudes_preenvio_envio
                WHERE id_envio = ? AND estatus = 'APROBADA'
                ORDER BY id_solicitud DESC
                LIMIT 1
            ", [$idEnvio]);
            if (empty($sol['detalle_observaciones_json'])) {
                return [];
            }
            $det = json_decode((string)$sol['detalle_observaciones_json'], true);
            if (!is_array($det)) {
                return [];
            }
            $map = [];
            foreach ($det as $item) {
                $vk = strtoupper(trim((string)($item['vin'] ?? '')));
                if ($vk !== '') {
                    $map[$vk] = true;
                }
            }
            return $map;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Obtiene el resumen de la última solicitud de autorización excepcional asociada a un envío/pre-envío.
     */
    public function getSolicitudExcepcionEnvio(int $idEnvio): ?array
    {
        if ($idEnvio <= 0) {
            return null;
        }
        try {
            $this->ensureSolicitudesPreEnvioTable();
            $row = $this->select("
                SELECT s.*,
                       TRIM(CONCAT(COALESCE(us.nombres, ''), ' ', COALESCE(us.apellidos, ''))) AS solicitante_nombre,
                       TRIM(CONCAT(COALESCE(ur.nombres, ''), ' ', COALESCE(ur.apellidos, ''))) AS aprobador_nombre
                FROM lgs_solicitudes_preenvio_envio s
                LEFT JOIN usuarios us ON us.idusuario = s.solicitado_by
                LEFT JOIN usuarios ur ON ur.idusuario = s.resuelto_by
                WHERE s.id_envio = ?
                ORDER BY s.id_solicitud DESC
                LIMIT 1
            ", [$idEnvio]);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Valida estructura de ruta/acomodo, conflictos con Envíos Oficiales y cumplimiento
     * de Factura + Pago + Autorización de Movimiento para cada VIN de un envío/pre-envío.
     */
    public function validarRequisitosConversionEnvio(int $idEnvio): array
    {
        $db = $this->getConexion();
        $this->ensureDistanciasTable($db);
        $this->ensureSolicitudesPreEnvioTable($db);

        $cab = $this->getEnvioCabecera($idEnvio);
        if (empty($cab)) {
            throw new Exception("El registro de envío/pre-envío solicitado no existe.");
        }

        // 1. Validar que todas las paradas de entrega tengan sede o destino definido
        $nodosEntrega = $this->select_all("
            SELECT id_nodo, tipo_nodo, id_ubicacion, destino_nombre_libre
            FROM lgs_envios_nodos
            WHERE id_envio = ? AND orden > 0 AND COALESCE(tipo_nodo, 'entrega') = 'entrega'
        ", [$idEnvio]) ?: [];
        foreach ($nodosEntrega as $n) {
            if (empty($n['id_ubicacion']) && trim((string)($n['destino_nombre_libre'] ?? '')) === '') {
                throw new Exception("Todas las paradas de entrega deben tener una Sede o Destino asignado antes de pasar a Envío Oficial.");
            }
        }

        // 2. Obtener VINs asignados en el acomodo
        $vinsEnvio = $this->select_all("
            SELECT ev.id AS id_ev,
                   ev.id_unidad,
                   COALESCE(ev.id_numeros_serie, u.id_numeros_serie, ns.id_numeros_serie, 0) AS id_numeros_serie,
                   UPPER(TRIM(COALESCE(u.vin, ns.numero_serie, ''))) AS vin,
                   COALESCE(u.modelo, 'Unidad PT') AS modelo,
                   COALESCE(NULLIF(TRIM(ev.destino_nombre_libre), ''), NULLIF(TRIM(lu.destino_descripcion), ''), NULLIF(TRIM(u.destino), ''), '') AS destino_txt,
                   ev.id_madrina,
                   ev.id_plataforma,
                   ev.id_chofer,
                   COALESCE(lu.id_lgs_unidad, 0) AS id_lgs_unidad,
                   COALESCE(lu.activado_comercial, 0) AS activado_comercial,
                   COALESCE(lu.estatus_finanzas, '') AS estatus_finanzas_bandeja,
                   COALESCE(lu.folio_pedido, '') AS lu_folio_pedido,
                   COALESCE(lu.id_cliente, 0) AS lu_id_cliente
            FROM lgs_envios_vins ev
            INNER JOIN lgs_unidades_envios u ON u.id_unidad = ev.id_unidad
            LEFT JOIN lgs_unidades lu ON lu.id_unidad = ev.id_unidad
            LEFT JOIN wms_numeros_series ns ON ns.id_numeros_serie = COALESCE(ev.id_numeros_serie, u.id_numeros_serie)
                OR CONVERT(UPPER(TRIM(ns.numero_serie)) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(UPPER(TRIM(u.vin)) USING utf8mb4) COLLATE utf8mb4_unicode_ci
            WHERE ev.id_envio = ?
        ", [$idEnvio]) ?: [];

        if (empty($vinsEnvio)) {
            throw new Exception("No hay unidades (VINs) asignadas en Acomodo. Agregue un vehículo y asigne al menos un VIN antes de continuar.");
        }

        $sinVehiculo = 0;
        foreach ($vinsEnvio as $vChk) {
            if (empty($vChk['id_madrina']) && empty($vChk['id_plataforma']) && empty($vChk['id_chofer'])) {
                $sinVehiculo++;
            }
        }
        if ($sinVehiculo > 0) {
            throw new Exception("Hay {$sinVehiculo} unidad(es) sin Madrina, Plataforma o Chofer asignado. Complete el Acomodo de VINs antes de pasar a Envío.");
        }

        $idTipoTraslado = intval($cab['id_tipo_traslado'] ?? 1);
        if ($idTipoTraslado === 1 && count($vinsEnvio) === 1) {
            throw new Exception("No es posible pasar a Envío en Madrina con 1 sola unidad. Agregue al menos 2 unidades en Acomodo o cambie a Plataforma/Chofer.");
        }

        $draftConflicts = [];
        $vinsDetalle    = [];
        $vinsObservados = [];
        $vinsOrdenados  = [];

        $mapaPago = [
            -1 => 'Sin Facturar',
             0 => 'Pendiente de pago',
             1 => 'Crédito autorizado',
             2 => 'Parcialmente pagada',
             3 => 'Pagada',
        ];

        $bandejaHelper = null;
        if (file_exists("Models/Lgs_bandejaModel.php")) {
            require_once "Models/Lgs_bandejaModel.php";
            $bandejaHelper = new Lgs_bandejaModel();
        }

        foreach ($vinsEnvio as $v) {
            $idU   = intval($v['id_unidad']);
            $idNs  = intval($v['id_numeros_serie']);
            $vin   = strtoupper(trim((string)$v['vin']));
            if ($vin === '') continue;
            $vinsOrdenados[] = $vin;

            // Buscar otro Envío Oficial EN- activo con este VIN (excluye cancelado 0 y entregado 7)
            $confs = $this->select_all("
                SELECT DISTINCT e.id_envio, e.folio, e.id_estado
                FROM lgs_envios_vins ev
                INNER JOIN lgs_envios e ON e.id_envio = ev.id_envio
                INNER JOIN lgs_unidades_envios u ON u.id_unidad = ev.id_unidad
                WHERE e.deleted_at IS NULL
                  AND COALESCE(e.es_preenvio, CASE WHEN e.folio LIKE 'PE-%' THEN 1 ELSE 0 END) = 0
                  AND e.id_estado NOT IN (0, 7)
                  AND e.id_envio <> ?
                  AND (
                      ev.id_unidad = ?
                      " . ($idNs > 0 ? "OR ev.id_numeros_serie = ?" : "") . "
                      OR CONVERT(UPPER(TRIM(u.vin)) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci
                  )
            ", $idNs > 0 ? [$idEnvio, $idU, $idNs, $vin] : [$idEnvio, $idU, $vin]) ?: [];

            foreach ($confs as $cf) {
                $estCf = intval($cf['id_estado'] ?? 0);
                if ($estCf !== 1) {
                    throw new Exception("El VIN {$vin} ya está comprometido en el Envío Oficial {$cf['folio']} (estado activo). Libérelo antes de continuar.");
                }
                $draftConflicts[] = ['id_envio' => intval($cf['id_envio']), 'id_unidad' => $idU, 'folio' => $cf['folio'], 'vin' => $vin];
            }

            // Revisar facturación vigente y cobranza (`wms_vin_facturacion` + `vta_cobranza_unidades`)
            $fact = null;
            if ($idNs > 0) {
                $fact = $this->select("
                    SELECT f.idfacturacion, f.factura, f.distribuidor, f.id_cliente, cb.estatus_pago
                    FROM wms_vin_facturacion f
                    LEFT JOIN vta_cobranza_unidades cb ON cb.idfacturacion = f.idfacturacion AND cb.id_numeros_serie = f.id_numeros_serie
                    WHERE f.id_numeros_serie = ? AND f.estatus IN (1, 2)
                    LIMIT 1
                ", [$idNs]);
            }
            if (!$fact) {
                $fact = $this->select("
                    SELECT f.idfacturacion, f.factura, f.distribuidor, f.id_cliente, cb.estatus_pago
                    FROM wms_vin_facturacion f
                    LEFT JOIN vta_cobranza_unidades cb ON cb.idfacturacion = f.idfacturacion AND cb.id_numeros_serie = f.id_numeros_serie
                    WHERE CONVERT(UPPER(TRIM(f.numero_serie)) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci
                      AND f.estatus IN (1, 2)
                    LIMIT 1
                ", [$vin]);
            }

            $facturaFolio  = $fact ? trim((string)($fact['factura'] ?? '')) : '';
            $cumpleFactura = ($fact && intval($fact['idfacturacion'] ?? 0) > 0 && $facturaFolio !== '');
            $pCod          = $cumpleFactura
                ? (($fact['estatus_pago'] !== null && $fact['estatus_pago'] !== '') ? intval($fact['estatus_pago']) : 0)
                : -1;
            $pagoTexto     = $mapaPago[$pCod] ?? 'Pendiente de pago';
            $cumplePago    = ($cumpleFactura && in_array($pCod, [1, 3], true));
            $cumpleAut     = (intval($v['activado_comercial'] ?? 0) === 1);

            $estadoEval = $bandejaHelper
                ? $bandejaHelper->resolverEstadoEnvioUnidad($cumpleFactura, $cumplePago, $cumpleAut, $facturaFolio, $pagoTexto)
                : [
                    'cumple_factura'      => $cumpleFactura ? 1 : 0,
                    'cumple_pago'         => $cumplePago ? 1 : 0,
                    'cumple_autorizacion' => $cumpleAut ? 1 : 0,
                    'se_envia'            => ($cumpleFactura && $cumplePago && $cumpleAut) ? 1 : 0,
                    'estado_envio_motivo' => ($cumpleFactura && $cumplePago && $cumpleAut) ? 'Autorizada' : 'Falta validación',
                ];

            $faltantes = [];
            if (!$cumpleFactura) {
                $faltantes[] = 'Sin Factura';
            } elseif (!$cumplePago) {
                $faltantes[] = "Cobranza: {$pagoTexto}";
            }
            if (!$cumpleAut) {
                $faltantes[] = 'Sin Autorización de Movimiento';
            }

            $clienteNom = trim((string)($fact['distribuidor'] ?? ''));
            if ($clienteNom === '' && intval($v['lu_id_cliente'] ?? 0) > 0) {
                try {
                    $cRow = $this->select("SELECT COALESCE(NULLIF(razon_social,''), nombre_comercial) AS nom FROM cli_clientes WHERE idcliente = ? LIMIT 1", [intval($v['lu_id_cliente'])]);
                    $clienteNom = trim((string)($cRow['nom'] ?? ''));
                } catch (Throwable $e) {}
            }

            $itemDet = [
                'id_unidad'                => $idU,
                'id_numeros_serie'         => $idNs,
                'vin'                      => $vin,
                'modelo'                   => (string)($v['modelo'] ?? 'Unidad PT'),
                'cliente_nombre'           => $clienteNom,
                'destino_sede'             => (string)($v['destino_txt'] ?? ''),
                'folio_pedido'             => (string)($v['lu_folio_pedido'] ?? ''),
                'factura'                  => $facturaFolio,
                'estatus_pago_cod'         => $pCod,
                'estatus_pago_texto'       => $pagoTexto,
                'estatus_finanzas_bandeja' => (string)($v['estatus_finanzas_bandeja'] ?? ''),
                'activado_comercial'       => $cumpleAut ? 1 : 0,
                'cumple_factura'           => $estadoEval['cumple_factura'],
                'cumple_pago'              => $estadoEval['cumple_pago'],
                'cumple_autorizacion'      => $estadoEval['cumple_autorizacion'],
                'se_envia'                 => $estadoEval['se_envia'],
                'estado_envio_motivo'      => $estadoEval['estado_envio_motivo'],
                'faltantes'                => $faltantes,
                'estatus'                  => implode(' · ', $faltantes) ?: 'Autorizada',
            ];

            $vinsDetalle[] = $itemDet;
            if (empty($estadoEval['se_envia'])) {
                $vinsObservados[] = $itemDet;
            }
        }

        sort($vinsOrdenados);
        $vinsHash = hash('sha256', implode('|', $vinsOrdenados));

        $solPendiente = $this->select("
            SELECT s.*, TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) AS solicitante_nombre
            FROM lgs_solicitudes_preenvio_envio s
            LEFT JOIN usuarios u ON u.idusuario = s.solicitado_by
            WHERE s.id_envio = ? AND s.estatus = 'PENDIENTE'
            ORDER BY s.id_solicitud DESC
            LIMIT 1
        ", [$idEnvio]) ?: null;

        $solAprobada = $this->select("
            SELECT s.*, TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) AS aprobador_nombre
            FROM lgs_solicitudes_preenvio_envio s
            LEFT JOIN usuarios u ON u.idusuario = s.resuelto_by
            WHERE s.id_envio = ? AND s.estatus = 'APROBADA'
            ORDER BY s.id_solicitud DESC
            LIMIT 1
        ", [$idEnvio]) ?: null;

        $solRechazada = $this->select("
            SELECT s.*, TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) AS aprobador_nombre
            FROM lgs_solicitudes_preenvio_envio s
            LEFT JOIN usuarios u ON u.idusuario = s.resuelto_by
            WHERE s.id_envio = ? AND s.estatus = 'RECHAZADA'
            ORDER BY s.id_solicitud DESC
            LIMIT 1
        ", [$idEnvio]) ?: null;

        $tieneAprobacionVigente = false;
        if ($solAprobada) {
            $hashAprob = trim((string)($solAprobada['vins_snapshot_hash'] ?? ''));
            if ($hashAprob === '' || $hashAprob === $vinsHash) {
                $tieneAprobacionVigente = true;
            }
        }

        return [
            'cabecera'                 => $cab,
            'draft_conflicts'          => $draftConflicts,
            'vins_detalle'             => $vinsDetalle,
            'vins_observados'          => $vinsObservados,
            'vins_snapshot_hash'       => $vinsHash,
            'puede_convertir_directo'  => empty($vinsObservados),
            'tiene_aprobacion_vigente' => $tieneAprobacionVigente,
            'solicitud_pendiente'      => $solPendiente,
            'solicitud_aprobada'       => $solAprobada,
            'ultima_rechazada'         => $solRechazada,
        ];
    }

    /**
     * Genera una solicitud formal en Aprobaciones para forzar el paso a Envío Oficial
     * cuando existen unidades sin factura, pago o autorización de movimiento.
     */
    public function solicitarAutorizacionPreEnvio(int $idEnvio, string $motivo, int $userId): array
    {
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 5) {
            throw new Exception("Debe capturar un motivo o justificación de al menos 5 caracteres para enviar la solicitud.");
        }

        $val = $this->validarRequisitosConversionEnvio($idEnvio);
        $cab = $val['cabecera'];

        if ($val['puede_convertir_directo']) {
            throw new Exception("Todas las unidades del envío ya cuentan con Factura, Pago y Autorización de Movimiento. Puede convertirlo directamente sin solicitud.");
        }
        if ($val['tiene_aprobacion_vigente']) {
            throw new Exception("Este registro ya cuenta con una solicitud de excepción aprobada para estas unidades.");
        }

        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            // Si había una solicitud pendiente con exactamente los mismos VINs, evitar duplicado
            if (!empty($val['solicitud_pendiente'])) {
                $pend = $val['solicitud_pendiente'];
                if ((string)($pend['vins_snapshot_hash'] ?? '') === $val['vins_snapshot_hash']) {
                    throw new Exception("Ya existe la solicitud {$pend['folio_solicitud']} pendiente de revisión en el Panel de Aprobaciones.");
                }
                // Si el acomodo de VINs cambió, cancelar la pendiente anterior para reemplazarla
                $db->prepare("UPDATE lgs_solicitudes_preenvio_envio SET estatus = 'CANCELADA' WHERE id_solicitud = ?")
                   ->execute([intval($pend['id_solicitud'])]);
            }

            $stmtLast = $db->query("SELECT folio_solicitud FROM lgs_solicitudes_preenvio_envio ORDER BY id_solicitud DESC LIMIT 1 FOR UPDATE");
            $lastFolio = $stmtLast ? $stmtLast->fetchColumn() : false;
            $nextNum = 1;
            if ($lastFolio && preg_match('/(\d+)$/', (string)$lastFolio, $m)) {
                $nextNum = intval($m[1]) + 1;
            }
            $folioSol = 'SOL-PE-' . str_pad((string)$nextNum, 6, '0', STR_PAD_LEFT);

            $stmtIns = $db->prepare("
                INSERT INTO lgs_solicitudes_preenvio_envio
                    (folio_solicitud, id_envio, folio_preenvio, estatus, motivo_solicitud, detalle_observaciones_json, vins_snapshot_hash, total_vins, vins_con_observacion, solicitado_by, solicitado_at)
                VALUES (?, ?, ?, 'PENDIENTE', ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtIns->execute([
                $folioSol,
                $idEnvio,
                (string)($cab['folio'] ?? ('PE-' . $idEnvio)),
                $motivo,
                json_encode($val['vins_detalle'], JSON_UNESCAPED_UNICODE),
                $val['vins_snapshot_hash'],
                count($val['vins_detalle']),
                count($val['vins_observados']),
                $userId,
            ]);
            $idSol = intval($db->lastInsertId());

            $db->commit();

            // Notificar por correo a aprobadores / logística si el helper está disponible
            try {
                if (file_exists('Services/Lgs_notificacionesHelper.php')) {
                    require_once 'Services/Lgs_notificacionesHelper.php';
                    $dest = array_unique(array_merge(
                        Lgs_notificacionesHelper::getCorreosAprobadores($db),
                        Lgs_notificacionesHelper::getCorreosLogistica($db)
                    ));
                    if (!empty($dest)) {
                        $folioPre = htmlspecialchars((string)($cab['folio'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $motivoEsc = htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8');
                        $obsCount = count($val['vins_observados']);
                        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
                        $html = "
                        <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                            <div style='background-color: #D97706; color: #ffffff; padding: 20px; text-align: center;'>
                                <h2 style='margin: 0; font-size: 18px;'>Solicitud de Autorización Excepcional ({$folioSol})</h2>
                                <p style='margin: 5px 0 0 0; font-size: 13px;'>Pre-envío: <strong>{$folioPre}</strong></p>
                            </div>
                            <div style='padding: 22px; background-color: #ffffff;'>
                                <p style='font-size: 14px; color: #334155;'>Se solicitó autorización para pasar el Pre-envío <strong>{$folioPre}</strong> a Envío Oficial con <strong>{$obsCount}</strong> unidad(es) pendientes de factura, pago o autorización de movimiento.</p>
                                <p style='font-size: 13px; color: #0F172A;'><strong>Justificación:</strong> {$motivoEsc}</p>
                                <div style='text-align: center; margin-top: 20px;'>
                                    <a href='{$baseUrl}/Lgs_aprobaciones?tab=preenvios' style='background-color: #D97706; color: #fff; text-decoration: none; padding: 10px 24px; border-radius: 20px; font-weight: bold; font-size: 13px;'>Revisar en Aprobaciones</a>
                                </div>
                            </div>
                        </div>";
                        Lgs_notificacionesHelper::enviar($dest, "⚠️ Solicitud Excepcional {$folioSol} - {$folioPre}", $html);
                    }
                }
            } catch (Throwable $eMail) {
                error_log("Error notificación solicitud pre-envío: " . $eMail->getMessage());
            }

            return [
                'id_solicitud'         => $idSol,
                'folio_solicitud'      => $folioSol,
                'id_envio'             => $idEnvio,
                'folio_preenvio'       => (string)($cab['folio'] ?? ''),
                'vins_con_observacion' => count($val['vins_observados']),
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Convierte un Pre-envío (PE-xxxxxx) en un Envío Oficial (EN-xxxxxx)
     * validando ruta, acomodo, exclusividad de VINs, Factura + Pago + Aut. Movimiento.
     * Si faltan requisitos y no existe solicitud aprobada (ni $forzarAprobado desde Aprobaciones),
     * devuelve `requires_authorization = true` para iniciar el flujo "forzar bajo solicitud".
     */
    public function convertirPreEnvioAEnvioOficial(int $idEnvio, int $userId, bool $forzarAprobado = false): array
    {
        $val = $this->validarRequisitosConversionEnvio($idEnvio);
        $cab = $val['cabecera'];

        if (intval($cab['es_preenvio'] ?? 0) !== 1 && strpos((string)($cab['folio'] ?? ''), 'PE-') !== 0) {
            throw new Exception("Este registro ya es un Envío Oficial ({$cab['folio']}).");
        }

        if (!$val['puede_convertir_directo'] && !$val['tiene_aprobacion_vigente'] && !$forzarAprobado) {
            $resumenList = array_map(fn($w) => "{$w['vin']} ({$w['estatus']})", array_slice($val['vins_observados'], 0, 8));
            return [
                'requires_authorization' => true,
                'id_envio'               => $idEnvio,
                'folio_anterior'         => $cab['folio'] ?? '',
                'vins_observados'        => $val['vins_observados'],
                'vins_detalle'           => $val['vins_detalle'],
                'solicitud_pendiente'    => $val['solicitud_pendiente'],
                'ultima_rechazada'       => $val['ultima_rechazada'],
                'warning_message'        => count($val['vins_observados']) . " unidad(es) requieren Factura, Pago o Autorización de Movimiento: " . implode(', ', $resumenList),
            ];
        }

        $db = $this->getConexion();
        $draftConflicts = $val['draft_conflicts'];
        $enviosAfectados = [];
        foreach ($draftConflicts as $dc) {
            $enviosAfectados[intval($dc['id_envio'])] = true;
        }

        // Instanciar ANTES de beginTransaction() porque __construct ejecuta DDL (CREATE/ALTER) que auto-commitearía MySQL
        $bandejaSync = null;
        if (!empty($val['vins_detalle']) && file_exists("Models/Lgs_bandejaModel.php")) {
            require_once "Models/Lgs_bandejaModel.php";
            $bandejaSync = new Lgs_bandejaModel();
        }

        $db->beginTransaction();
        try {
            // 1. Retirar de borradores oficiales en conflicto (estado = 1)
            if (!empty($draftConflicts)) {
                $stmtDelDraft = $db->prepare("DELETE FROM lgs_envios_vins WHERE id_envio = ? AND id_unidad = ?");
                foreach ($draftConflicts as $dc) {
                    $stmtDelDraft->execute([$dc['id_envio'], $dc['id_unidad']]);
                }
            }

            // 2. Garantizar que todas las unidades existan en lgs_unidades y marcarlas id_estado_proceso = 2 (En Envío Oficial)
            $unitIds = array_values(array_unique(array_filter(array_map(fn($d) => intval($d['id_unidad'] ?? 0), $val['vins_detalle']))));
            if ($bandejaSync !== null && !empty($val['vins_detalle'])) {
                $syncPayload = [];
                foreach ($val['vins_detalle'] as $vd) {
                    $syncPayload[] = [
                        'vin'                     => $vd['vin'],
                        'id_numeros_serie'        => intval($vd['id_numeros_serie'] ?? 0),
                        'modelo'                  => (string)($vd['modelo'] ?? 'Unidad PT'),
                        'estatus_pago_cod'        => isset($vd['estatus_pago_cod']) ? intval($vd['estatus_pago_cod']) : null,
                        'estatus_finanzas_bandeja'=> (string)($vd['estatus_finanzas_bandeja'] ?? 'PENDIENTE'),
                        'destino_sugerido'        => (string)($vd['destino_sede'] ?? ''),
                    ];
                }
                $syncRes = $bandejaSync->sincronizarUnidadesPTInternal($db, $syncPayload, $userId);
                if (!empty($syncRes['map_ids'])) {
                    $unitIds = array_values(array_unique(array_merge($unitIds, array_map('intval', array_values($syncRes['map_ids'])))));
                }
            }
            if (!empty($unitIds)) {
                $ph = implode(',', array_fill(0, count($unitIds), '?'));
                $db->prepare("UPDATE lgs_unidades SET id_estado_proceso = 2, updated_at = NOW() WHERE id_unidad IN ({$ph})")
                   ->execute($unitIds);
            }

            // 3. Generar folio oficial EN-xxxxxx y convertir registro (pasa a estado 8 Finalizado para quedar listo en Planeaciones)
            $nuevoFolio = $this->generarFolioTransaccional($db, false);
            $stmt = $db->prepare(
                "UPDATE lgs_envios
                 SET folio = ?,
                     es_preenvio = 0,
                     id_estado = CASE WHEN id_estado = 1 THEN 8 ELSE id_estado END,
                     updated_by = ?,
                     updated_at = NOW()
                 WHERE id_envio = ?"
            );
            $stmt->execute([$nuevoFolio, $userId, $idEnvio]);

            if (!empty($val['solicitud_aprobada']['id_solicitud'])) {
                $db->prepare("UPDATE lgs_solicitudes_preenvio_envio SET folio_envio_generado = ? WHERE id_solicitud = ?")
                   ->execute([$nuevoFolio, intval($val['solicitud_aprobada']['id_solicitud'])]);
            }

            $db->commit();

            // 4. Recalcular costos del envío convertido y de borradores que perdieron VINs
            if (file_exists("Services/Lgs_enviosService.php")) {
                require_once "Services/Lgs_enviosService.php";
                $service = new Lgs_enviosService();
                try {
                    $service->recalcularCostoTotal($idEnvio);
                    $service->asegurarCostosVins($idEnvio);
                } catch (Throwable $eRec) {}
                foreach (array_keys($enviosAfectados) as $idAf) {
                    try {
                        $service->recalcularCostoTotal((int)$idAf);
                    } catch (Throwable $eAf) {}
                }
            }

            return [
                'id_envio'       => $idEnvio,
                'folio_anterior' => $cab['folio'] ?? '',
                'folio'          => $nuevoFolio,
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}

