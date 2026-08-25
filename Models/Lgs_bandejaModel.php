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

            $checkUnidades = $this->select_all("SELECT id_unidad FROM lgs_unidades_envios LIMIT 1");
            if (empty($checkUnidades)) {
                $seedUnidades = "INSERT INTO `lgs_unidades_envios` (`vin`, `num_serie`, `modelo`, `origen`, `destino`, `estatus`) VALUES
                ('VIN-2026-TOL-001', 'SN-8801', 'Camión Eléctrico E-Truck 4x2', 'Planta Toluca', 'Distribuidor CDMX Sur', 'disponible'),
                ('VIN-2026-TOL-002', 'SN-8802', 'Tractocamión Heavy Duty 6x4', 'Planta Toluca', 'Agencia Monterrey', 'disponible'),
                ('VIN-2026-TOL-003', 'SN-8803', 'Van Carga Urbana 3.5T', 'Planta Toluca', 'Puebla Centro', 'disponible'),
                ('VIN-2026-TOL-004', 'SN-8804', 'Chasis Cabina Diesel', 'Planta Toluca', 'Guadalajara Norte', 'disponible'),
                ('VIN-2026-TOL-005', 'SN-8805', 'Autobús Urbano 30 Pasajeros', 'Planta Toluca', 'Querétaro Parque Ind.', 'disponible'),
                ('VIN-2026-TOL-006', 'SN-8806', 'Camión de Volteo 14m3', 'Planta Toluca', 'León Guanajuato', 'disponible'),
                ('VIN-2026-TOL-007', 'SN-8807', 'Pickup 4x4 Doble Cabina', 'Planta Toluca', 'Veracruz Puerto', 'disponible'),
                ('VIN-2026-TOL-008', 'SN-8808', 'Panel Repartidor 2.0L', 'Planta Toluca', 'San Luis Potosí', 'disponible')
                ON DUPLICATE KEY UPDATE `estatus` = VALUES(`estatus`)";
                $this->insert($seedUnidades, []);
            }

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

            // Sembrar lgs_unidades con unidades de prueba si está vacía
            $checkBandeja = $this->select_all("SELECT id_lgs_unidad FROM lgs_unidades LIMIT 1");
            if (empty($checkBandeja)) {
                $seedBandeja = "INSERT INTO `lgs_unidades` (`id_unidad`, `id_motivo`, `id_destino`, `destino_descripcion`, `id_estado_proceso`, `created_by`)
                SELECT `id_unidad`, 1, 1, `destino`, 1, 1 FROM `lgs_unidades_envios`";
                $this->insert($seedBandeja, []);
            }

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

        } catch (Throwable $e) {
            // Manejo silencioso para no interrumpir el flujo si las tablas ya están creadas
        }
    }

    /**
     * Obtiene todos los VINs elegibles para la bandeja de logística.
     */
    public function getUnidadesBandeja(array $filtros = []): array {
        $where  = "WHERE 1=1";
        $params = [];

        // Filtro por estado logístico
        if (!empty($filtros['id_estado_proceso'])) {
            $where .= " AND lu.id_estado_proceso = ?";
            $params[] = intval($filtros['id_estado_proceso']);
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

        // Búsqueda por VIN o folio
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (u.vin LIKE ? OR u.num_serie LIKE ? OR ut.clave LIKE ? OR ut.num_unidad LIKE ?)";
            $term = '%' . $filtros['busqueda'] . '%';
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
                    COALESCE(lu.destino_descripcion, u.destino, 'Por Definir') AS destino_descripcion,
                    lu.id_estado_proceso,
                    CASE lu.id_estado_proceso
                        WHEN 1 THEN 'Pendiente'
                        WHEN 2 THEN 'En Tránsito'
                        WHEN 3 THEN 'Entregado'
                        ELSE 'Desconocido'
                    END AS estado_proceso_texto,
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
}
