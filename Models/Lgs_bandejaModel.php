<?php

class Lgs_bandejaModel extends Mysql {

    public function __construct() {
        parent::__construct();
        $this->asegurarTablas();
    }

    /**
     * Asegura la creación de tablas requeridas para la bandeja de logística y catálogos
     */
    private function asegurarTablas(): void {
        try {
            // 1. Catálogo motivos de envío
            $sqlMotivos = "CREATE TABLE IF NOT EXISTS `lgs_cat_motivo_envio` (
              `id_motivo` int(11) NOT NULL AUTO_INCREMENT,
              `cve_motivo` varchar(50) DEFAULT NULL,
              `descripcion` varchar(150) NOT NULL,
              `activo` tinyint(1) NOT NULL DEFAULT 1,
              PRIMARY KEY (`id_motivo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->insert($sqlMotivos, []);

            $checkMotivos = $this->select_all("SELECT id_motivo FROM lgs_cat_motivo_envio LIMIT 1");
            if (empty($checkMotivos)) {
                $seedMotivos = "INSERT INTO `lgs_cat_motivo_envio` (`cve_motivo`, `descripcion`, `activo`) VALUES
                ('VENTA', 'Venta Directa', 1),
                ('TRASLADO_PLANTA', 'Traslado entre Plantas', 1),
                ('DEMO', 'Demostración / Expo', 1),
                ('SERVICIO', 'Servicio / Mantenimiento', 1),
                ('EXPORT', 'Exportación', 1)";
                $this->insert($seedMotivos, []);
            }

            // 2. Catálogo tipo destino
            $sqlDestinos = "CREATE TABLE IF NOT EXISTS `lgs_cat_tipo_destino` (
              `id_tipo_destino` int(11) NOT NULL AUTO_INCREMENT,
              `cve_destino` varchar(50) DEFAULT NULL,
              `descripcion` varchar(150) NOT NULL,
              `activo` tinyint(1) NOT NULL DEFAULT 1,
              PRIMARY KEY (`id_tipo_destino`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->insert($sqlDestinos, []);

            $checkDestinos = $this->select_all("SELECT id_tipo_destino FROM lgs_cat_tipo_destino LIMIT 1");
            if (empty($checkDestinos)) {
                $seedDestinos = "INSERT INTO `lgs_cat_tipo_destino` (`cve_destino`, `descripcion`, `activo`) VALUES
                ('DISTRIBUIDOR', 'Distribuidor Autorizado', 1),
                ('AGENCIA', 'Agencia / Concesionario', 1),
                ('PATIO', 'Patio Central', 1),
                ('CLIENTE_FINAL', 'Cliente Final', 1),
                ('ADUANA', 'Aduana / Puerto', 1)";
                $this->insert($seedDestinos, []);
            }

            // 3. Catálogo unidades de envíos (si no existe)
            $sqlUnidadesEnvios = "CREATE TABLE IF NOT EXISTS `lgs_unidades_envios` (
              `id_unidad` int(11) NOT NULL AUTO_INCREMENT,
              `vin` varchar(50) NOT NULL UNIQUE,
              `num_serie` varchar(50) DEFAULT NULL,
              `modelo` varchar(100) DEFAULT NULL,
              `origen` varchar(150) DEFAULT NULL,
              `destino` varchar(150) DEFAULT NULL,
              `estatus` varchar(50) DEFAULT 'disponible',
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_unidad`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->insert($sqlUnidadesEnvios, []);

            // 4. Tabla principal lgs_unidades
            $sqlBandeja = "CREATE TABLE IF NOT EXISTS `lgs_unidades` (
              `id_lgs_unidad` int(11) NOT NULL AUTO_INCREMENT,
              `id_unidad` int(11) NOT NULL,
              `id_motivo` int(11) DEFAULT NULL,
              `id_destino` int(11) DEFAULT NULL,
              `destino_descripcion` varchar(255) DEFAULT NULL,
              `id_estado_proceso` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=Pendiente, 2=En Tránsito, 3=Entregado',
              `fecha_salida` datetime DEFAULT NULL,
              `fecha_llegada` datetime DEFAULT NULL,
              `created_by` int(11) DEFAULT 1,
              `updated_by` int(11) DEFAULT NULL,
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_lgs_unidad`),
              KEY `idx_lgs_id_unidad` (`id_unidad`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->insert($sqlBandeja, []);

            // 5. Tabla entrega interna
            $sqlEntrega = "CREATE TABLE IF NOT EXISTS `lgs_unidades_entrega_interna` (
              `id_entrega_interna` int(11) NOT NULL AUTO_INCREMENT,
              `id_unidad` int(11) NOT NULL,
              `id_estado` tinyint(4) NOT NULL DEFAULT 1,
              `observaciones` text DEFAULT NULL,
              `solicitado_by` int(11) DEFAULT NULL,
              `solicitado_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `confirmado_by` int(11) DEFAULT NULL,
              `confirmado_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id_entrega_interna`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->insert($sqlEntrega, []);

            // 6. Asegurar columnas de Pedidos, Finanzas y Activación Comercial en lgs_unidades (Puntos 5, 8 y 9)
            $cols = $this->select_all("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lgs_unidades'");
            $colNames = array_column($cols ?: [], 'COLUMN_NAME');
            if (!in_array('id_pedido', $colNames)) {
                $this->insert("ALTER TABLE lgs_unidades ADD COLUMN id_pedido bigint(20) DEFAULT NULL AFTER id_destino", []);
            }
            if (!in_array('folio_pedido', $colNames)) {
                $this->insert("ALTER TABLE lgs_unidades ADD COLUMN folio_pedido varchar(50) DEFAULT NULL AFTER id_pedido", []);
            }
            if (!in_array('destino_pedido', $colNames)) {
                $this->insert("ALTER TABLE lgs_unidades ADD COLUMN destino_pedido varchar(255) DEFAULT NULL AFTER folio_pedido", []);
            }
            if (!in_array('estatus_finanzas', $colNames)) {
                $this->insert("ALTER TABLE lgs_unidades ADD COLUMN estatus_finanzas varchar(50) DEFAULT 'PENDIENTE' AFTER destino_descripcion", []);
            }
            if (!in_array('activado_comercial', $colNames)) {
                $this->insert("ALTER TABLE lgs_unidades ADD COLUMN activado_comercial tinyint(1) NOT NULL DEFAULT 0 AFTER estatus_finanzas", []);
            }
            if (!in_array('fecha_activacion_comercial', $colNames)) {
                $this->insert("ALTER TABLE lgs_unidades ADD COLUMN fecha_activacion_comercial datetime DEFAULT NULL AFTER activado_comercial", []);
            }

        } catch (Throwable $e) {
            // Manejo silencioso para no interrumpir el flujo si las tablas ya están creadas
        }
    }

    /**
     * Obtiene todos los VINs elegibles para la bandeja de logística con información enriquecida
     * de Pedidos (Punto 5), Finanzas (Punto 8) y Activación Comercial (Punto 9).
     */
    public function getUnidadesBandeja(array $filtros = []): array {
        $where  = "WHERE 1=1";
        $params = [];

        // Filtro por estado logístico
        if (!empty($filtros['id_estado_proceso'])) {
            $where .= " AND lu.id_estado_proceso = ?";
            $params[] = intval($filtros['id_estado_proceso']);
        }

        // Filtro por activación comercial (Punto 9: 1 = Listo para Despacho, 0 = Solo Preplaneación)
        if (isset($filtros['activado_comercial']) && $filtros['activado_comercial'] !== '') {
            $where .= " AND lu.activado_comercial = ?";
            $params[] = intval($filtros['activado_comercial']);
        }

        // Filtro por estatus de finanzas (Punto 8: PAGADO, CREDITO_AUTORIZADO, PENDIENTE)
        if (!empty($filtros['estatus_finanzas'])) {
            $where .= " AND lu.estatus_finanzas = ?";
            $params[] = trim($filtros['estatus_finanzas']);
        }

        // Filtro por confirmación de destino (Punto 5)
        if (isset($filtros['destino_confirmado']) && $filtros['destino_confirmado'] !== '') {
            if (intval($filtros['destino_confirmado']) === 1) {
                $where .= " AND lu.destino_descripcion IS NOT NULL AND TRIM(lu.destino_descripcion) <> '' AND lu.destino_descripcion <> 'Por Confirmar'";
            } else {
                $where .= " AND (lu.destino_descripcion IS NULL OR TRIM(lu.destino_descripcion) = '' OR lu.destino_descripcion = 'Por Confirmar')";
            }
        }

        // Filtro por destino
        if (!empty($filtros['id_destino'])) {
            $where .= " AND lu.id_destino = ?";
            $params[] = intval($filtros['id_destino']);
        }

        // Filtro por motivo
        if (!empty($filtros['id_motivo'])) {
            $where .= " AND lu.id_motivo = ?";
            $params[] = intval($filtros['id_motivo']);
        }

        // Búsqueda por VIN, folio de pedido o descripción
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (u.vin LIKE ? OR u.num_serie LIKE ? OR ut.clave LIKE ? OR ut.num_unidad LIKE ? OR lu.folio_pedido LIKE ? OR lu.destino_pedido LIKE ? OR lu.destino_descripcion LIKE ?)";
            $term = '%' . $filtros['busqueda'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql = "SELECT
                    lu.id_lgs_unidad,
                    lu.id_unidad,
                    COALESCE(u.vin, ut.clave, CONCAT('VIN-', lu.id_unidad)) AS vin,
                    COALESCE(u.num_serie, ut.num_unidad, 'S/N') AS num_serie,
                    COALESCE(u.modelo, 'Unidad Terminada') AS modelo_unidad,
                    'Blanco' AS color_unidad,
                    COALESCE(m.descripcion, 'Sin Asignar') AS motivo_envio,
                    COALESCE(d.descripcion, 'Sin Asignar') AS tipo_destino,
                    -- Destino original de Pedidos (Punto 5)
                    COALESCE(lu.destino_pedido, u.destino, 'Por Definir') AS destino_pedido,
                    -- Destino logístico confirmado por Adriana
                    COALESCE(lu.destino_descripcion, lu.destino_pedido, u.destino, 'Por Confirmar') AS destino_descripcion,
                    CASE 
                        WHEN lu.destino_descripcion IS NOT NULL AND TRIM(lu.destino_descripcion) <> '' AND lu.destino_descripcion <> 'Por Confirmar' THEN 1 
                        ELSE 0 
                    END AS destino_confirmado,
                    -- Folio de Pedido asociado
                    COALESCE(lu.folio_pedido, '-') AS folio_pedido,
                    lu.id_pedido,
                    -- Estatus Finanzas (Punto 8)
                    COALESCE(lu.estatus_finanzas, 'PENDIENTE') AS estatus_finanzas,
                    -- Activación Comercial vs Preplaneación (Punto 9)
                    lu.activado_comercial,
                    lu.fecha_activacion_comercial,
                    CASE lu.activado_comercial
                        WHEN 1 THEN 'Listo para Despacho'
                        ELSE 'Solo Preplaneación'
                    END AS activacion_comercial_texto,
                    lu.id_estado_proceso,
                    CASE lu.id_estado_proceso
                        WHEN 1 THEN 'Pendiente'
                        WHEN 2 THEN 'En Tránsito'
                        WHEN 3 THEN 'Entregado'
                        ELSE 'Desconocido'
                    END AS estado_proceso_texto,
                    (SELECT e.folio 
                     FROM lgs_envios_vins ev 
                     INNER JOIN lgs_envios e ON ev.id_envio = e.id_envio 
                     WHERE ev.id_unidad = lu.id_unidad AND e.deleted_at IS NULL AND e.id_estado <> 0 
                     ORDER BY ev.id DESC LIMIT 1) AS envio_folio,
                    (SELECT e.id_envio 
                     FROM lgs_envios_vins ev 
                     INNER JOIN lgs_envios e ON ev.id_envio = e.id_envio 
                     WHERE ev.id_unidad = lu.id_unidad AND e.deleted_at IS NULL AND e.id_estado <> 0 
                     ORDER BY ev.id DESC LIMIT 1) AS envio_id,
                    lu.fecha_salida,
                    lu.fecha_llegada,
                    lu.created_at
                FROM lgs_unidades lu
                LEFT JOIN lgs_unidades_envios u ON u.id_unidad = lu.id_unidad
                LEFT JOIN mrp_unidades_terminadas ut ON ut.idunidad = lu.id_unidad
                LEFT JOIN lgs_cat_motivo_envio m ON m.id_motivo = lu.id_motivo
                LEFT JOIN lgs_cat_tipo_destino d ON d.id_tipo_destino = lu.id_destino
                {$where}
                ORDER BY lu.id_lgs_unidad DESC";

        $res = $this->select_all($sql, $params);
        return $res ?: [];
    }

    /**
     * Obtiene la lista unificada de distribuidores y sedes (vinculada a cli_* y wms_almacenes)
     */
    public function getListaDistribuidores(): array {
        if (class_exists('Lgs_clientesSyncService')) {
            try {
                Lgs_clientesSyncService::sincronizarDistribuidoresYSedes($this);
            } catch (Throwable $e) {}
        }
        try {
            $sql = "SELECT DISTINCT TRIM(nombre) COLLATE utf8mb4_unicode_ci AS nombre FROM lgs_cat_distribuidores WHERE activo = 1 AND nombre IS NOT NULL AND TRIM(nombre) <> ''
                    UNION
                    SELECT DISTINCT TRIM(nombre) COLLATE utf8mb4_unicode_ci AS nombre FROM lgs_cat_ubicaciones WHERE activo = 1 AND nombre IS NOT NULL AND TRIM(nombre) <> ''
                    ORDER BY nombre ASC";
            $res = $this->select_all($sql);
            return $res ?: [];
        } catch (Throwable $e) {
            $list1 = $this->select_all("SELECT DISTINCT TRIM(nombre) AS nombre FROM lgs_cat_distribuidores WHERE activo = 1 AND nombre IS NOT NULL AND TRIM(nombre) <> ''") ?: [];
            $list2 = $this->select_all("SELECT DISTINCT TRIM(nombre) AS nombre FROM lgs_cat_ubicaciones WHERE activo = 1 AND nombre IS NOT NULL AND TRIM(nombre) <> ''") ?: [];

            $nombres = [];
            foreach (array_merge($list1, $list2) as $row) {
                if (!empty($row['nombre'])) {
                    $nombres[trim($row['nombre'])] = true;
                }
            }
            ksort($nombres);
            $res = [];
            foreach (array_keys($nombres) as $n) {
                $res[] = ['nombre' => $n];
            }
            return $res;
        }
    }

    /**
     * Obtiene el detalle completo de una unidad en logística.
     */
    public function getUnidadDetalle(int $idLgsUnidad): ?array {
        $sql = "SELECT
                    lu.*,
                    COALESCE(u.vin, ut.clave, CONCAT('VIN-', lu.id_unidad)) AS vin,
                    COALESCE(u.num_serie, ut.num_unidad, 'S/N') AS num_serie,
                    COALESCE(u.modelo, 'Unidad Terminada') AS modelo_unidad,
                    'Blanco' AS color_unidad,
                    COALESCE(m.descripcion, 'Sin Asignar') AS motivo_envio,
                    m.cve_motivo,
                    COALESCE(d.descripcion, 'Sin Asignar') AS tipo_destino,
                    d.cve_destino
                FROM lgs_unidades lu
                LEFT JOIN lgs_unidades_envios u ON u.id_unidad = lu.id_unidad
                LEFT JOIN mrp_unidades_terminadas ut ON ut.idunidad = lu.id_unidad
                LEFT JOIN lgs_cat_motivo_envio m ON m.id_motivo = lu.id_motivo
                LEFT JOIN lgs_cat_tipo_destino d ON d.id_tipo_destino = lu.id_destino
                WHERE lu.id_lgs_unidad = ?";
        $res = $this->select($sql, [$idLgsUnidad]);
        return $res ?: null;
    }

    /**
     * Asigna destino y motivo de envío a una unidad (flujo global).
     */
    public function asignarDestinoMotivo(int $idLgsUnidad, array $data, int $userId): bool {
        $sql = "UPDATE lgs_unidades
                SET id_motivo           = ?,
                    id_destino          = ?,
                    destino_descripcion = ?,
                    id_estado_proceso   = 2,
                    updated_by          = ?,
                    updated_at          = NOW()
                WHERE id_lgs_unidad = ?";
        $params = [
            intval($data['id_motivo']),
            intval($data['id_destino']),
            htmlspecialchars(trim($data['destino_descripcion'] ?? ''), ENT_QUOTES, 'UTF-8'),
            $userId,
            $idLgsUnidad,
        ];
        return (bool) $this->update($sql, $params);
    }

    /**
     * Registra fechas de salida/llegada de la unidad.
     */
    public function registrarFechas(int $idLgsUnidad, ?string $fechaSalida, ?string $fechaLlegada, int $userId): bool {
        $sql = "UPDATE lgs_unidades
                SET fecha_salida  = ?,
                    fecha_llegada = ?,
                    updated_by    = ?,
                    updated_at    = NOW()
                WHERE id_lgs_unidad = ?";
        return (bool) $this->update($sql, [
            $fechaSalida  ?: null,
            $fechaLlegada ?: null,
            $userId,
            $idLgsUnidad,
        ]);
    }

    /**
     * Finaliza el traslado: marca como Entregado (3).
     */
    public function finalizarUnidad(int $idLgsUnidad, int $userId): bool {
        $sql = "UPDATE lgs_unidades
                SET id_estado_proceso = 3,
                    updated_by        = ?,
                    updated_at        = NOW()
                WHERE id_lgs_unidad = ?";
        return (bool) $this->update($sql, [$userId, $idLgsUnidad]);
    }

    /**
     * Ingresa un VIN a la bandeja de logística (crea registro en lgs_unidades).
     */
    public function insertarUnidad(int $idUnidad, int $userId): int {
        $sql = "INSERT INTO lgs_unidades (id_unidad, id_estado_proceso, created_by)
                VALUES (?, 1, ?)";
        return (int) $this->insert($sql, [$idUnidad, $userId]);
    }

    // ---------- Catálogos ----------

    public function getMotivos(): array {
        $res = $this->select_all("SELECT id_motivo, cve_motivo, descripcion FROM lgs_cat_motivo_envio WHERE activo = 1 ORDER BY descripcion ASC");
        return $res ?: [];
    }

    public function getDestinos(): array {
        $res = $this->select_all("SELECT id_tipo_destino AS id_destino, cve_destino, descripcion FROM lgs_cat_tipo_destino WHERE activo = 1 ORDER BY descripcion ASC");
        return $res ?: [];
    }

    // ---------- Entrega Interna ----------

    public function solicitarEntregaInterna(int $idUnidad, ?string $observaciones, int $userId): int {
        $sql = "INSERT INTO lgs_unidades_entrega_interna
                    (id_unidad, id_estado, observaciones, solicitado_by, solicitado_at)
                VALUES (?, 1, ?, ?, NOW())";
        return (int) $this->insert($sql, [
            $idUnidad,
            htmlspecialchars(trim($observaciones ?? ''), ENT_QUOTES, 'UTF-8'),
            $userId,
        ]);
    }

    public function confirmarEntregaInterna(int $idEntrega, int $userId): bool {
        $sql = "UPDATE lgs_unidades_entrega_interna
                SET id_estado = 2, confirmado_by = ?, confirmado_at = NOW()
                WHERE id_entrega_interna = ? AND id_estado = 1";
        return (bool) $this->update($sql, [$userId, $idEntrega]);
    }

    public function cancelarEntregaInterna(int $idEntrega, int $userId): bool {
        $sql = "UPDATE lgs_unidades_entrega_interna
                SET id_estado = 3, confirmado_by = ?
                WHERE id_entrega_interna = ? AND id_estado = 1";
        return (bool) $this->update($sql, [$userId, $idEntrega]);
    }

    /**
     * Confirma el destino que viene de Pedidos (Punto 5 de la minuta).
     * Adriana analiza y confirma con un clic el destino originado en el pedido comercial.
     */
    public function confirmarDestinoDesdePedido(int $idLgsUnidad, int $userId): bool {
        $sql = "UPDATE lgs_unidades
                SET destino_descripcion = COALESCE(NULLIF(destino_pedido, ''), destino_descripcion),
                    updated_by          = ?,
                    updated_at          = NOW()
                WHERE id_lgs_unidad = ?";
        return (bool) $this->update($sql, [$userId, $idLgsUnidad]);
    }

    /**
     * Ajusta el destino confirmado si Adriana tras validar con pedidos requiere especificar
     * un nodo, patio o ubicación logística exacta (Punto 5 de la minuta).
     */
    public function ajustarDestino(int $idLgsUnidad, string $destinoConfirmado, int $userId): bool {
        $sql = "UPDATE lgs_unidades
                SET destino_descripcion = ?,
                    updated_by          = ?,
                    updated_at          = NOW()
                WHERE id_lgs_unidad = ?";
        return (bool) $this->update($sql, [
            htmlspecialchars(trim($destinoConfirmado), ENT_QUOTES, 'UTF-8'),
            $userId,
            $idLgsUnidad
        ]);
    }

    /**
     * Activa o desactiva la unidad para despacho comercial (Punto 9 de la minuta).
     * 1 = Activado por Comercial (Listo para despacho en firme)
     * 0 = Solo Preplaneación (Permitido para rutas preliminares pero no despacho)
     */
    public function toggleActivacionComercial(int $idLgsUnidad, int $activado, int $userId): bool {
        $sql = "UPDATE lgs_unidades
                SET activado_comercial         = ?,
                    fecha_activacion_comercial = IF(? = 1, NOW(), fecha_activacion_comercial),
                    updated_by                 = ?,
                    updated_at                 = NOW()
                WHERE id_lgs_unidad = ?";
        return (bool) $this->update($sql, [$activado, $activado, $userId, $idLgsUnidad]);
    }

    /**
     * Actualiza el estatus de finanzas de la unidad (Punto 8 de la minuta).
     * Valores: PAGADO, CREDITO_AUTORIZADO, PARCIAL_AUTORIZADO, PENDIENTE
     */
    public function actualizarEstatusFinanzas(int $idLgsUnidad, string $estatusFinanzas, int $userId): bool {
        $sql = "UPDATE lgs_unidades
                SET estatus_finanzas = ?,
                    updated_by       = ?,
                    updated_at       = NOW()
                WHERE id_lgs_unidad = ?";
        return (bool) $this->update($sql, [trim($estatusFinanzas), $userId, $idLgsUnidad]);
    }

    // =========================================================================
    // PESTAÑA 2: CATÁLOGO GLOBAL UNIDADES PT (FACTURACIÓN, PAGO Y PRE-ENVÍOS)
    // =========================================================================

    private function tableExists(string $tableName): bool {
        try {
            $row = $this->select(
                "SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$tableName]
            );
            return intval($row['total'] ?? 0) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function columnExists(string $tableName, string $columnName): bool {
        try {
            $row = $this->select(
                "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$tableName, $columnName]
            );
            return intval($row['total'] ?? 0) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Clasifica el segmento logístico de una unidad PT según sublínea y modelo.
     */
    public function clasificarSegmentoUnidad(string $modelo, string $sublinea = '', string $vin = ''): array {
        $nombres = [
            1 => 'Ligero',
            2 => 'Mediano',
            3 => 'Pesado',
            4 => 'Bus',
            5 => 'Lowboy',
        ];

        $modeloLower = mb_strtolower(trim($modelo), 'UTF-8');
        $subUpper    = strtoupper(trim($sublinea));
        $idSegmento  = 1;

        if (strpos($modeloLower, 'lowboy') !== false || strpos($modeloLower, 'sobredimensionado') !== false) {
            $idSegmento = 5;
        } elseif ($subUpper === 'AUV' || $subUpper === 'BUS') {
            $idSegmento = 4;
        } elseif ($subUpper === 'HDT') {
            $idSegmento = 3;
        } elseif ($subUpper === 'MDT') {
            $idSegmento = 2;
        } elseif (in_array($subUpper, ['PICKUP', 'VAN', 'MT', 'LDT'], true)) {
            $idSegmento = 1;
        } elseif (
            strpos($modeloLower, 'auv') !== false || strpos($modeloLower, 'araña') !== false || strpos($modeloLower, 'arana') !== false
            || strpos($modeloLower, 'bus') !== false || strpos($modeloLower, 'autob') !== false
            || strpos($modeloLower, 'beccar') !== false || strpos($modeloLower, 'orion') !== false || strpos($modeloLower, 'urbi') !== false
        ) {
            $idSegmento = 4;
        } elseif (
            strpos($modeloLower, 'est') !== false || strpos($modeloLower, 'galaxy') !== false || strpos($modeloLower, 'galaxus') !== false
            || strpos($modeloLower, 'gtl') !== false || strpos($modeloLower, '2491') !== false || strpos($modeloLower, '3256') !== false
            || strpos($modeloLower, 's35') !== false || strpos($modeloLower, 's38') !== false || strpos($modeloLower, 's40') !== false
            || strpos($modeloLower, 'isg') !== false || strpos($modeloLower, 'tracto') !== false || strpos($modeloLower, 'volteo') !== false
            || strpos($modeloLower, 'heavy') !== false || strpos($modeloLower, 'pesado') !== false
        ) {
            $idSegmento = 3;
        } elseif (
            strpos($modeloLower, 's8') !== false || strpos($modeloLower, 's12') !== false || strpos($modeloLower, 's13') !== false
            || strpos($modeloLower, 's20') !== false || strpos($modeloLower, 'mediano') !== false || strpos($modeloLower, 'medium') !== false
        ) {
            $idSegmento = 2;
        }

        return [
            'id_segmento'     => $idSegmento,
            'segmento_nombre' => $nombres[$idSegmento] ?? 'Ligero',
        ];
    }

    /**
     * Evalúa de manera uniforme si una unidad cumple Factura, Pago y Autorización de Movimiento
     * para poder pasar directamente a un Envío Oficial (Sí/No) y explica el motivo si no cumple.
     */
    public function resolverEstadoEnvioUnidad(bool $cumpleFactura, bool $cumplePago, bool $cumpleAut, string $factura = '', string $pagoTexto = 'Pendiente'): array {
        $seEnvia = $cumpleFactura && $cumplePago && $cumpleAut;
        if ($seEnvia) {
            $motivo = 'Autorizada (Factura + Pago + Aut. Mov.)';
        } elseif ($cumpleFactura && $cumplePago && !$cumpleAut) {
            $motivo = 'Cumple Factura y Pago · Falta Aut. Movimiento';
        } elseif ($cumpleAut && (!$cumpleFactura || !$cumplePago)) {
            $motivo = !$cumpleFactura
                ? 'Con Aut. Movimiento · Falta Factura'
                : "Con Aut. Movimiento · Pago {$pagoTexto}";
        } elseif ($cumpleFactura && !$cumplePago) {
            $facLbl = $factura !== '' ? " ({$factura})" : '';
            $motivo = "Facturada{$facLbl} · Pago {$pagoTexto} · Sin Aut. Mov.";
        } else {
            $motivo = 'Sin Factura ni Aut. Movimiento';
        }
        return [
            'cumple_factura'      => $cumpleFactura ? 1 : 0,
            'cumple_pago'         => $cumplePago ? 1 : 0,
            'cumple_autorizacion' => $cumpleAut ? 1 : 0,
            'se_envia'            => $seEnvia ? 1 : 0,
            'estado_envio_motivo' => $motivo,
        ];
    }

    /**
     * Obtiene TODAS las unidades de Producto Terminado (PT) globales con sus estados de
     * facturación (wms_vin_facturacion), cobranza/pago (vta_cobranza_unidades) y estatus logístico.
     */
    public function getUnidadesPTGlobales(array $filtros = []): array {
        $byVin = [];

        $mapaPagoTexto = [
            -1 => 'Sin Facturar',
             0 => 'Pendiente',
             1 => 'Crédito',
             2 => 'Parcialmente pagada',
             3 => 'Pagada',
        ];

        $mapFinanzasToPagoCod = [
            'PAGADO'             => 3,
            'CREDITO_AUTORIZADO' => 1,
            'PARCIAL_AUTORIZADO' => 2,
            'PENDIENTE'          => 0,
        ];

        // 1. Mapa auxiliar desde bandeja operativa (lgs_unidades + lgs_unidades_envios)
        $bandejaByVin = [];
        $bandejaByIdUnidad = [];
        try {
            $sqlAux = "SELECT
                            u.id_unidad,
                            u.id_numeros_serie,
                            u.vin,
                            u.num_serie,
                            u.modelo,
                            u.origen,
                            u.destino,
                            u.estatus,
                            lu.id_lgs_unidad,
                            lu.id_motivo,
                            lu.id_destino,
                            lu.id_cliente AS lu_id_cliente,
                            lu.id_ubicacion_destino AS lu_id_ubicacion_destino,
                            lu.destino_descripcion,
                            lu.destino_pedido,
                            lu.id_pedido AS lu_id_pedido,
                            lu.folio_pedido,
                            lu.estatus_finanzas,
                            lu.activado_comercial,
                            lu.id_estado_proceso
                       FROM lgs_unidades_envios u
                       LEFT JOIN lgs_unidades lu ON lu.id_unidad = u.id_unidad
                       ORDER BY u.id_unidad DESC";
            $rowsAux = $this->select_all($sqlAux) ?: [];
            foreach ($rowsAux as $ax) {
                $vKey = strtoupper(trim((string)($ax['vin'] ?? '')));
                if ($vKey !== '') {
                    $bandejaByVin[$vKey] = $ax;
                }
                if (!empty($ax['id_unidad'])) {
                    $bandejaByIdUnidad[intval($ax['id_unidad'])] = $ax;
                }
            }
        } catch (Throwable $e) {
            // Silencioso si no existe lgs_unidades_envios o columnas nuevas
        }

        // 1b. Catálogos maestros de Clientes, Distribuidores, Ubicaciones/Sedes y Pedidos-VIN
        $clientesById = [];
        $distByClienteId = [];
        $clienteByNomNorm = [];
        $ubicacionById = [];
        $pedidoBySerieId = [];

        try {
            $normFn = static function (string $txt): string {
                return class_exists('Lgs_clientesSyncService')
                    ? Lgs_clientesSyncService::normalizarTexto($txt)
                    : strtoupper(trim($txt));
            };

            foreach ($this->select_all("SELECT idcliente, codigo_cliente, razon_social, COALESCE(NULLIF(nombre_comercial,''), razon_social) AS nombre_com FROM cli_clientes WHERE estado = 2") ?: [] as $cliR) {
                $cid = intval($cliR['idcliente']);
                $nomCli = trim((string)($cliR['razon_social'] ?: $cliR['nombre_com'] ?: ''));
                $clientesById[$cid] = [
                    'id_cliente' => $cid,
                    'nombre'     => $nomCli,
                ];
                foreach ([$cliR['razon_social'] ?? '', $cliR['nombre_com'] ?? '', $cliR['codigo_cliente'] ?? ''] as $alias) {
                    $k = str_replace(' ', '', $normFn((string)$alias));
                    if ($k !== '' && !isset($clienteByNomNorm[$k])) {
                        $clienteByNomNorm[$k] = ['id_cliente' => $cid, 'nombre' => $nomCli];
                    }
                }
            }

            foreach ($this->select_all("SELECT id_distribuidor, id_cliente, TRIM(nombre) AS nombre FROM lgs_cat_distribuidores WHERE activo = 1") ?: [] as $dR) {
                $cid = intval($dR['id_cliente'] ?? 0);
                $did = intval($dR['id_distribuidor']);
                if ($cid > 0 && !isset($distByClienteId[$cid])) {
                    $distByClienteId[$cid] = $did;
                }
                $k = str_replace(' ', '', $normFn((string)$dR['nombre']));
                if ($k !== '' && $cid > 0 && !isset($clienteByNomNorm[$k])) {
                    $clienteByNomNorm[$k] = [
                        'id_cliente'      => $cid,
                        'nombre'          => $clientesById[$cid]['nombre'] ?? $dR['nombre'],
                        'id_distribuidor' => $did,
                    ];
                }
            }

            foreach ($this->select_all("
                SELECT u.id_ubicacion, u.nombre, u.direccion, u.id_distribuidor,
                       COALESCE(u.id_cliente, d.id_cliente, 0) AS id_cliente,
                       u.id_sucursal_cli, u.id_direccion_cli
                FROM lgs_cat_ubicaciones u
                LEFT JOIN lgs_cat_distribuidores d ON d.id_distribuidor = u.id_distribuidor
                WHERE u.activo = 1
            ") ?: [] as $uR) {
                $ubicacionById[intval($uR['id_ubicacion'])] = $uR;
            }
        } catch (Throwable $e) {
            // Silencioso en caso de esquema parcial
        }

        if ($this->tableExists('ped_pedidos_vins')) {
            try {
                $rowsPedVin = $this->select_all("
                    SELECT pv.id_numeros_serie,
                           p.idpedido,
                           p.folio_pedido,
                           p.estatus AS estatus_pedido,
                           p.idcliente,
                           COALESCE(NULLIF(TRIM(c.razon_social), ''), NULLIF(TRIM(c.nombre_comercial), ''), '') AS cliente_nombre,
                           d.idpedido_detalle,
                           d.tipo_entrega,
                           d.idsucursal_entrega,
                           COALESCE(s.nombre_sucursal, '') AS sucursal_entrega_nombre,
                           COALESCE(d.direccion_entrega, '') AS direccion_entrega,
                           u_ped.id_ubicacion AS sede_ped_ubicacion_id,
                           COALESCE(u_ped.nombre, '') AS sede_ped_nombre
                    FROM ped_pedidos_vins pv
                    INNER JOIN ped_pedidos p ON p.idpedido = pv.idpedido AND p.estado = 2
                    INNER JOIN ped_pedidos_detalle d ON d.idpedido_detalle = pv.idpedido_detalle AND d.estado = 2
                    LEFT JOIN cli_clientes c ON c.idcliente = p.idcliente
                    LEFT JOIN cli_clientes_sucursales s ON s.idsucursal = d.idsucursal_entrega
                    LEFT JOIN lgs_cat_ubicaciones u_ped ON u_ped.id_sucursal_cli = d.idsucursal_entrega AND u_ped.activo = 1
                    WHERE pv.estatus = 'ACTIVA'
                ") ?: [];
                foreach ($rowsPedVin as $pvR) {
                    $nsId = intval($pvR['id_numeros_serie'] ?? 0);
                    if ($nsId > 0) {
                        $pedidoBySerieId[$nsId] = $pvR;
                    }
                }
            } catch (Throwable $e) {
                // Silencioso
            }
        }

        // 2. Mapa separado de Envíos Oficiales (EN-, bloqueantes) vs Pre-envíos Ficticios (PE-, NO bloqueantes)
        $envioByVin = [];
        $envioByIdUnidad = [];
        $preenviosByVin = [];
        $preenviosByIdUnidad = [];
        try {
            $sqlEnv = "SELECT
                            ev.id_unidad,
                            ev.id_numeros_serie,
                            e.id_envio,
                            e.folio,
                            e.id_estado,
                            COALESCE(e.es_preenvio, CASE WHEN e.folio LIKE 'PE-%' THEN 1 ELSE 0 END) AS es_preenvio,
                            UPPER(TRIM(COALESCE(u.vin, ns.numero_serie, ''))) AS vin_key
                       FROM lgs_envios_vins ev
                       INNER JOIN lgs_envios e ON ev.id_envio = e.id_envio
                       LEFT JOIN lgs_unidades_envios u ON u.id_unidad = ev.id_unidad
                       LEFT JOIN wms_numeros_series ns ON ns.id_numeros_serie = ev.id_numeros_serie
                       WHERE e.deleted_at IS NULL AND e.id_estado NOT IN (0, 7)
                       ORDER BY ev.id DESC";
            $rowsEnv = $this->select_all($sqlEnv) ?: [];
            foreach ($rowsEnv as $ev) {
                $idU   = intval($ev['id_unidad'] ?? 0);
                $vKey  = strtoupper(trim((string)($ev['vin_key'] ?? '')));
                if ($vKey === '' && $idU > 0 && isset($bandejaByIdUnidad[$idU])) {
                    $vKey = strtoupper(trim((string)($bandejaByIdUnidad[$idU]['vin'] ?? '')));
                }
                $esPre = intval($ev['es_preenvio'] ?? 0) === 1;
                $itemEnv = [
                    'id_envio'  => intval($ev['id_envio']),
                    'folio'     => (string)$ev['folio'],
                    'id_estado' => intval($ev['id_estado']),
                ];

                if ($esPre) {
                    if ($idU > 0) {
                        $preenviosByIdUnidad[$idU][$itemEnv['id_envio']] = $itemEnv;
                    }
                    if ($vKey !== '') {
                        $preenviosByVin[$vKey][$itemEnv['id_envio']] = $itemEnv;
                    }
                } else {
                    if ($idU > 0 && !isset($envioByIdUnidad[$idU])) {
                        $envioByIdUnidad[$idU] = $itemEnv;
                    }
                    if ($vKey !== '' && !isset($envioByVin[$vKey])) {
                        $envioByVin[$vKey] = $itemEnv;
                    }
                }
            }
        } catch (Throwable $e) {
            // Silencioso si no existe lgs_envios_vins
        }

        // 3. Consultar unidades PT globales desde WMS (wms_numeros_series + wms_inventario)
        if ($this->tableExists('wms_numeros_series') && $this->tableExists('wms_inventario')) {
            $hasFact     = $this->tableExists('wms_vin_facturacion');
            $hasCobranza = $hasFact && $this->tableExists('vta_cobranza_unidades');
            $hasAsoc     = $this->tableExists('wms_vin_asociaciones');
            $hasAnio     = $this->tableExists('cat_anio_vin');
            $hasAlmacen  = $this->tableExists('wms_almacenes');
            $hasPlanta   = $this->tableExists('mrp_planta');
            $hasIngCfg   = $this->tableExists('ing_modelo_configuracion');
            $hasInvLinea = $this->tableExists('wms_inventario_linea') && $this->tableExists('wms_sublinea_producto');
            $hasMovSer   = $this->tableExists('wms_movimientos_inventario_series');

            $selectFact = $hasFact
                ? "COALESCE(f.idfacturacion, 0) AS idfacturacion,
                   CASE WHEN f.idfacturacion IS NOT NULL OR ns.estado = '3' THEN 1 ELSE 0 END AS facturado,
                   COALESCE(f.factura, '') AS factura,
                   COALESCE(DATE_FORMAT(f.fecha_factura, '%Y-%m-%d'), '') AS fecha_factura,
                   COALESCE(f.distribuidor, '') AS distribuidor,
                   COALESCE(f.id_cliente, 0) AS fact_id_cliente,
                   COALESCE(f.estatus, 0) AS estatus_fact_wms,"
                : "0 AS idfacturacion,
                   CASE WHEN ns.estado = '3' THEN 1 ELSE 0 END AS facturado,
                   '' AS factura,
                   '' AS fecha_factura,
                   '' AS distribuidor,
                   0 AS fact_id_cliente,
                   0 AS estatus_fact_wms,";

            $selectCob = $hasCobranza
                ? "cb.estatus_pago AS estatus_pago_raw,
                   COALESCE(DATE_FORMAT(cb.fecha_pago, '%Y-%m-%d'), '') AS fecha_pago,"
                : "NULL AS estatus_pago_raw,
                   '' AS fecha_pago,";

            $joinFact = $hasFact
                ? "LEFT JOIN wms_vin_facturacion f ON f.id_numeros_serie = ns.id_numeros_serie AND f.estatus IN (1, 2)"
                : "";
            $joinCob = $hasCobranza
                ? "LEFT JOIN vta_cobranza_unidades cb ON cb.idfacturacion = f.idfacturacion AND cb.id_numeros_serie = ns.id_numeros_serie"
                : "";

            $joinAlmacen = $hasAlmacen ? "LEFT JOIN wms_almacenes a ON a.idalmacen = ns.almacenid" : "";
            $joinPlanta  = ($hasAlmacen && $hasPlanta) ? "LEFT JOIN mrp_planta pl ON pl.idplanta = a.plantaid" : "";
            $joinAnio    = $hasAnio ? "LEFT JOIN cat_anio_vin an ON an.codigo = SUBSTRING(ns.numero_serie, 10, 1) COLLATE utf8mb4_general_ci" : "";
            $joinAsoc    = $hasAsoc ? "LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie" : "";

            $joinSub = "";
            $modeloExpr = "COALESCE(NULLIF(TRIM(i.descripcion), ''), 'Unidad PT')";
            $subExpr    = "''";
            if ($hasIngCfg && $hasInvLinea) {
                $joinSub = "LEFT JOIN (
                                SELECT id_inventario, MIN(id_sublineaproducto) AS idsub, MIN(nombre_comercial) AS nombre_comercial
                                FROM ing_modelo_configuracion
                                WHERE deleted_at IS NULL AND id_inventario IS NOT NULL
                                GROUP BY id_inventario
                            ) cfg ON cfg.id_inventario = i.idinventario
                            LEFT JOIN (
                                SELECT inventarioid, MIN(sublineaproductoid) AS idsub
                                FROM wms_inventario_linea WHERE estado = 2
                                GROUP BY inventarioid
                            ) il ON il.inventarioid = i.idinventario
                            LEFT JOIN wms_sublinea_producto sl ON sl.idsublineaproducto = COALESCE(cfg.idsub, il.idsub)";
                $modeloExpr = "COALESCE(NULLIF(TRIM(cfg.nombre_comercial), ''), NULLIF(TRIM(i.descripcion), ''), 'Unidad PT')";
                $subExpr    = "COALESCE(sl.cve_sublinea_producto, '')";
            } elseif ($hasInvLinea) {
                $joinSub = "LEFT JOIN (
                                SELECT inventarioid, MIN(sublineaproductoid) AS idsub
                                FROM wms_inventario_linea WHERE estado = 2
                                GROUP BY inventarioid
                            ) il ON il.inventarioid = i.idinventario
                            LEFT JOIN wms_sublinea_producto sl ON sl.idsublineaproducto = il.idsub";
                $subExpr = "COALESCE(sl.cve_sublinea_producto, '')";
            }

            $transitoCond = $hasAlmacen
                ? "(UPPER(TRIM(COALESCE(a.cve_almacen, ''))) = 'ALM-TRANSITO' OR UPPER(TRIM(COALESCE(a.descripcion, ''))) IN ('ALMACEN DE TRANSITO', 'ALMACÉN DE TRANSITO', 'ALMACÉN DE TRÁNSITO'))"
                : "0";
            $plantaClaveExpr = $hasAlmacen
                ? "CASE WHEN {$transitoCond} THEN 'TRANSITO' ELSE " . ($hasPlanta ? "COALESCE(pl.cve_planta, CASE WHEN a.cve_almacen REGEXP '^PL[0-9]{2}-' THEN LEFT(a.cve_almacen, 4) ELSE 'SIN' END)" : "CASE WHEN a.cve_almacen REGEXP '^PL[0-9]{2}-' THEN LEFT(a.cve_almacen, 4) ELSE 'SIN' END") . " END"
                : "'SIN'";
            $plantaNombreExpr = $hasAlmacen
                ? "CASE WHEN {$transitoCond} THEN 'En tránsito' ELSE " . ($hasPlanta ? "COALESCE(pl.nombre_planta, CASE WHEN a.cve_almacen REGEXP '^PL[0-9]{2}-' THEN CONCAT('Planta ', CAST(SUBSTRING(a.cve_almacen, 3, 2) AS UNSIGNED)) ELSE 'Sin planta asignada' END)" : "CASE WHEN a.cve_almacen REGEXP '^PL[0-9]{2}-' THEN CONCAT('Planta ', CAST(SUBSTRING(a.cve_almacen, 3, 2) AS UNSIGNED)) ELSE 'Sin planta asignada' END") . " END"
                : "'Sin planta asignada'";
            $idPlantaExpr = ($hasAlmacen && $hasPlanta) ? "COALESCE(pl.idplanta, a.plantaid, 0)" : ($hasAlmacen ? "COALESCE(a.plantaid, 0)" : "0");

            // Filtrar estrictamente por unidades físicas actuales en PT (pt = 1 / ALM-TRANSITO con estado 1 o 2)
            // o unidades con facturación vigente en wms_vin_facturacion (excluye históricos ya vendidos estado = 3 sin facturación)
            $hasPtCol = $hasAlmacen && $this->columnExists('wms_almacenes', 'pt');
            $physWhere = "ns.estado IN ('1', '2')";
            if ($hasAlmacen) {
                $ptCond = $hasPtCol
                    ? "(a.pt = 1 AND a.estado = 2)"
                    : "(a.estado = 2 AND (UPPER(TRIM(COALESCE(a.cve_almacen, ''))) REGEXP '(^|-)ALM-PT(-|$)' OR UPPER(TRIM(COALESCE(a.descripcion, ''))) LIKE '%PRODUCTO TERMINADO%'))";
                $physWhere .= " AND ({$ptCond} OR (a.estado = 2 AND {$transitoCond}))";
            }
            $ptWhere = $hasFact
                ? "(({$physWhere}) OR (f.idfacturacion IS NOT NULL AND ns.estado IN ('1', '2', '3')))"
                : "({$physWhere})";

            $sqlWms = "SELECT
                            ns.id_numeros_serie,
                            ns.numero_serie AS vin,
                            COALESCE(NULLIF(TRIM(ns.referencia), ''), RIGHT(ns.numero_serie, 6)) AS num_serie,
                            " . ($hasAsoc ? "COALESCE(va.numero_motor, '')" : "''") . " AS numero_motor,
                            COALESCE(i.cve_articulo, '') AS sku,
                            {$modeloExpr} AS modelo,
                            {$subExpr} AS sublinea,
                            " . ($hasAsoc ? "COALESCE(NULLIF(TRIM(va.color), ''), 'Sin color')" : "'Sin color'") . " AS color,
                            " . ($hasAnio ? "COALESCE(an.anio, '')" : "''") . " AS anio,
                            {$idPlantaExpr} AS id_planta,
                            {$plantaClaveExpr} AS planta_clave,
                            {$plantaNombreExpr} AS planta_nombre,
                            COALESCE(ns.almacenid, 0) AS almacenid,
                            " . ($hasAlmacen ? "COALESCE(a.cve_almacen, 'SIN-ALM')" : "'SIN-ALM'") . " AS cve_almacen,
                            " . ($hasAlmacen ? "COALESCE(a.descripcion, 'Sin almacén asignado')" : "'Sin almacén asignado'") . " AS almacen_nombre,
                            ns.estado AS estado_vin_wms,
                            {$selectFact}
                            {$selectCob}
                            COALESCE(DATE_FORMAT(ns.fecha, '%Y-%m-%d'), '') AS fecha_registro
                        FROM wms_numeros_series ns
                        INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                        {$joinAlmacen}
                        {$joinPlanta}
                        {$joinAnio}
                        {$joinAsoc}
                        {$joinSub}
                        {$joinFact}
                        {$joinCob}
                        WHERE i.tipo_elemento = 'P'
                          AND i.estado = '2'
                          AND {$ptWhere}
                        ORDER BY ns.id_numeros_serie DESC";

            $rowsWms = [];
            try {
                $rowsWms = $this->select_all($sqlWms) ?: [];
            } catch (Throwable $e) {
                // Fallback seguro si alguna columna opcional (p. ej. facturable, nombre_comercial) difiere
                try {
                    $sqlSimple = "SELECT
                                    ns.id_numeros_serie,
                                    ns.numero_serie AS vin,
                                    COALESCE(NULLIF(TRIM(ns.referencia), ''), RIGHT(ns.numero_serie, 6)) AS num_serie,
                                    '' AS numero_motor,
                                    COALESCE(i.cve_articulo, '') AS sku,
                                    COALESCE(NULLIF(TRIM(i.descripcion), ''), 'Unidad PT') AS modelo,
                                    '' AS sublinea,
                                    'Sin color' AS color,
                                    '' AS anio,
                                    0 AS id_planta,
                                    'SIN' AS planta_clave,
                                    'Sin planta asignada' AS planta_nombre,
                                    COALESCE(ns.almacenid, 0) AS almacenid,
                                    'SIN-ALM' AS cve_almacen,
                                    'Almacén' AS almacen_nombre,
                                    ns.estado AS estado_vin_wms,
                                    0 AS idfacturacion,
                                    CASE WHEN ns.estado = '3' THEN 1 ELSE 0 END AS facturado,
                                    '' AS factura,
                                    '' AS fecha_factura,
                                    '' AS distribuidor,
                                    0 AS estatus_fact_wms,
                                    NULL AS estatus_pago_raw,
                                    '' AS fecha_pago,
                                    '' AS fecha_registro
                                  FROM wms_numeros_series ns
                                  INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                                  WHERE i.tipo_elemento = 'P' AND i.estado = '2' AND ns.estado IN ('1', '2', '3')
                                  ORDER BY ns.id_numeros_serie DESC";
                    $rowsWms = $this->select_all($sqlSimple) ?: [];
                } catch (Throwable $e2) {
                    $rowsWms = [];
                }
            }

            foreach ($rowsWms as $r) {
                $vinClean = strtoupper(trim((string)($r['vin'] ?? '')));
                if ($vinClean === '' || isset($byVin[$vinClean])) {
                    continue;
                }

                $idNs      = intval($r['id_numeros_serie']);
                $seg       = $this->clasificarSegmentoUnidad((string)$r['modelo'], (string)($r['sublinea'] ?? ''), $vinClean);
                $aux       = $bandejaByVin[$vinClean] ?? null;
                $idUnidad  = intval($aux['id_unidad'] ?? 0);
                $envioInfo = $envioByVin[$vinClean] ?? ($idUnidad > 0 ? ($envioByIdUnidad[$idUnidad] ?? null) : null);
                $preList   = array_values($preenviosByVin[$vinClean] ?? ($idUnidad > 0 ? ($preenviosByIdUnidad[$idUnidad] ?? []) : []));
                $pedInfo   = $pedidoBySerieId[$idNs] ?? null;

                $facturado = intval($r['facturado'] ?? 0) === 1 ? 1 : 0;
                $estatusFact = $facturado === 1 ? 'FACTURADA' : 'SIN_FACTURAR';
                $estatusFactTexto = $facturado === 1 ? 'Facturada' : 'Sin Facturar';

                if ($facturado === 1) {
                    $pagoCod = ($r['estatus_pago_raw'] !== null && $r['estatus_pago_raw'] !== '')
                        ? intval($r['estatus_pago_raw'])
                        : 0;
                    if (!isset($mapaPagoTexto[$pagoCod])) {
                        $pagoCod = 0;
                    }
                } else {
                    $pagoCod = -1;
                }
                $pagoTexto = $mapaPagoTexto[$pagoCod] ?? 'Sin Facturar';

                // Resolver Cliente Maestro (`cli_clientes`) en cascada: Factura -> Pedido -> Bandeja -> Coincidencia nombre
                $idCliente = intval($r['fact_id_cliente'] ?? 0);
                if ($idCliente <= 0 && $pedInfo) {
                    $idCliente = intval($pedInfo['idcliente'] ?? 0);
                }
                if ($idCliente <= 0 && $aux) {
                    $idCliente = intval($aux['lu_id_cliente'] ?? 0);
                }
                if ($idCliente <= 0 && trim((string)($r['distribuidor'] ?? '')) !== '') {
                    $normFnLocal = class_exists('Lgs_clientesSyncService')
                        ? Lgs_clientesSyncService::normalizarTexto((string)$r['distribuidor'])
                        : strtoupper(trim((string)$r['distribuidor']));
                    $kDist = str_replace(' ', '', $normFnLocal);
                    if (isset($clienteByNomNorm[$kDist])) {
                        $idCliente = intval($clienteByNomNorm[$kDist]['id_cliente']);
                    }
                }

                $clienteNombre = $idCliente > 0 && isset($clientesById[$idCliente])
                    ? $clientesById[$idCliente]['nombre']
                    : trim((string)($pedInfo['cliente_nombre'] ?? $r['distribuidor'] ?? ''));
                $idDistLgs = $idCliente > 0 ? intval($distByClienteId[$idCliente] ?? 0) : 0;

                // Resolver Sede seleccionada para este VIN (estrictamente perteneciente a su cliente)
                $sedeId = intval($aux['lu_id_ubicacion_destino'] ?? 0);
                if ($sedeId > 0 && $idCliente > 0 && isset($ubicacionById[$sedeId])) {
                    $uCli = intval($ubicacionById[$sedeId]['id_cliente'] ?? 0);
                    if ($uCli > 0 && $uCli !== $idCliente) {
                        $sedeId = 0;
                    }
                }
                if ($sedeId <= 0 && $pedInfo && !empty($pedInfo['sede_ped_ubicacion_id'])) {
                    $sedeId = intval($pedInfo['sede_ped_ubicacion_id']);
                }
                $sedeNombre = ($sedeId > 0 && isset($ubicacionById[$sedeId]))
                    ? (string)$ubicacionById[$sedeId]['nombre']
                    : trim((string)($pedInfo['sucursal_entrega_nombre'] ?? ''));
                $sedeDireccion = ($sedeId > 0 && isset($ubicacionById[$sedeId]))
                    ? (string)($ubicacionById[$sedeId]['direccion'] ?? '')
                    : trim((string)($pedInfo['direccion_entrega'] ?? ''));

                $destPedidoTxt = '';
                if ($pedInfo) {
                    $destPedidoTxt = trim((string)($pedInfo['sucursal_entrega_nombre'] ?: $pedInfo['direccion_entrega'] ?: ''));
                }

                $destinoSug = $clienteNombre !== '' ? $clienteNombre : trim((string)($r['distribuidor'] ?? ''));
                if ($sedeNombre !== '') {
                    $destinoSug = ($clienteNombre !== '' && stripos($sedeNombre, $clienteNombre) === false)
                        ? $clienteNombre . ' — ' . $sedeNombre
                        : $sedeNombre;
                } elseif ($destinoSug === '' && $aux) {
                    foreach ([$aux['destino_descripcion'] ?? '', $aux['destino_pedido'] ?? '', $aux['destino'] ?? ''] as $cand) {
                        $cand = trim((string)$cand);
                        if ($cand !== '' && $cand !== 'Por Confirmar' && $cand !== 'Por Definir') {
                            $destinoSug = $cand;
                            break;
                        }
                    }
                }
                if ($destinoSug === '') {
                    $destinoSug = 'Por Asignar';
                }

                $idFacturacion = intval($r['idfacturacion'] ?? 0);
                $facturaFolio  = trim((string)($r['factura'] ?? ''));
                $cumpleFactura = ($idFacturacion > 0 && $facturaFolio !== '');
                $cumplePago    = ($cumpleFactura && in_array($pagoCod, [1, 3], true));
                $cumpleAut     = (intval($aux['activado_comercial'] ?? 0) === 1);
                $estadoEnvio   = $this->resolverEstadoEnvioUnidad($cumpleFactura, $cumplePago, $cumpleAut, $facturaFolio, $pagoTexto);
                $idLgsUnidad   = intval($aux['id_lgs_unidad'] ?? 0);

                $byVin[$vinClean] = [
                    'id_numeros_serie'         => $idNs,
                    'id_unidad'                => $idUnidad,
                    'id_lgs_unidad'            => $idLgsUnidad,
                    'en_bandeja'               => $idLgsUnidad > 0 ? 1 : 0,
                    'vin'                      => $vinClean,
                    'num_serie'                => (string)($r['num_serie'] ?? ''),
                    'numero_motor'             => (string)($r['numero_motor'] ?? ''),
                    'sku'                      => (string)($r['sku'] ?? ''),
                    'modelo'                   => (string)($r['modelo'] ?? 'Unidad PT'),
                    'sublinea'                 => (string)($r['sublinea'] ?? ''),
                    'id_segmento'              => $seg['id_segmento'],
                    'segmento_nombre'          => $seg['segmento_nombre'],
                    'color'                    => (string)($r['color'] ?? 'Sin color'),
                    'anio'                     => (string)($r['anio'] ?? ''),
                    'id_planta'                => intval($r['id_planta'] ?? 0),
                    'planta_clave'             => (string)($r['planta_clave'] ?? 'SIN'),
                    'planta_nombre'            => (string)($r['planta_nombre'] ?? 'Sin planta asignada'),
                    'almacenid'                => intval($r['almacenid'] ?? 0),
                    'cve_almacen'              => (string)($r['cve_almacen'] ?? ''),
                    'almacen_nombre'           => (string)($r['almacen_nombre'] ?? ''),
                    'estado_vin_wms'           => (string)($r['estado_vin_wms'] ?? ''),
                    'idfacturacion'            => $idFacturacion,
                    'facturado'                => $facturado,
                    'estatus_facturacion'      => $estatusFact,
                    'estatus_facturacion_texto'=> $estatusFactTexto,
                    'factura'                  => $facturaFolio,
                    'fecha_factura'            => (string)($r['fecha_factura'] ?? ''),
                    'distribuidor'             => $clienteNombre !== '' ? $clienteNombre : (string)($r['distribuidor'] ?? ''),
                    'id_cliente'               => $idCliente,
                    'cliente_nombre'           => $clienteNombre,
                    'id_distribuidor_lgs'      => $idDistLgs,
                    'id_pedido'                => intval($pedInfo['idpedido'] ?? $aux['lu_id_pedido'] ?? 0),
                    'folio_pedido'             => (string)($pedInfo['folio_pedido'] ?? $aux['folio_pedido'] ?? ''),
                    'estatus_pedido'           => (string)($pedInfo['estatus_pedido'] ?? ''),
                    'sede_id'                  => $sedeId,
                    'sede_nombre'              => $sedeNombre,
                    'sede_direccion'           => $sedeDireccion,
                    'direccion_entrega_pedido' => $destPedidoTxt,
                    'destino_sugerido'         => $destinoSug,
                    'estatus_pago_cod'         => $pagoCod,
                    'estatus_pago_texto'       => $pagoTexto,
                    'fecha_pago'               => (string)($r['fecha_pago'] ?? ''),
                    'estatus_finanzas_bandeja' => (string)($aux['estatus_finanzas'] ?? ''),
                    'activado_comercial'       => intval($aux['activado_comercial'] ?? 0),
                    'cumple_factura'           => $estadoEnvio['cumple_factura'],
                    'cumple_pago'              => $estadoEnvio['cumple_pago'],
                    'cumple_autorizacion'      => $estadoEnvio['cumple_autorizacion'],
                    'se_envia'                 => $estadoEnvio['se_envia'],
                    'estado_envio_motivo'      => $estadoEnvio['estado_envio_motivo'],
                    'id_estado_proceso'        => intval($aux['id_estado_proceso'] ?? 0),
                    'envio_id'                 => intval($envioInfo['id_envio'] ?? 0),
                    'envio_folio'              => (string)($envioInfo['folio'] ?? ''),
                    'envio_estado'             => intval($envioInfo['id_estado'] ?? 0),
                    'preenvios'                => $preList,
                    'preenvio_folios'          => implode(', ', array_column($preList, 'folio')),
                    'fuente'                   => 'WMS_PT',
                ];
            }
        }

        // 4. Complementar con unidades activas en lgs_unidades_envios pendientes que aún no existan en WMS
        foreach ($bandejaByVin as $vinClean => $ax) {
            if (isset($byVin[$vinClean])) {
                continue;
            }
            if (intval($ax['id_estado_proceso'] ?? 1) === 3) {
                continue;
            }
            $modelo   = trim((string)($ax['modelo'] ?? 'Unidad PT'));
            $seg      = $this->clasificarSegmentoUnidad($modelo, '', $vinClean);
            $idUnidad = intval($ax['id_unidad'] ?? 0);
            $envioInfo = $envioByVin[$vinClean] ?? ($idUnidad > 0 ? ($envioByIdUnidad[$idUnidad] ?? null) : null);
            $preList   = array_values($preenviosByVin[$vinClean] ?? ($idUnidad > 0 ? ($preenviosByIdUnidad[$idUnidad] ?? []) : []));

            $estFin  = strtoupper(trim((string)($ax['estatus_finanzas'] ?? '')));
            $pagoCod = $mapFinanzasToPagoCod[$estFin] ?? -1;
            $idCliente = intval($ax['lu_id_cliente'] ?? 0);
            $clienteNombre = $idCliente > 0 && isset($clientesById[$idCliente]) ? $clientesById[$idCliente]['nombre'] : '';
            $idDistLgs = $idCliente > 0 ? intval($distByClienteId[$idCliente] ?? 0) : 0;
            $sedeId = intval($ax['lu_id_ubicacion_destino'] ?? 0);
            $sedeNombre = ($sedeId > 0 && isset($ubicacionById[$sedeId])) ? (string)$ubicacionById[$sedeId]['nombre'] : '';
            $sedeDireccion = ($sedeId > 0 && isset($ubicacionById[$sedeId])) ? (string)($ubicacionById[$sedeId]['direccion'] ?? '') : '';

            $destinoSug = $sedeNombre !== '' ? $sedeNombre : '';
            if ($destinoSug === '') {
                foreach ([$ax['destino_descripcion'] ?? '', $ax['destino_pedido'] ?? '', $ax['destino'] ?? ''] as $cand) {
                    $cand = trim((string)$cand);
                    if ($cand !== '' && $cand !== 'Por Confirmar' && $cand !== 'Por Definir') {
                        $destinoSug = $cand;
                        break;
                    }
                }
            }
            if ($destinoSug === '') {
                $destinoSug = 'Por Asignar';
            }

            $pagoTxtAux  = $mapaPagoTexto[$pagoCod] ?? 'Sin Facturar';
            $idLgsUnidad = intval($ax['id_lgs_unidad'] ?? 0);
            $cumpleAut   = (intval($ax['activado_comercial'] ?? 0) === 1);
            $estadoEnvio = $this->resolverEstadoEnvioUnidad(false, false, $cumpleAut, '', $pagoTxtAux);

            $byVin[$vinClean] = [
                'id_numeros_serie'         => intval($ax['id_numeros_serie'] ?? 0),
                'id_unidad'                => $idUnidad,
                'id_lgs_unidad'            => $idLgsUnidad,
                'en_bandeja'               => $idLgsUnidad > 0 ? 1 : 0,
                'vin'                      => $vinClean,
                'num_serie'                => (string)($ax['num_serie'] ?? ''),
                'numero_motor'             => '',
                'sku'                      => '',
                'modelo'                   => $modelo,
                'sublinea'                 => '',
                'id_segmento'              => $seg['id_segmento'],
                'segmento_nombre'          => $seg['segmento_nombre'],
                'color'                    => 'Sin color',
                'anio'                     => '',
                'id_planta'                => 0,
                'planta_clave'             => 'LOG',
                'planta_nombre'            => (string)($ax['origen'] ?: 'Logística / Planta'),
                'almacenid'                => 0,
                'cve_almacen'              => 'LGS',
                'almacen_nombre'           => 'Bandeja Logística',
                'estado_vin_wms'           => '',
                'idfacturacion'            => 0,
                'facturado'                => 0,
                'estatus_facturacion'      => 'SIN_FACTURAR',
                'estatus_facturacion_texto'=> 'Sin Facturar',
                'factura'                  => '',
                'fecha_factura'            => '',
                'distribuidor'             => $clienteNombre !== '' ? $clienteNombre : ($destinoSug !== 'Por Asignar' ? $destinoSug : ''),
                'id_cliente'               => $idCliente,
                'cliente_nombre'           => $clienteNombre,
                'id_distribuidor_lgs'      => $idDistLgs,
                'id_pedido'                => intval($ax['lu_id_pedido'] ?? 0),
                'folio_pedido'             => (string)($ax['folio_pedido'] ?? ''),
                'estatus_pedido'           => '',
                'sede_id'                  => $sedeId,
                'sede_nombre'              => $sedeNombre,
                'sede_direccion'           => $sedeDireccion,
                'direccion_entrega_pedido' => (string)($ax['destino_pedido'] ?? ''),
                'destino_sugerido'         => $destinoSug,
                'estatus_pago_cod'         => $pagoCod,
                'estatus_pago_texto'       => $pagoTxtAux,
                'fecha_pago'               => '',
                'estatus_finanzas_bandeja' => $estFin,
                'activado_comercial'       => intval($ax['activado_comercial'] ?? 0),
                'cumple_factura'           => $estadoEnvio['cumple_factura'],
                'cumple_pago'              => $estadoEnvio['cumple_pago'],
                'cumple_autorizacion'      => $estadoEnvio['cumple_autorizacion'],
                'se_envia'                 => $estadoEnvio['se_envia'],
                'estado_envio_motivo'      => $estadoEnvio['estado_envio_motivo'],
                'id_estado_proceso'        => intval($ax['id_estado_proceso'] ?? 0),
                'envio_id'                 => intval($envioInfo['id_envio'] ?? 0),
                'envio_folio'              => (string)($envioInfo['folio'] ?? ''),
                'envio_estado'             => intval($envioInfo['id_estado'] ?? 0),
                'preenvios'                => $preList,
                'preenvio_folios'          => implode(', ', array_column($preList, 'folio')),
                'fuente'                   => 'LGS_AUX',
            ];
        }

        $lista = array_values($byVin);

        // 6. Filtros opcionales desde servidor
        if (!empty($filtros['facturacion'])) {
            $facFilter = strtoupper(trim((string)$filtros['facturacion']));
            $lista = array_values(array_filter($lista, fn($x) => $x['estatus_facturacion'] === $facFilter));
        }
        if (isset($filtros['pago']) && $filtros['pago'] !== '') {
            $pagoFilter = intval($filtros['pago']);
            $lista = array_values(array_filter($lista, fn($x) => intval($x['estatus_pago_cod']) === $pagoFilter));
        }
        if (!empty($filtros['id_segmento'])) {
            $segFilter = intval($filtros['id_segmento']);
            $lista = array_values(array_filter($lista, fn($x) => intval($x['id_segmento']) === $segFilter));
        }
        if (!empty($filtros['planta'])) {
            $plFilter = trim((string)$filtros['planta']);
            $lista = array_values(array_filter($lista, fn($x) => $x['planta_nombre'] === $plFilter || (string)$x['id_planta'] === $plFilter));
        }
        if (!empty($filtros['id_cliente'])) {
            $cliFilter = intval($filtros['id_cliente']);
            $lista = array_values(array_filter($lista, fn($x) => intval($x['id_cliente'] ?? 0) === $cliFilter));
        }
        if (isset($filtros['con_pedido']) && $filtros['con_pedido'] !== '') {
            $conPed = strtoupper(trim((string)$filtros['con_pedido']));
            if ($conPed === 'SI' || $conPed === '1') {
                $lista = array_values(array_filter($lista, fn($x) => intval($x['id_pedido'] ?? 0) > 0));
            } elseif ($conPed === 'NO' || $conPed === '0') {
                $lista = array_values(array_filter($lista, fn($x) => intval($x['id_pedido'] ?? 0) === 0));
            }
        }
        if (!empty($filtros['busqueda'])) {
            $q = mb_strtolower(trim((string)$filtros['busqueda']), 'UTF-8');
            $lista = array_values(array_filter($lista, function ($x) use ($q) {
                $hay = mb_strtolower(
                    ($x['vin'] ?? '') . ' ' .
                    ($x['num_serie'] ?? '') . ' ' .
                    ($x['numero_motor'] ?? '') . ' ' .
                    ($x['sku'] ?? '') . ' ' .
                    ($x['modelo'] ?? '') . ' ' .
                    ($x['color'] ?? '') . ' ' .
                    ($x['factura'] ?? '') . ' ' .
                    ($x['distribuidor'] ?? '') . ' ' .
                    ($x['cliente_nombre'] ?? '') . ' ' .
                    ($x['folio_pedido'] ?? '') . ' ' .
                    ($x['sede_nombre'] ?? '') . ' ' .
                    ($x['destino_sugerido'] ?? '') . ' ' .
                    ($x['planta_nombre'] ?? '') . ' ' .
                    ($x['envio_folio'] ?? '') . ' ' .
                    ($x['preenvio_folios'] ?? ''),
                    'UTF-8'
                );
                return mb_strpos($hay, $q) !== false;
            }));
        }

        return $lista;
    }

    /**
     * Sedes activas agrupadas estrictamente por `cli_clientes.idcliente` y catálogo de clientes activos.
     */
    public function getSedesAgrupadasPorCliente(): array {
        if (class_exists('Lgs_clientesSyncService')) {
            try {
                (new Lgs_clientesSyncService($this->getConexion()))->sincronizarDistribuidoresYSedes();
            } catch (Throwable $e) {
            }
        }

        $sedesPorCliente = [];
        try {
            $rows = $this->select_all("
                SELECT u.id_ubicacion AS id,
                       u.nombre,
                       u.direccion,
                       u.id_tipo_destino,
                       u.id_distribuidor,
                       COALESCE(u.id_cliente, d.id_cliente, 0) AS id_cliente,
                       u.id_sucursal_cli,
                       u.id_direccion_cli
                FROM lgs_cat_ubicaciones u
                LEFT JOIN lgs_cat_distribuidores d ON d.id_distribuidor = u.id_distribuidor
                WHERE u.activo = 1
                  AND COALESCE(u.id_cliente, d.id_cliente, 0) > 0
                ORDER BY u.nombre ASC
            ") ?: [];
            foreach ($rows as $r) {
                $cid = strval(intval($r['id_cliente']));
                if (!isset($sedesPorCliente[$cid])) {
                    $sedesPorCliente[$cid] = [];
                }
                $sedesPorCliente[$cid][] = $r;
            }
        } catch (Throwable $e) {
            $sedesPorCliente = [];
        }

        $clientes = [];
        try {
            $hasGrupoCol = !empty($this->select("SHOW COLUMNS FROM cli_clientes LIKE 'grupo_comercial'"));
            $grupoColSql = $hasGrupoCol ? "COALESCE(NULLIF(TRIM(c.grupo_comercial), ''), '') AS grupo_comercial," : "'' AS grupo_comercial,";
            $clientes = $this->select_all("
                SELECT c.idcliente AS id_cliente,
                       COALESCE(NULLIF(TRIM(c.razon_social), ''), NULLIF(TRIM(c.nombre_comercial), ''), c.codigo_cliente) AS nombre,
                       {$grupoColSql}
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
            'sedes_por_cliente' => $sedesPorCliente,
            'clientes'          => $clientes,
        ];
    }

    /**
     * Asigna directamente una sede (`lgs_cat_ubicaciones`) validada contra el cliente dueño del VIN.
     */
    public function asignarSedeUnidadPT(int $idNumerosSerie, string $vin, int $idUbicacion, int $userId, array $unitSnapshot = []): array {
        $vinClean = strtoupper(trim($vin));
        if ($vinClean === '' && $idNumerosSerie <= 0) {
            throw new Exception("Identificador de unidad (VIN) no válido.");
        }
        if ($idUbicacion <= 0) {
            throw new Exception("Debe seleccionar una sede válida del catálogo.");
        }

        $db = $this->getConexion();
        $ubiSt = $db->prepare("
            SELECT u.id_ubicacion, u.nombre, u.direccion, u.id_distribuidor,
                   COALESCE(u.id_cliente, d.id_cliente, 0) AS id_cliente
            FROM lgs_cat_ubicaciones u
            LEFT JOIN lgs_cat_distribuidores d ON d.id_distribuidor = u.id_distribuidor
            WHERE u.id_ubicacion = ? AND u.activo = 1
            LIMIT 1
        ");
        $ubiSt->execute([$idUbicacion]);
        $ubi = $ubiSt->fetch(PDO::FETCH_ASSOC);
        if (!$ubi) {
            throw new Exception("La sede seleccionada no existe o está inactiva.");
        }

        if ($idNumerosSerie <= 0 && $vinClean !== '') {
            $nsSt = $db->prepare("SELECT id_numeros_serie, numero_serie FROM wms_numeros_series WHERE UPPER(TRIM(numero_serie)) = ? LIMIT 1");
            $nsSt->execute([$vinClean]);
            if ($nsRow = $nsSt->fetch(PDO::FETCH_ASSOC)) {
                $idNumerosSerie = intval($nsRow['id_numeros_serie']);
                $vinClean = strtoupper(trim((string)$nsRow['numero_serie']));
            }
        }

        // Determinar el cliente propietario autorizado del VIN
        $idClientePropietario = intval($unitSnapshot['id_cliente'] ?? 0);
        if ($idNumerosSerie > 0) {
            $fSt = $db->prepare("SELECT id_cliente FROM wms_vin_facturacion WHERE id_numeros_serie = ? AND estatus IN (1, 2) AND id_cliente IS NOT NULL LIMIT 1");
            $fSt->execute([$idNumerosSerie]);
            $fCli = $fSt->fetchColumn();
            if ($fCli) {
                $idClientePropietario = intval($fCli);
            } else {
                $pSt = $db->prepare("
                    SELECT p.idcliente
                    FROM ped_pedidos_vins pv
                    INNER JOIN ped_pedidos p ON p.idpedido = pv.idpedido
                    WHERE pv.id_numeros_serie = ? AND pv.estatus = 'ACTIVA'
                    LIMIT 1
                ");
                $pSt->execute([$idNumerosSerie]);
                $pCli = $pSt->fetchColumn();
                if ($pCli) {
                    $idClientePropietario = intval($pCli);
                }
            }
        }

        $ubiCliente = intval($ubi['id_cliente'] ?? 0);
        if ($idClientePropietario > 0 && $ubiCliente > 0 && $idClientePropietario !== $ubiCliente) {
            throw new Exception("La sede '{$ubi['nombre']}' no pertenece al cliente asignado/facturado de este VIN.");
        }
        $clienteFinal = $idClientePropietario > 0 ? $idClientePropietario : $ubiCliente;

        $payload = array_merge($unitSnapshot, [
            'id_numeros_serie' => $idNumerosSerie,
            'vin'              => $vinClean,
            'id_cliente'       => $clienteFinal,
            'sede_id'          => $idUbicacion,
            'sede_nombre'      => (string)$ubi['nombre'],
            'distribuidor'     => (string)$ubi['nombre'],
        ]);

        $db->beginTransaction();
        try {
            $sync = $this->sincronizarUnidadesPTInternal($db, [$payload], $userId);
            $idUnidad = intval($sync['map_ids'][$vinClean] ?? 0);
            if ($idUnidad <= 0) {
                throw new Exception("No se pudo vincular el registro operativo del VIN.");
            }

            $db->prepare("
                UPDATE lgs_unidades
                SET id_numeros_serie = COALESCE(NULLIF(?, 0), id_numeros_serie),
                    id_cliente = COALESCE(NULLIF(?, 0), id_cliente),
                    id_ubicacion_destino = ?,
                    destino_descripcion = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id_unidad = ?
            ")->execute([
                $idNumerosSerie,
                $clienteFinal,
                $idUbicacion,
                mb_substr((string)$ubi['nombre'], 0, 255),
                $userId,
                $idUnidad
            ]);

            $db->commit();
            return [
                'vin'          => $vinClean,
                'id_unidad'    => $idUnidad,
                'id_cliente'   => $clienteFinal,
                'sede_id'      => $idUbicacion,
                'sede_nombre'  => (string)$ubi['nombre'],
                'sede_dir'     => (string)($ubi['direccion'] ?? ''),
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Catálogos para el simulador de Posibles Envíos, Acomodos y Combinaciones.
     */
    public function getCatalogosSimuladorBandeja(): array {
        $plantas = [];
        try {
            $plantas = $this->select_all(
                "SELECT idplanta, cve_planta, nombre_planta, direccion
                 FROM mrp_planta
                 WHERE nombre_planta IS NOT NULL AND TRIM(nombre_planta) <> ''
                 ORDER BY nombre_planta ASC"
            ) ?: [];
        } catch (Throwable $e) {
            $plantas = [];
        }

        $tiposTraslado = [];
        try {
            $tiposTraslado = $this->select_all(
                "SELECT id_tipo_traslado AS id, nombre FROM lgs_cat_tipo_traslado WHERE activo = 1 ORDER BY id_tipo_traslado ASC"
            ) ?: [];
        } catch (Throwable $e) {
            $tiposTraslado = [];
        }
        if (empty($tiposTraslado)) {
            $tiposTraslado = [
                ['id' => 1, 'nombre' => 'Madrina'],
                ['id' => 2, 'nombre' => 'Chofer (Rodando)'],
                ['id' => 3, 'nombre' => 'Plataforma'],
            ];
        }

        $proveedores = [];
        try {
            $sqlProv = "SELECT p.id_proveedor AS id, p.razon_social AS nombre
                        FROM prv_cat_proveedores p
                        INNER JOIN prv_rel_proveedores_actividades r ON r.id_proveedor = p.id_proveedor
                        INNER JOIN prv_cat_actividades a ON a.id_actividad = r.id_actividad
                        WHERE a.cve_actividad = 'TRASLADO_UNIDADES' AND p.deleted_at IS NULL
                        ORDER BY p.razon_social ASC";
            $proveedores = $this->select_all($sqlProv) ?: [];
            if (empty($proveedores)) {
                $proveedores = $this->select_all(
                    "SELECT id_proveedor AS id, razon_social AS nombre FROM prv_cat_proveedores WHERE deleted_at IS NULL ORDER BY razon_social ASC"
                ) ?: [];
            }
        } catch (Throwable $e) {
            try {
                $proveedores = $this->select_all(
                    "SELECT id_proveedor AS id, razon_social AS nombre FROM prv_cat_proveedores WHERE deleted_at IS NULL ORDER BY razon_social ASC"
                ) ?: [];
            } catch (Throwable $e2) {
                $proveedores = [];
            }
        }

        $ubicaciones = [];
        try {
            if (file_exists("Models/Lgs_enviosModel.php")) {
                require_once "Models/Lgs_enviosModel.php";
                $envModel = new Lgs_enviosModel();
                $envModel->sincronizarPlantasYAlmacenes();
            }
            $ubicaciones = $this->select_all(
                "SELECT id_ubicacion AS id, nombre, direccion, id_tipo_destino, id_distribuidor, id_cliente, id_sucursal_cli, id_direccion_cli, id_planta_mrp, id_almacen_wms
                 FROM lgs_cat_ubicaciones
                 WHERE activo = 1
                 ORDER BY nombre ASC"
            ) ?: [];
        } catch (Throwable $e) {
            $ubicaciones = [];
        }

        return [
            'plantas'        => $plantas,
            'tipos_traslado' => $tiposTraslado,
            'proveedores'    => $proveedores,
            'ubicaciones'    => $ubicaciones,
        ];
    }

    /**
     * Simula combinación de unidades, factoraje, kilómetros (con memoria progresiva)
     * y costo estimado (sin IVA) a 5 decimales.
     */
    public function simularPosibleEnvioPT(array $params): array {
        $db = $this->getConexion();

        $idTipoTraslado = intval($params['id_tipo_traslado'] ?? 1);
        if (!in_array($idTipoTraslado, [1, 2, 3], true)) {
            $idTipoTraslado = 1;
        }
        $idProveedor = intval($params['id_proveedor'] ?? 0);
        $idOrigen    = intval($params['id_origen'] ?? 0);
        $idDestino   = intval($params['id_destino'] ?? 0);
        $kmManual    = floatval($params['km_estimado'] ?? 0);
        $vins        = is_array($params['vins'] ?? null) ? $params['vins'] : [];

        // 1. Consultar distancia en memoria progresiva si hay origen y destino
        $kmMemoria = null;
        if ($idOrigen > 0 && $idDestino > 0) {
            try {
                if (file_exists("Models/Lgs_enviosModel.php")) {
                    require_once "Models/Lgs_enviosModel.php";
                    $envModel = new Lgs_enviosModel();
                    $dist = $envModel->getDistanciaEntre($idOrigen, $idDestino, $db);
                    if ($dist !== null && $dist > 0) {
                        $kmMemoria = round((float)$dist, 5);
                    }
                }
            } catch (Throwable $e) {
                $kmMemoria = null;
            }
        }

        $kmUsar = ($kmManual > 0) ? round($kmManual, 5) : ($kmMemoria ?? 0.0);

        // 2. Clasificar combinación y semáforo de facturación/pago
        $conteo = [
            'ligeros'  => 0,
            'medianos' => 0,
            'pesados'  => 0,
            'buses'    => 0,
            'lowboy'   => 0,
        ];
        $semaforo = [
            'facturadas'      => 0,
            'sin_facturar'    => 0,
            'pagadas'         => 0,
            'credito'         => 0,
            'parciales'       => 0,
            'pendientes_pago' => 0,
        ];

        $segmentosList = [];
        foreach ($vins as $v) {
            $segId = intval($v['id_segmento'] ?? 0);
            if ($segId < 1 || $segId > 5) {
                $clas = $this->clasificarSegmentoUnidad((string)($v['modelo'] ?? ''), (string)($v['sublinea'] ?? ''), (string)($v['vin'] ?? ''));
                $segId = $clas['id_segmento'];
            }
            $segmentosList[] = $segId;

            if ($segId === 1) $conteo['ligeros']++;
            elseif ($segId === 2) $conteo['medianos']++;
            elseif ($segId === 3) $conteo['pesados']++;
            elseif ($segId === 4) $conteo['buses']++;
            elseif ($segId === 5) $conteo['lowboy']++;

            $facturado = intval($v['facturado'] ?? 0) === 1;
            if ($facturado) {
                $semaforo['facturadas']++;
                $pagoCod = intval($v['estatus_pago_cod'] ?? 0);
                if ($pagoCod === 3) $semaforo['pagadas']++;
                elseif ($pagoCod === 1) $semaforo['credito']++;
                elseif ($pagoCod === 2) $semaforo['parciales']++;
                else $semaforo['pendientes_pago']++;
            } else {
                $semaforo['sin_facturar']++;
            }
        }

        $numVins = count($vins);
        $partesComb = [];
        if ($conteo['ligeros'] > 0)  $partesComb[] = $conteo['ligeros'] . ' Ligero' . ($conteo['ligeros'] > 1 ? 's' : '');
        if ($conteo['medianos'] > 0) $partesComb[] = $conteo['medianos'] . ' Mediano' . ($conteo['medianos'] > 1 ? 's' : '');
        if ($conteo['pesados'] > 0)  $partesComb[] = $conteo['pesados'] . ' Pesado' . ($conteo['pesados'] > 1 ? 's' : '');
        if ($conteo['buses'] > 0)    $partesComb[] = $conteo['buses'] . ' Bus' . ($conteo['buses'] > 1 ? 'es' : '');
        if ($conteo['lowboy'] > 0)   $partesComb[] = $conteo['lowboy'] . ' Lowboy';
        $combinacionResumen = !empty($partesComb) ? implode(' + ', $partesComb) : 'Sin unidades seleccionadas';

        $alertas = [];
        if ($idTipoTraslado === 1 && $numVins === 1) {
            $alertas[] = "En Madrina no se permite enviar solo 1 unidad; agregue otra unidad o cambie a Plataforma / Chofer (Rodando).";
        }
        if ($idTipoTraslado === 1 && $numVins > 9) {
            $alertas[] = "La combinación supera 9 unidades; la tarifa aplica tope de factoraje (volumen 9) o requiere dividirse en más de 1 madrina.";
        }
        if ($idTipoTraslado === 3 && $conteo['lowboy'] === 0 && $numVins > 3) {
            $alertas[] = "En Plataforma estándar se cotizan hasta 3 unidades (las adicionales toman el factor de volumen 3).";
        }
        $noListasFin = $semaforo['sin_facturar'] + $semaforo['pendientes_pago'];
        if ($numVins > 0 && $noListasFin > 0) {
            $alertas[] = "{$noListasFin} unidad(es) en esta combinación aún están Sin Facturar o Pendientes de pago.";
        }

        // 3. Tarifas y costeo a 5 decimales (sin IVA)
        $costoPorKm        = 0.0;
        $factor            = 1.0;
        $precioUnitarioKm  = 0.0;
        $costoTotalEst     = 0.0;
        $costoUnitarioEst  = 0.0;
        $tipoServicio      = 'FORANEO';

        if ($numVins > 0) {
            try {
                require_once "Services/Lgs_tarifaCalculator.php";
                $calc = new Lgs_tarifaCalculator();

                if ($kmUsar > 0 && $kmUsar <= 40 && !($idTipoTraslado === 1 && $numVins < 3)) {
                    $tipoServicio = 'SLC';
                } elseif ($kmUsar > 40 && $kmUsar <= 80 && !($idTipoTraslado === 1 && $numVins < 3)) {
                    $tipoServicio = 'SLL';
                } else {
                    $tipoServicio = 'FORANEO';
                }

                if ($idTipoTraslado === 2) {
                    // Chofer (Rodando): factor = 1.0, costo según el segmento de cada unidad
                    $factor = 1.0;
                    $sumaCostos   = 0.0;
                    $sumaCostoKm  = 0.0;
                    foreach ($segmentosList as $segId) {
                        $tSeg = $calc->getTarifaAplicable($db, 2, $idProveedor, $segId, 1);
                        $cKm  = round((float)($tSeg['costo_por_km'] ?? 0), 5);
                        $sumaCostoKm += $cKm;
                        if ($tipoServicio === 'SLC') {
                            $sumaCostos += (float)($tSeg['precio_slc'] ?? 0);
                        } elseif ($tipoServicio === 'SLL') {
                            $sumaCostos += (float)($tSeg['precio_sll'] ?? 0);
                        } else {
                            $sumaCostos += round($cKm * $kmUsar, 5);
                        }
                    }
                    $costoPorKm       = round($sumaCostoKm / $numVins, 5);
                    $precioUnitarioKm = $costoPorKm;
                    $costoTotalEst    = round($sumaCostos, 5);
                    $costoUnitarioEst = round($costoTotalEst / $numVins, 5);
                } else {
                    $isLowboy = $conteo['lowboy'] > 0;
                    $segmentoDominante = ($idTipoTraslado === 3 && $isLowboy) ? 5 : 1;
                    $volumenParaTarifa = $numVins;
                    if ($idTipoTraslado === 1 && $volumenParaTarifa > 9) {
                        $volumenParaTarifa = 9;
                    } elseif ($idTipoTraslado === 3 && $isLowboy) {
                        $volumenParaTarifa = 4;
                    } elseif ($idTipoTraslado === 3 && $volumenParaTarifa > 3) {
                        $volumenParaTarifa = 3;
                    }

                    $tarifa = $calc->getTarifaAplicable($db, $idTipoTraslado, $idProveedor, $segmentoDominante, max(1, $volumenParaTarifa));
                    $costoPorKm       = round((float)($tarifa['costo_por_km'] ?? 0), 5);
                    $rawFactor        = (float)($tarifa['factor'] ?? 1.0);
                    $factor           = round($rawFactor > 0 ? $rawFactor : 1.0, 5);
                    $precioUnitarioKm = round($costoPorKm * $factor, 5);

                    if ($tipoServicio === 'SLC') {
                        $costoTotalEst = round((float)($tarifa['precio_slc'] ?? 0), 5);
                    } elseif ($tipoServicio === 'SLL') {
                        $costoTotalEst = round((float)($tarifa['precio_sll'] ?? 0), 5);
                    } else {
                        $costoTotalEst = round($precioUnitarioKm * $kmUsar * $numVins, 5);
                    }
                    $costoUnitarioEst = round($costoTotalEst / $numVins, 5);
                }
            } catch (Throwable $e) {
                // Silencioso en simulación si falla tarifa en entorno parcial
            }
        }

        return [
            'num_vins'              => $numVins,
            'conteo_segmentos'      => $conteo,
            'combinacion_resumen'   => $combinacionResumen,
            'semaforo_financiero'   => $semaforo,
            'km_memoria'            => $kmMemoria,
            'km_estimado'           => $kmUsar,
            'tipo_servicio'         => $tipoServicio,
            'costo_por_km'          => $costoPorKm,
            'factor'                => $factor,
            'precio_unitario_km'    => $precioUnitarioKm,
            'costo_total_estimado'  => $costoTotalEst,
            'costo_unitario_estimado'=> $costoUnitarioEst,
            'alertas'               => $alertas,
        ];
    }

    /**
     * Garantiza el registro de un VIN en lgs_unidades_envios y retorna su id_unidad.
     */
    private function ensureUnidadEnviosInternal(PDO $db, array $v): int {
        $vinClean = strtoupper(trim((string)($v['vin'] ?? '')));
        if ($vinClean === '') {
            return 0;
        }

        $stmtCheck = $db->prepare("SELECT id_unidad FROM lgs_unidades_envios WHERE UPPER(TRIM(vin)) = ? LIMIT 1");
        $stmtCheck->execute([$vinClean]);
        $idExist = $stmtCheck->fetchColumn();
        $idNs = intval($v['id_numeros_serie'] ?? 0);
        if ($idExist) {
            if ($idNs > 0) {
                $db->prepare("UPDATE lgs_unidades_envios SET id_numeros_serie = COALESCE(id_numeros_serie, ?) WHERE id_unidad = ?")
                   ->execute([$idNs, intval($idExist)]);
            }
            return intval($idExist);
        }

        $numSerie = trim((string)($v['num_serie'] ?? substr($vinClean, -6)));
        $modelo   = trim((string)($v['modelo'] ?? 'Unidad PT'));
        $origen   = trim((string)($v['planta_nombre'] ?? 'Planta'));
        $destino  = trim((string)($v['sede_nombre'] ?? $v['destino_sugerido'] ?? $v['distribuidor'] ?? 'Por Asignar'));

        $stmtIns = $db->prepare(
            "INSERT INTO lgs_unidades_envios (id_numeros_serie, vin, num_serie, modelo, origen, destino, estatus)
             VALUES (?, ?, ?, ?, ?, ?, 'disponible')"
        );
        $stmtIns->execute([
            $idNs > 0 ? $idNs : null,
            substr($vinClean, 0, 50),
            substr($numSerie, 0, 50),
            substr($modelo, 0, 100),
            substr($origen, 0, 150),
            substr($destino, 0, 150),
        ]);

        return intval($db->lastInsertId());
    }

    /**
     * Mapea el código de cobranza al estatus financiero de la bandeja logística.
     */
    private function mapPagoCodToBandejaFinanzas(?int $pagoCod, string $fallback = 'PENDIENTE'): string {
        if ($pagoCod === 3) return 'PAGADO';
        if ($pagoCod === 1) return 'CREDITO_AUTORIZADO';
        if ($pagoCod === 2) return 'PARCIAL_AUTORIZADO';
        if ($pagoCod === 0 || $pagoCod === -1) return 'PENDIENTE';
        return $fallback;
    }

    /**
     * Sincroniza internamente (dentro o fuera de transacción) unidades PT a lgs_unidades_envios y lgs_unidades.
     */
    public function sincronizarUnidadesPTInternal(PDO $db, array $vins, int $userId): array {
        $mapIds  = [];
        $creados = 0;
        $actualizados = 0;

        $stmtCheckLu = $db->prepare("SELECT id_lgs_unidad, destino_descripcion, estatus_finanzas FROM lgs_unidades WHERE id_unidad = ? LIMIT 1");
        $stmtInsLu   = $db->prepare(
            "INSERT INTO lgs_unidades
                (id_unidad, id_numeros_serie, id_cliente, id_ubicacion_destino, id_pedido, folio_pedido, destino_pedido, destino_descripcion, estatus_finanzas, id_estado_proceso, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())"
        );
        $stmtUpdLu   = $db->prepare(
            "UPDATE lgs_unidades
             SET estatus_finanzas = ?,
                 id_numeros_serie = COALESCE(id_numeros_serie, ?),
                 id_cliente = COALESCE(NULLIF(?, 0), id_cliente),
                 id_ubicacion_destino = COALESCE(id_ubicacion_destino, NULLIF(?, 0)),
                 id_pedido = COALESCE(id_pedido, NULLIF(?, 0)),
                 folio_pedido = COALESCE(NULLIF(folio_pedido, ''), NULLIF(?, '')),
                 destino_pedido = COALESCE(NULLIF(destino_pedido, ''), NULLIF(?, '')),
                 destino_descripcion = CASE
                     WHEN (destino_descripcion IS NULL OR TRIM(destino_descripcion) = '' OR destino_descripcion = 'Por Confirmar') AND ? <> '' AND ? <> 'Por Asignar'
                     THEN ? ELSE destino_descripcion END,
                 updated_by = ?,
                 updated_at = NOW()
             WHERE id_lgs_unidad = ?"
        );

        foreach ($vins as $v) {
            $vinClean = strtoupper(trim((string)($v['vin'] ?? '')));
            if ($vinClean === '') continue;

            $idUnidad = $this->ensureUnidadEnviosInternal($db, $v);
            if ($idUnidad <= 0) continue;

            $mapIds[$vinClean] = $idUnidad;

            $idNs      = intval($v['id_numeros_serie'] ?? 0);
            $idCli     = intval($v['id_cliente'] ?? 0);
            $sedeId    = intval($v['sede_id'] ?? 0);
            $idPed     = intval($v['id_pedido'] ?? 0);
            $folioPed  = trim((string)($v['folio_pedido'] ?? ''));
            $destPed   = trim((string)($v['direccion_entrega_pedido'] ?? ''));
            $pagoCod   = isset($v['estatus_pago_cod']) ? intval($v['estatus_pago_cod']) : null;
            $estFin    = $this->mapPagoCodToBandejaFinanzas($pagoCod, strtoupper(trim((string)($v['estatus_finanzas_bandeja'] ?? 'PENDIENTE'))) ?: 'PENDIENTE');
            $destSug   = trim((string)($v['sede_nombre'] ?? $v['distribuidor'] ?? $v['destino_sugerido'] ?? ''));
            if ($destSug === 'Por Asignar') {
                $destSug = '';
            }

            $stmtCheckLu->execute([$idUnidad]);
            $lu = $stmtCheckLu->fetch(PDO::FETCH_ASSOC);
            if (!$lu) {
                $stmtInsLu->execute([
                    $idUnidad,
                    $idNs > 0 ? $idNs : null,
                    $idCli > 0 ? $idCli : null,
                    $sedeId > 0 ? $sedeId : null,
                    $idPed > 0 ? $idPed : null,
                    $folioPed !== '' ? mb_substr($folioPed, 0, 50) : null,
                    $destPed !== '' ? mb_substr($destPed, 0, 255) : null,
                    $destSug !== '' ? mb_substr($destSug, 0, 255) : null,
                    $estFin,
                    $userId,
                ]);
                $creados++;
            } else {
                $stmtUpdLu->execute([
                    $estFin,
                    $idNs > 0 ? $idNs : null,
                    $idCli,
                    $sedeId,
                    $idPed,
                    $folioPed,
                    $destPed,
                    $destSug,
                    $destSug,
                    mb_substr($destSug, 0, 255),
                    $userId,
                    intval($lu['id_lgs_unidad']),
                ]);
                $actualizados++;
            }
        }

        return [
            'map_ids'      => $mapIds,
            'creados'      => $creados,
            'actualizados' => $actualizados,
            'total'        => count($mapIds),
        ];
    }

    /**
     * Sincroniza unidades PT seleccionadas hacia la Bandeja Operativa (lgs_unidades).
     */
    public function sincronizarUnidadesPTEnBandeja(array $vins, int $userId): array {
        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            $res = $this->sincronizarUnidadesPTInternal($db, $vins, $userId);
            $db->commit();
            return $res;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Crea un Posible Envío / Borrador en lgs_envios con los VINs seleccionados desde PT
     * y devuelve el enlace directo hacia su pantalla de Acomodo (/Lgs_envios/detalle/{id}).
     */
    public function crearPreEnvioDesdePT(array $data, array $vins, int $userId): array {
        if (empty($vins)) {
            throw new Exception("Debe seleccionar al menos una unidad PT para crear el envío.");
        }

        require_once "Models/Lgs_enviosModel.php";
        $enviosModel = new Lgs_enviosModel();
        $enviosModel->ensureDistanciasTable();

        $idTipoTraslado = intval($data['id_tipo_traslado'] ?? 1);
        if (!in_array($idTipoTraslado, [1, 2, 3], true)) {
            $idTipoTraslado = 1;
        }
        $idProveedor = intval($data['id_proveedor'] ?? 0);
        if ($idProveedor <= 0) {
            try {
                $rowProv = $this->select(
                    "SELECT p.id_proveedor
                     FROM prv_cat_proveedores p
                     LEFT JOIN prv_rel_proveedores_actividades r ON r.id_proveedor = p.id_proveedor
                     LEFT JOIN prv_cat_actividades a ON a.id_actividad = r.id_actividad
                     WHERE p.deleted_at IS NULL
                     ORDER BY (a.cve_actividad = 'TRASLADO_UNIDADES') DESC, p.id_proveedor ASC
                     LIMIT 1"
                );
                $idProveedor = intval($rowProv['id_proveedor'] ?? 1);
            } catch (Throwable $e) {
                $idProveedor = 1;
            }
        }

        $idOrigen  = intval($data['id_origen'] ?? 0);
        $idDestino = intval($data['id_destino'] ?? 0);
        $destLibre = trim((string)($data['destino_nombre_libre'] ?? ''));
        $kmEst     = floatval($data['km_estimado'] ?? 0);
        $fechaTent = trim((string)($data['fecha_tentativa_envio'] ?? ''));
        $obs       = trim((string)($data['observaciones'] ?? ''));

        $sim = $this->simularPosibleEnvioPT([
            'id_tipo_traslado' => $idTipoTraslado,
            'id_proveedor'     => $idProveedor,
            'id_origen'        => $idOrigen,
            'id_destino'       => $idDestino,
            'km_estimado'      => $kmEst,
            'vins'             => $vins,
        ]);

        $kmFinal       = floatval($sim['km_estimado'] ?? $kmEst);
        $costoTotalEst = floatval($sim['costo_total_estimado'] ?? 0);
        $costoUnitEst  = floatval($sim['costo_unitario_estimado'] ?? 0);
        $tipoServicio  = (string)($sim['tipo_servicio'] ?? 'FORANEO');
        $isLowboy      = intval($sim['conteo_segmentos']['lowboy'] ?? 0) > 0 ? 1 : 0;

        if ($destLibre === '') {
            $dests = [];
            foreach ($vins as $v) {
                $d = trim((string)($v['distribuidor'] ?? $v['destino_sugerido'] ?? ''));
                if ($d !== '' && $d !== 'Por Asignar') {
                    $dests[$d] = true;
                }
            }
            if (!empty($dests)) {
                $destLibre = implode(', ', array_slice(array_keys($dests), 0, 3));
            }
        }

        $obsFinal = ($obs !== '' ? $obs . ' · ' : '')
            . 'Pre-envío armado desde Unidades PT (' . ($sim['combinacion_resumen'] ?? '') . ')';

        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            $sync = $this->sincronizarUnidadesPTInternal($db, $vins, $userId);
            $mapIds = $sync['map_ids'];

            $folio = $enviosModel->generarFolioTransaccional($db);

            $idEnvio = $enviosModel->insertEnvio($db, [
                'folio'                 => $folio,
                'id_tipo_traslado'      => $idTipoTraslado,
                'tipo_servicio'         => 'AUTOMATICO',
                'modalidad_calculo'     => 'DIRECTO',
                'is_lowboy'             => $isLowboy,
                'id_motivo'             => 1,
                'id_proveedor'          => $idProveedor,
                'id_origen'             => $idOrigen > 0 ? $idOrigen : null,
                'id_destino'            => $idDestino > 0 ? $idDestino : null,
                'destino_nombre_libre'  => $destLibre !== '' ? substr($destLibre, 0, 255) : null,
                'km_total'              => round($kmFinal, 2),
                'costo_total'           => round($costoTotalEst, 2),
                'fecha_tentativa_envio' => $fechaTent !== '' ? str_replace('T', ' ', $fechaTent) : null,
                'observaciones'         => $obsFinal,
                'id_estado'             => 1,
                'created_by'            => $userId,
            ]);

            $idNodoOrigen = null;
            if ($idOrigen > 0) {
                $idNodoOrigen = $enviosModel->insertNodoOrigen($db, $idEnvio, $idOrigen);
            }

            $idNodoDestino = null;
            if ($idDestino > 0 || $destLibre !== '') {
                $idNodoDestino = $enviosModel->insertParada($db, [
                    'id_envio'             => $idEnvio,
                    'orden'                => 1,
                    'tipo_nodo'            => 'entrega',
                    'id_ubicacion'         => $idDestino > 0 ? $idDestino : null,
                    'id_origen_referencia' => $idNodoOrigen,
                    'destino_nombre_libre' => $destLibre !== '' ? substr($destLibre, 0, 255) : null,
                    'km_tramo'             => round($kmFinal, 2),
                ]);
            }

            $pos = 1;
            foreach ($vins as $v) {
                $vinClean = strtoupper(trim((string)($v['vin'] ?? '')));
                $idUnidad = intval($mapIds[$vinClean] ?? 0);
                if ($idUnidad <= 0) continue;

                $destVin = trim((string)($v['distribuidor'] ?? $v['destino_sugerido'] ?? $destLibre));
                if ($destVin === 'Por Asignar') {
                    $destVin = $destLibre;
                }

                $enviosModel->insertVin($db, [
                    'id_envio'             => $idEnvio,
                    'id_unidad'            => $idUnidad,
                    'id_numeros_serie'     => intval($v['id_numeros_serie'] ?? 0) ?: null,
                    'id_parada'            => $idNodoDestino,
                    'id_nodo_subida'       => $idNodoOrigen,
                    'id_nodo_bajada'       => $idNodoDestino,
                    'destino_nombre_libre' => $destVin !== '' ? substr($destVin, 0, 255) : null,
                    'posicion_acomodo'     => $pos++,
                    'costo_unidad'         => round($costoUnitEst, 2),
                    'tipo_servicio'        => $tipoServicio,
                    'id_estado'            => 1,
                ]);
            }

            $db->commit();

            if ($idOrigen > 0 && $idDestino > 0 && $kmFinal > 0) {
                $enviosModel->saveDistancia($idOrigen, $idDestino, $kmFinal);
            }

            // Recalcular costo oficial si aplica (silencioso si es 1 VIN en Madrina aún en borrador)
            if (file_exists("Services/Lgs_enviosService.php")) {
                try {
                    require_once "Services/Lgs_enviosService.php";
                    $envService = new Lgs_enviosService();
                    $envService->recalcularCostoTotal($idEnvio);
                } catch (Throwable $eRecalc) {
                    // Se mantiene la estimación hasta que el usuario ajuste unidades/transporte en Acomodo
                }
            }

            return [
                'id_envio'    => $idEnvio,
                'folio_envio' => $folio,
                'total_vins'  => ($pos - 1),
                'url_acomodo' => base_url() . '/Lgs_envios/detalle/' . $idEnvio,
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Adjunta VINs seleccionados desde Bandeja PT a un Envío/Pre-envío guardado en `Lgs_envios::store()`,
     * emparejando automáticamente cada VIN con su parada de entrega por Sede / Cliente.
     */
    public function adjuntarVinsPTAlEnvio(PDO $db, int $idEnvio, array $vins, array $nodoIdMapByIndex, array $nodosInput, int $userId): int
    {
        if ($idEnvio <= 0 || empty($vins)) {
            return 0;
        }

        $cntSt = $db->prepare("SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = ?");
        $cntSt->execute([$idEnvio]);
        if (intval($cntSt->fetchColumn()) > 0) {
            return 0;
        }

        $sync = $this->sincronizarUnidadesPTInternal($db, $vins, $userId);
        $mapIds = $sync['map_ids'] ?? [];

        // Construir mapa de nodos de bajada disponibles por ubicación y por distribuidor
        $nodoSubida = !empty($nodoIdMapByIndex[0]) ? intval($nodoIdMapByIndex[0]) : null;
        $nodoPorUbicacion = [];
        $nodoPorDist = [];
        $ultimoNodoEntrega = null;
        foreach ($nodosInput as $idx => $n) {
            if ($idx === 0) continue;
            $tipo = trim((string)($n['tipo_nodo'] ?? 'entrega'));
            if ($tipo === 'carga') continue;
            $nid = intval($nodoIdMapByIndex[$idx] ?? 0);
            if ($nid <= 0) continue;
            $ultimoNodoEntrega = $nid;
            $uId = intval($n['id_ubicacion'] ?? $n['id_destino_cat'] ?? 0);
            $dId = intval($n['id_distribuidor'] ?? 0);
            if ($uId > 0 && !isset($nodoPorUbicacion[$uId])) {
                $nodoPorUbicacion[$uId] = $nid;
            }
            if ($dId > 0 && !isset($nodoPorDist[$dId])) {
                $nodoPorDist[$dId] = $nid;
            }
        }

        require_once "Models/Lgs_enviosModel.php";
        $envModel = new Lgs_enviosModel();
        $pos = 1;
        $insertados = 0;

        foreach ($vins as $v) {
            $vinClean = strtoupper(trim((string)($v['vin'] ?? '')));
            $idUnidad = intval($mapIds[$vinClean] ?? 0);
            if ($idUnidad <= 0) continue;

            $sedeId = intval($v['sede_id'] ?? 0);
            $distId = intval($v['id_distribuidor_lgs'] ?? 0);
            $nodoBajada = $nodoPorUbicacion[$sedeId] ?? $nodoPorDist[$distId] ?? $ultimoNodoEntrega;
            $destLibre = trim((string)($v['sede_nombre'] ?? $v['cliente_nombre'] ?? $v['distribuidor'] ?? ''));

            $envModel->insertVin($db, [
                'id_envio'             => $idEnvio,
                'id_unidad'            => $idUnidad,
                'id_numeros_serie'     => intval($v['id_numeros_serie'] ?? 0) ?: null,
                'id_parada'            => $nodoBajada,
                'id_nodo_subida'       => $nodoSubida,
                'id_nodo_bajada'       => $nodoBajada,
                'destino_nombre_libre' => $destLibre !== '' ? mb_substr($destLibre, 0, 255) : null,
                'posicion_acomodo'     => $pos++,
                'tipo_servicio'        => 'AUTOMATICO',
                'id_estado'            => 1,
            ]);
            $insertados++;
        }

        return $insertados;
    }
}
