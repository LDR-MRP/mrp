<?php

class Lgs_gastosadicionalesModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_tra_gastos_adicionales';

    public function getTableName(): string
    {
        return $this->table;
    }

    public function getConexion(): PDO
    {
        return $this->conexion;
    }

    /**
     * Genera un nuevo folio transaccional: GA-000001
     */
    public function generarFolioTransaccional(PDO $db): string
    {
        $sql = "SELECT folio FROM lgs_tra_gastos_adicionales ORDER BY id_gasto DESC LIMIT 1 FOR UPDATE";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $ultimo = $stmt->fetchColumn();

        if (!$ultimo) {
            return 'GA-000001';
        }

        $num = intval(substr($ultimo, 3)) + 1;
        return 'GA-' . str_pad((string)$num, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene los catálogos para formularios de gastos adicionales
     */
    public function getCatalogosFormulario(): array
    {
        $tipos = $this->select_all("SELECT id_tipo_gasto, clave, nombre, categoria, alcance, responsable, 
                                           naturaleza, documento, calculo, unidad_medida, requiere_incidencia, requiere_documento_soporte
                                    FROM lgs_cat_tipos_gasto_adicional 
                                    WHERE activo = 1 
                                    ORDER BY id_tipo_gasto ASC") ?: [];

        $motivos = $this->select_all("SELECT id_motivo_gasto, id_tipo_gasto, descripcion 
                                      FROM lgs_cat_motivos_gasto_adicional 
                                      WHERE activo = 1 
                                      ORDER BY descripcion ASC") ?: [];

        $proveedores = $this->select_all("SELECT p.id_proveedor AS id, p.razon_social AS nombre 
                                          FROM prv_cat_proveedores p 
                                          WHERE p.deleted_at IS NULL 
                                          ORDER BY p.razon_social ASC") ?: [];

        $tiposIncidencia = $this->select_all("SELECT id_tipo_incidencia, clave, nombre 
                                              FROM lgs_cat_tipos_incidencia 
                                              WHERE activo = 1 
                                              ORDER BY nombre ASC") ?: [];

        $absorciones = $this->select_all("SELECT id_absorcion, clave, nombre 
                                          FROM lgs_cat_absorcion_danios 
                                          WHERE activo = 1 
                                          ORDER BY id_absorcion ASC") ?: [];

        return [
            'tipos_gasto'      => $tipos,
            'motivos_gasto'    => $motivos,
            'proveedores'      => $proveedores,
            'tipos_incidencia' => $tiposIncidencia,
            'absorciones'      => $absorciones,
        ];
    }

    /**
     * Busca incidencias disponibles para asociar gastos (estados 1: Abierta, 2: En Investigación, 3: Dictaminada, 4: Con Gastos)
     * Soporta búsqueda ágil multi-parámetro
     */
    public function buscarIncidenciasDisponibles(string $query = '', array $filtros = []): array
    {
        $where = "WHERE i.deleted_at IS NULL AND i.id_estado IN (1, 2, 3, 4)";
        $params = [];

        if (!empty(trim($query))) {
            $where .= " AND (i.folio LIKE ? 
                             OR e.folio LIKE ? 
                             OR plan.folio LIKE ? 
                             OR iv.vin LIKE ? 
                             OR p.razon_social LIKE ? 
                             OR ti.nombre LIKE ? 
                             OR i.descripcion LIKE ?)";
            $t = '%' . trim($query) . '%';
            for ($k = 0; $k < 7; $k++) {
                $params[] = $t;
            }
        }

        if (!empty($filtros['id_tipo_incidencia'])) {
            $where .= " AND i.id_tipo_incidencia = ?";
            $params[] = intval($filtros['id_tipo_incidencia']);
        }

        if (!empty($filtros['id_absorcion'])) {
            $where .= " AND i.id_absorcion = ?";
            $params[] = intval($filtros['id_absorcion']);
        }

        if (!empty($filtros['id_proveedor'])) {
            $where .= " AND i.id_proveedor = ?";
            $params[] = intval($filtros['id_proveedor']);
        }

        if (!empty($filtros['folio_planeacion'])) {
            $where .= " AND plan.folio LIKE ?";
            $params[] = '%' . trim($filtros['folio_planeacion']) . '%';
        }

        if (!empty($filtros['vin'])) {
            $where .= " AND iv.vin LIKE ?";
            $params[] = '%' . trim($filtros['vin']) . '%';
        }

        $sql = "SELECT DISTINCT
                    i.id_incidencia,
                    i.folio,
                    i.id_envio,
                    e.folio AS folio_envio,
                    e.id_estado AS estado_envio,
                    COALESCE(plan.folio, CONCAT('PLN-', LPAD(plan.id_planeacion, 5, '0')), 'Sin Planeación') AS folio_planeacion,
                    ti.nombre AS tipo_incidencia,
                    ti.clave AS clave_tipo_incidencia,
                    ti.requiere_dictamen,
                    ti.id_tipo_gasto_sugerido,
                    i.id_proveedor,
                    COALESCE(p.razon_social, 'Sin Proveedor') AS trasladista,
                    i.id_absorcion,
                    ad.nombre AS absorcion_nombre,
                    ad.clave AS absorcion_clave,
                    i.porcentaje_proveedor,
                    i.id_estado,
                    i.fecha_incidente,
                    i.descripcion,
                    (SELECT COUNT(*) FROM lgs_det_incidencias_vins v WHERE v.id_incidencia = i.id_incidencia) AS total_vins_afectados,
                    (SELECT GROUP_CONCAT(DISTINCT COALESCE(iv_sub.vin, '') SEPARATOR ', ')
                     FROM lgs_det_incidencias_vins iv_sub
                     WHERE iv_sub.id_incidencia = i.id_incidencia
                    ) AS vins_afectados_str
                FROM lgs_tra_incidencias i
                INNER JOIN lgs_envios e ON i.id_envio = e.id_envio
                LEFT JOIN lgs_planeaciones plan ON e.id_planeacion = plan.id_planeacion
                INNER JOIN lgs_cat_tipos_incidencia ti ON i.id_tipo_incidencia = ti.id_tipo_incidencia
                LEFT JOIN prv_cat_proveedores p ON i.id_proveedor = p.id_proveedor
                LEFT JOIN lgs_cat_absorcion_danios ad ON i.id_absorcion = ad.id_absorcion
                LEFT JOIN lgs_det_incidencias_vins iv ON iv.id_incidencia = i.id_incidencia
                {$where}
                ORDER BY i.id_incidencia DESC
                LIMIT 60";

        return $this->select_all($sql, $params) ?: [];
    }

    /**
     * Obtiene información detallada del envío para costeo y cálculo
     */
    public function getEnvioConSegmentoDominante(int $idEnvio): ?array
    {
        $sql = "SELECT 
                    e.id_envio,
                    e.folio,
                    e.id_tipo_traslado,
                    e.id_proveedor,
                    e.km_total,
                    e.costo_total,
                    e.id_estado,
                    COALESCE(pr.razon_social, 'Sin Proveedor') AS trasladista,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins,
                    COALESCE(MAX(mv.id_segmento), 1) AS id_segmento_dominante
                FROM lgs_envios e
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_envios_vins ev ON ev.id_envio = e.id_envio
                LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                LEFT JOIN cat_modelos_vin mv ON mv.modelo = u.modelo
                WHERE e.id_envio = ? AND e.deleted_at IS NULL
                GROUP BY e.id_envio";

        $res = $this->select($sql, [$idEnvio]);
        return !empty($res) ? $res : null;
    }

    /**
     * Consulta para el DataTable de Gastos Adicionales
     */
    public function getGastosDataTable(array $filtros = []): array
    {
        $where = "WHERE g.deleted_at IS NULL";
        $params = [];

        if (!empty($filtros['id_estado'])) {
            $where .= " AND g.id_estado = ?";
            $params[] = intval($filtros['id_estado']);
        }
        if (!empty($filtros['id_tipo_gasto'])) {
            $where .= " AND g.id_tipo_gasto = ?";
            $params[] = intval($filtros['id_tipo_gasto']);
        }
        if (!empty($filtros['id_proveedor'])) {
            $where .= " AND g.id_proveedor = ?";
            $params[] = intval($filtros['id_proveedor']);
        }
        if (isset($filtros['es_independiente']) && $filtros['es_independiente'] !== '') {
            if (intval($filtros['es_independiente']) === 1) {
                $where .= " AND g.id_incidencia IS NULL";
            } else {
                $where .= " AND g.id_incidencia IS NOT NULL";
            }
        }

        $sql = "SELECT 
                    g.id_gasto,
                    g.folio,
                    g.id_envio,
                    e.folio AS folio_envio,
                    e.id_estado AS estado_envio,
                    g.id_incidencia,
                    inc.folio AS folio_incidencia,
                    tg.nombre AS tipo_gasto,
                    tg.clave AS clave_tipo_gasto,
                    tg.categoria,
                    g.alcance_aplicado,
                    g.responsable,
                    g.naturaleza,
                    g.documento,
                    COALESCE(pr.razon_social, 'Sin Proveedor') AS trasladista,
                    g.monto_calculado,
                    g.monto_final,
                    g.moneda,
                    g.fecha_gasto,
                    g.descripcion,
                    g.id_estado,
                    g.doc_folio,
                    g.doc_uuid,
                    (SELECT COUNT(*) FROM lgs_det_gastos_adicionales_vins gv WHERE gv.id_gasto = g.id_gasto) AS total_vins_reparto,
                    (SELECT COUNT(*) FROM lgs_det_gastos_adicionales_docs gd WHERE gd.id_gasto = g.id_gasto) AS total_documentos,
                    g.created_at
                FROM lgs_tra_gastos_adicionales g
                INNER JOIN lgs_envios e ON g.id_envio = e.id_envio
                INNER JOIN lgs_cat_tipos_gasto_adicional tg ON g.id_tipo_gasto = tg.id_tipo_gasto
                LEFT JOIN lgs_tra_incidencias inc ON g.id_incidencia = inc.id_incidencia
                LEFT JOIN prv_cat_proveedores pr ON g.id_proveedor = pr.id_proveedor
                {$where}
                ORDER BY g.id_gasto DESC";

        return $this->select_all($sql, $params) ?: [];
    }

    /**
     * Obtiene el detalle completo de un gasto
     */
    public function getGastoDetalle(int $idGasto): ?array
    {
        $sql = "SELECT 
                    g.*,
                    e.folio AS folio_envio,
                    e.id_estado AS estado_envio,
                    inc.folio AS folio_incidencia,
                    inc.descripcion AS descripcion_incidencia,
                    tg.nombre AS tipo_gasto,
                    tg.clave AS clave_tipo_gasto,
                    tg.categoria,
                    tg.calculo,
                    mg.descripcion AS motivo_descripcion,
                    COALESCE(pr.razon_social, 'Sin Proveedor') AS trasladista,
                    ad.nombre AS absorcion_nombre,
                    CONCAT(u_reg.nombres, ' ', u_reg.apellidos) AS registrado_por,
                    CONCAT(u_apr.nombres, ' ', u_apr.apellidos) AS aprobado_por
                FROM lgs_tra_gastos_adicionales g
                INNER JOIN lgs_envios e ON g.id_envio = e.id_envio
                INNER JOIN lgs_cat_tipos_gasto_adicional tg ON g.id_tipo_gasto = tg.id_tipo_gasto
                LEFT JOIN lgs_tra_incidencias inc ON g.id_incidencia = inc.id_incidencia
                LEFT JOIN lgs_cat_motivos_gasto_adicional mg ON g.id_motivo_gasto = mg.id_motivo_gasto
                LEFT JOIN prv_cat_proveedores pr ON g.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_absorcion_danios ad ON g.id_absorcion = ad.id_absorcion
                LEFT JOIN persona u_reg ON g.created_by = u_reg.idpersona
                LEFT JOIN persona u_apr ON g.aprobado_by = u_apr.idpersona
                WHERE g.id_gasto = ? AND g.deleted_at IS NULL";

        $gasto = $this->select($sql, [$idGasto]);
        if (empty($gasto)) {
            return null;
        }

        // Reparto por VIN
        $sqlReparto = "SELECT gv.*, ev.posicion_acomodo, ev.costo_unidad AS costo_planeado_unidad 
                       FROM lgs_det_gastos_adicionales_vins gv
                       LEFT JOIN lgs_envios_vins ev ON gv.id_envio_vin = ev.id
                       WHERE gv.id_gasto = ?
                       ORDER BY gv.id ASC";
        $gasto['reparto_vins'] = $this->select_all($sqlReparto, [$idGasto]) ?: [];

        // Documentos soporte
        $sqlDocs = "SELECT gd.*, CONCAT(p.nombres, ' ', p.apellidos) AS subido_por 
                    FROM lgs_det_gastos_adicionales_docs gd
                    LEFT JOIN persona p ON gd.created_by = p.idpersona
                    WHERE gd.id_gasto = ?
                    ORDER BY gd.id_documento DESC";
        $gasto['documentos'] = $this->select_all($sqlDocs, [$idGasto]) ?: [];

        // Logs
        $sqlLogs = "SELECT lg.*, CONCAT(p.nombres, ' ', p.apellidos) AS usuario_nombre 
                    FROM log_lgs_gastos_adicionales lg
                    LEFT JOIN persona p ON lg.id_usuario = p.idpersona
                    WHERE lg.id_gasto = ?
                    ORDER BY lg.id ASC";
        $gasto['logs'] = $this->select_all($sqlLogs, [$idGasto]) ?: [];

        return $gasto;
    }

    /**
     * Inserta la cabecera del gasto adicional
     */
    public function insertGasto(PDO $db, array $data): int
    {
        $sql = "INSERT INTO lgs_tra_gastos_adicionales (
                    folio, id_envio, id_incidencia, id_tipo_gasto, id_motivo_gasto,
                    alcance_aplicado, id_proveedor, responsable, naturaleza, documento,
                    id_absorcion, justificacion_independiente, km_aprobados, km_adicionales,
                    tipo_servicio_original, tipo_servicio_nuevo, cantidad, precio_unitario,
                    tarifa_aplicada, monto_calculado, monto_final, justificacion_ajuste,
                    moneda, fecha_gasto, descripcion, id_estado, created_by
                ) VALUES (
                    :folio, :id_envio, :id_incidencia, :id_tipo_gasto, :id_motivo_gasto,
                    :alcance_aplicado, :id_proveedor, :responsable, :naturaleza, :documento,
                    :id_absorcion, :justificacion_independiente, :km_aprobados, :km_adicionales,
                    :tipo_servicio_original, :tipo_servicio_nuevo, :cantidad, :precio_unitario,
                    :tarifa_aplicada, :monto_calculado, :monto_final, :justificacion_ajuste,
                    :moneda, :fecha_gasto, :descripcion, :id_estado, :created_by
                )";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':folio'                       => $data['folio'],
            ':id_envio'                    => $data['id_envio'],
            ':id_incidencia'               => $data['id_incidencia'] ?? null,
            ':id_tipo_gasto'               => $data['id_tipo_gasto'],
            ':id_motivo_gasto'             => $data['id_motivo_gasto'] ?? null,
            ':alcance_aplicado'            => $data['alcance_aplicado'] ?? 'ENVIO',
            ':id_proveedor'                => $data['id_proveedor'] ?? null,
            ':responsable'                 => $data['responsable'] ?? 'INTERNO',
            ':naturaleza'                  => $data['naturaleza'] ?? 'CARGO',
            ':documento'                   => $data['documento'] ?? 'FACTURA_PROVEEDOR',
            ':id_absorcion'                => $data['id_absorcion'] ?? null,
            ':justificacion_independiente' => $data['justificacion_independiente'] ?? null,
            ':km_aprobados'                => $data['km_aprobados'] ?? null,
            ':km_adicionales'              => $data['km_adicionales'] ?? null,
            ':tipo_servicio_original'      => $data['tipo_servicio_original'] ?? null,
            ':tipo_servicio_nuevo'         => $data['tipo_servicio_nuevo'] ?? null,
            ':cantidad'                    => $data['cantidad'] ?? null,
            ':precio_unitario'             => $data['precio_unitario'] ?? null,
            ':tarifa_aplicada'             => $data['tarifa_aplicada'] ?? null,
            ':monto_calculado'             => $data['monto_calculado'] ?? 0.00,
            ':monto_final'                 => $data['monto_final'] ?? 0.00,
            ':justificacion_ajuste'        => $data['justificacion_ajuste'] ?? null,
            ':moneda'                      => $data['moneda'] ?? 'MXN',
            ':fecha_gasto'                 => $data['fecha_gasto'],
            ':descripcion'                 => $data['descripcion'],
            ':id_estado'                   => $data['id_estado'] ?? 1,
            ':created_by'                  => $data['created_by'],
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Inserta una partida de reparto por VIN
     */
    public function insertRepartoVin(PDO $db, array $data): int
    {
        $sql = "INSERT INTO lgs_det_gastos_adicionales_vins (
                    id_gasto, id_envio_vin, id_unidad, vin, porcentaje, monto
                ) VALUES (
                    :id_gasto, :id_envio_vin, :id_unidad, :vin, :porcentaje, :monto
                )";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_gasto'      => $data['id_gasto'],
            ':id_envio_vin'  => $data['id_envio_vin'],
            ':id_unidad'     => $data['id_unidad'],
            ':vin'           => $data['vin'],
            ':porcentaje'    => $data['porcentaje'],
            ':monto'         => $data['monto'],
        ]);
        return (int)$db->lastInsertId();
    }

    public function deleteRepartoVins(PDO $db, int $idGasto): void
    {
        $stmt = $db->prepare("DELETE FROM lgs_det_gastos_adicionales_vins WHERE id_gasto = ?");
        $stmt->execute([$idGasto]);
    }

    /**
     * Inserta documento financiero
     */
    public function insertDocumentoGasto(PDO $db, array $data): int
    {
        $sql = "INSERT INTO lgs_det_gastos_adicionales_docs (
                    id_gasto, tipo, ruta_archivo, nombre_original, mime, tamano_bytes, created_by
                ) VALUES (
                    :id_gasto, :tipo, :ruta_archivo, :nombre_original, :mime, :tamano_bytes, :created_by
                )";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_gasto'        => $data['id_gasto'],
            ':tipo'            => $data['tipo'] ?? 'FACTURA_PDF',
            ':ruta_archivo'    => $data['ruta_archivo'],
            ':nombre_original' => $data['nombre_original'],
            ':mime'            => $data['mime'],
            ':tamano_bytes'    => $data['tamano_bytes'],
            ':created_by'      => $data['created_by'],
        ]);
        return (int)$db->lastInsertId();
    }

    public function deleteDocumentoGasto(PDO $db, int $idDoc): ?string
    {
        $stmt = $db->prepare("SELECT ruta_archivo FROM lgs_det_gastos_adicionales_docs WHERE id_documento = ?");
        $stmt->execute([$idDoc]);
        $ruta = $stmt->fetchColumn();

        if ($ruta) {
            $stmtDel = $db->prepare("DELETE FROM lgs_det_gastos_adicionales_docs WHERE id_documento = ?");
            $stmtDel->execute([$idDoc]);
            return (string)$ruta;
        }
        return null;
    }

    public function insertLogGasto(PDO $db, int $idGasto, ?int $estadoAnt, int $estadoNuevo, ?string $comentario, int $userId): void
    {
        $sql = "INSERT INTO log_lgs_gastos_adicionales (id_gasto, estado_anterior, estado_nuevo, comentario, id_usuario) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idGasto, $estadoAnt, $estadoNuevo, $comentario, $userId]);
    }

    /**
     * Actualiza el estado del gasto
     */
    public function updateEstadoGasto(PDO $db, int $idGasto, int $nuevoEstado, ?string $comentario, int $userId): void
    {
        $stmtActual = $db->prepare("SELECT id_estado, aprobado_by, aprobado_at FROM lgs_tra_gastos_adicionales WHERE id_gasto = ?");
        $stmtActual->execute([$idGasto]);
        $actual = $stmtActual->fetch(PDO::FETCH_ASSOC);

        $aprobadoBy = $actual['aprobado_by'] ?? null;
        $aprobadoAt = $actual['aprobado_at'] ?? null;

        if ($nuevoEstado === 3) { // Aprobado
            $aprobadoBy = $userId;
            $aprobadoAt = date('Y-m-d H:i:s');
        }

        $sql = "UPDATE lgs_tra_gastos_adicionales SET 
                    id_estado = ?, 
                    aprobado_by = ?, 
                    aprobado_at = ?, 
                    updated_by = ?, 
                    updated_at = NOW() 
                WHERE id_gasto = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$nuevoEstado, $aprobadoBy, $aprobadoAt, $userId, $idGasto]);

        $this->insertLogGasto($db, $idGasto, intval($actual['id_estado'] ?? 1), $nuevoEstado, $comentario, $userId);
    }

    /**
     * Registra el documento aparte (factura / nota de cargo) y pasa el gasto a Documentado (5)
     */
    public function documentarGasto(PDO $db, int $idGasto, array $docData, int $userId): void
    {
        $sql = "UPDATE lgs_tra_gastos_adicionales SET 
                    doc_tipo = :doc_tipo,
                    doc_folio = :doc_folio,
                    doc_uuid = :doc_uuid,
                    doc_fecha = :doc_fecha,
                    id_estado = 5,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id_gasto = :id_gasto";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':doc_tipo'    => $docData['doc_tipo'] ?? 'FACTURA',
            ':doc_folio'   => $docData['doc_folio'] ?? null,
            ':doc_uuid'    => $docData['doc_uuid'] ?? null,
            ':doc_fecha'   => $docData['doc_fecha'] ?? date('Y-m-d'),
            ':updated_by'  => $userId,
            ':id_gasto'    => $idGasto,
        ]);

        $this->insertLogGasto($db, $idGasto, 3, 5, "Gasto documentado con comprobante aparte ({$docData['doc_tipo']} Folio: {$docData['doc_folio']})", $userId);
    }

    /**
     * Verifica si el usuario actual tiene permisos de aprobación (activo en lgs_aprobadores)
     */
    public function esUsuarioAprobador(int $userId): bool
    {
        try {
            $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM lgs_aprobadores WHERE id_usuario = ? AND activo = 1");
            $stmt->execute([$userId]);
            $count = (int)$stmt->fetchColumn();
            return $count > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Obtiene el resumen de costo real por VIN, incidencias y gastos de un envío específico
     */
    public function getAuditoriaCostoRealEnvio(int $idEnvio): array
    {
        $sqlVins = "SELECT 
                        cr.id_envio_vin,
                        cr.id_envio,
                        cr.id_unidad,
                        cr.vin,
                        cr.costo_planeado_unidad,
                        cr.total_cargos_adicionales,
                        cr.total_deducciones,
                        cr.costo_real_unidad,
                        cr.costo_neto_unidad,
                        cr.total_gastos_activos
                    FROM vw_lgs_costo_real_vin cr
                    WHERE cr.id_envio = ?
                    ORDER BY cr.id_envio_vin ASC";
        $vins = $this->select_all($sqlVins, [$idEnvio]) ?: [];

        $sqlGastos = "SELECT 
                        g.id_gasto,
                        g.folio,
                        g.id_incidencia,
                        inc.folio AS folio_incidencia,
                        tg.nombre AS tipo_gasto,
                        g.monto_final,
                        g.naturaleza,
                        g.responsable,
                        g.id_estado,
                        g.fecha_gasto,
                        g.doc_tipo,
                        g.doc_folio,
                        (SELECT COUNT(*) FROM lgs_det_gastos_adicionales_vins gv WHERE gv.id_gasto = g.id_gasto) AS vins_afectados
                      FROM lgs_tra_gastos_adicionales g
                      LEFT JOIN lgs_cat_tipos_gasto_adicional tg ON g.id_tipo_gasto = tg.id_tipo_gasto
                      LEFT JOIN lgs_tra_incidencias inc ON g.id_incidencia = inc.id_incidencia
                      WHERE g.id_envio = ? AND g.deleted_at IS NULL
                      ORDER BY g.id_gasto DESC";
        $gastos = $this->select_all($sqlGastos, [$idEnvio]) ?: [];

        $sqlIncidencias = "SELECT 
                            i.id_incidencia,
                            i.folio,
                            ti.nombre AS tipo_incidencia,
                            i.id_estado,
                            i.fecha_incidente,
                            i.es_post_entrega,
                            (SELECT COUNT(*) FROM lgs_det_incidencias_vins iv WHERE iv.id_incidencia = i.id_incidencia) AS vins_afectados,
                            ad.nombre AS absorcion
                           FROM lgs_tra_incidencias i
                           LEFT JOIN lgs_cat_tipos_incidencia ti ON i.id_tipo_incidencia = ti.id_tipo_incidencia
                           LEFT JOIN lgs_cat_absorcion_danios ad ON i.id_absorcion = ad.id_absorcion
                           WHERE i.id_envio = ? AND i.deleted_at IS NULL
                           ORDER BY i.id_incidencia DESC";
        $incidencias = $this->select_all($sqlIncidencias, [$idEnvio]) ?: [];

        $totalCostoPlaneado = 0;
        $totalCargos = 0;
        $totalDeducciones = 0;
        $totalCostoReal = 0;

        foreach ($vins as $v) {
            $totalCostoPlaneado += floatval($v['costo_planeado_unidad']);
            $totalCargos += floatval($v['total_cargos_adicionales']);
            $totalDeducciones += floatval($v['total_deducciones']);
            $totalCostoReal += floatval($v['costo_real_unidad']);
        }

        return [
            'id_envio' => $idEnvio,
            'totales' => [
                'costo_planeado' => $totalCostoPlaneado,
                'total_cargos' => $totalCargos,
                'total_deducciones' => $totalDeducciones,
                'costo_real' => $totalCostoReal,
                'vins_count' => count($vins),
                'gastos_count' => count($gastos),
                'incidencias_count' => count($incidencias)
            ],
            'vins' => $vins,
            'gastos' => $gastos,
            'incidencias' => $incidencias
        ];
    }
}

