<?php

class Lgs_bandejaModel extends Mysql {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Obtiene todos los VINs elegibles para la bandeja de logística.
     * Condiciones: proceso activo 6/13/20, liberada=1, solicitado=1, finanzas aprobado=3
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
            $where .= " AND (u.vin LIKE ? OR u.num_serie LIKE ?)";
            $term = '%' . $filtros['busqueda'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $sql = "SELECT
                    lu.id_lgs_unidad,
                    lu.id_unidad,
                    u.vin,
                    u.num_serie,
                    u.modelo       AS modelo_unidad,
                    u.color        AS color_unidad,
                    m.descripcion  AS motivo_envio,
                    d.descripcion  AS tipo_destino,
                    lu.destino_descripcion,
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
                INNER JOIN mrp_unidades_terminadas u ON u.id_unidad = lu.id_unidad
                LEFT JOIN  lgs_cat_motivo_envio m    ON m.id_motivo  = lu.id_motivo
                LEFT JOIN  lgs_cat_destino d         ON d.id_destino = lu.id_destino
                {$where}
                ORDER BY lu.id_lgs_unidad DESC";

        return $this->select_all($sql, $params);
    }

    /**
     * Obtiene el detalle completo de una unidad en logística.
     */
    public function getUnidadDetalle(int $idLgsUnidad): ?array {
        $sql = "SELECT
                    lu.*,
                    u.vin,
                    u.num_serie,
                    u.modelo       AS modelo_unidad,
                    u.color        AS color_unidad,
                    m.descripcion  AS motivo_envio,
                    m.cve_motivo,
                    d.descripcion  AS tipo_destino,
                    d.cve_destino
                FROM lgs_unidades lu
                INNER JOIN mrp_unidades_terminadas u ON u.id_unidad  = lu.id_unidad
                LEFT JOIN  lgs_cat_motivo_envio m    ON m.id_motivo  = lu.id_motivo
                LEFT JOIN  lgs_cat_destino d         ON d.id_destino = lu.id_destino
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
        return $this->select_all("SELECT id_motivo, cve_motivo, descripcion FROM lgs_cat_motivo_envio WHERE activo = 1 ORDER BY descripcion ASC");
    }

    public function getDestinos(): array {
        return $this->select_all("SELECT id_tipo_destino AS id_destino, cve_destino, descripcion FROM lgs_cat_tipo_destino WHERE activo = 1 ORDER BY descripcion ASC");
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
