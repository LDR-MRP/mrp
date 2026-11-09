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
     * Obtiene la lista unificada de distribuidores para autocompletar en la bandeja
     */
    public function getListaDistribuidores(): array {
        try {
            $sql = "SELECT DISTINCT TRIM(nombre) COLLATE utf8mb4_unicode_ci AS nombre FROM lgs_cat_destinos WHERE activo = 1 AND nombre IS NOT NULL AND TRIM(nombre) <> ''
                    UNION
                    SELECT DISTINCT TRIM(destino_descripcion) COLLATE utf8mb4_unicode_ci AS nombre FROM lgs_unidades WHERE destino_descripcion IS NOT NULL AND TRIM(destino_descripcion) <> ''
                    UNION
                    SELECT DISTINCT TRIM(destino) COLLATE utf8mb4_unicode_ci AS nombre FROM lgs_unidades_envios WHERE destino IS NOT NULL AND TRIM(destino) <> ''
                    ORDER BY nombre ASC";
            $res = $this->select_all($sql);
            return $res ?: [];
        } catch (Throwable $e) {
            $list1 = $this->select_all("SELECT DISTINCT TRIM(nombre) AS nombre FROM lgs_cat_destinos WHERE activo = 1 AND nombre IS NOT NULL AND TRIM(nombre) <> ''") ?: [];
            $list2 = $this->select_all("SELECT DISTINCT TRIM(destino_descripcion) AS nombre FROM lgs_unidades WHERE destino_descripcion IS NOT NULL AND TRIM(destino_descripcion) <> ''") ?: [];
            $list3 = $this->select_all("SELECT DISTINCT TRIM(destino) AS nombre FROM lgs_unidades_envios WHERE destino IS NOT NULL AND TRIM(destino) <> ''") ?: [];

            $nombres = [];
            foreach (array_merge($list1, $list2, $list3) as $row) {
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
}
