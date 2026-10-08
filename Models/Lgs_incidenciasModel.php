<?php

class Lgs_incidenciasModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_tra_incidencias';

    public function getTableName(): string
    {
        return $this->table;
    }

    public function getConexion(): PDO
    {
        return $this->conexion;
    }

    /**
     * Genera un nuevo folio transaccional único: IN-000001
     */
    public function generarFolioTransaccional(PDO $db): string
    {
        $sql = "SELECT folio FROM lgs_tra_incidencias ORDER BY id_incidencia DESC LIMIT 1 FOR UPDATE";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $ultimo = $stmt->fetchColumn();

        if (!$ultimo) {
            return 'IN-000001';
        }

        $num = intval(substr($ultimo, 3)) + 1;
        return 'IN-' . str_pad((string)$num, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Catálogos para formularios y filtros
     */
    public function getCatalogosFormulario(): array
    {
        $tipos = $this->select_all("SELECT id_tipo_incidencia, clave, nombre, requiere_dictamen, requiere_evidencia, id_tipo_gasto_sugerido 
                                    FROM lgs_cat_tipos_incidencia WHERE activo = 1 ORDER BY id_tipo_incidencia ASC") ?: [];

        $absorciones = $this->select_all("SELECT id_absorcion, clave, nombre, naturaleza, documento, genera_gasto 
                                          FROM lgs_cat_absorcion_danios WHERE activo = 1 ORDER BY id_absorcion ASC") ?: [];

        $proveedores = $this->select_all("SELECT p.id_proveedor AS id, p.razon_social AS nombre 
                                          FROM prv_cat_proveedores p WHERE p.deleted_at IS NULL ORDER BY p.razon_social ASC") ?: [];

        return [
            'tipos_incidencia' => $tipos,
            'absorciones'      => $absorciones,
            'proveedores'      => $proveedores,
        ];
    }

    /**
     * Obtiene los envíos elegibles (Estados 3: Aprobado, 6: Ejecutado, 7: Entregado)
     * Soporta búsqueda libre y filtros avanzados (planeación, vin, proveedor, fechas)
     */
    public function getEnviosElegibles(string $query = '', array $filtros = []): array
    {
        $whereQuery = "";
        $params = [];

        // Filtro por Estado (3: Aprobado, 6: En Tránsito, 7: Entregado)
        if (!empty($filtros['id_estado'])) {
            $whereQuery .= " AND e.id_estado = ?";
            $params[] = intval($filtros['id_estado']);
        } else {
            $whereQuery .= " AND e.id_estado IN (3, 6, 7)";
        }

        // Filtro por Proveedor / Trasladista
        if (!empty($filtros['id_proveedor'])) {
            $whereQuery .= " AND e.id_proveedor = ?";
            $params[] = intval($filtros['id_proveedor']);
        }

        // Filtro específico por Folio de Envío
        if (!empty($filtros['folio_envio'])) {
            $whereQuery .= " AND e.folio LIKE ?";
            $params[] = '%' . trim($filtros['folio_envio']) . '%';
        }

        // Filtro específico por Planeación (Folio o ID)
        if (!empty($filtros['folio_planeacion'])) {
            $whereQuery .= " AND (plan.folio LIKE ? OR plan.id_planeacion = ?)";
            $params[] = '%' . trim($filtros['folio_planeacion']) . '%';
            $params[] = intval($filtros['folio_planeacion']);
        }

        // Filtro específico por VIN o número de serie
        if (!empty($filtros['vin'])) {
            $whereQuery .= " AND (u.vin LIKE ? OR ut.clave LIKE ? OR u.num_serie LIKE ?)";
            $params[] = '%' . trim($filtros['vin']) . '%';
            $params[] = '%' . trim($filtros['vin']) . '%';
            $params[] = '%' . trim($filtros['vin']) . '%';
        }

        // Filtro por fechas
        if (!empty($filtros['fecha_desde'])) {
            $whereQuery .= " AND (DATE(e.fecha_tentativa_envio) >= ? OR DATE(e.fecha_salida_real) >= ?)";
            $params[] = $filtros['fecha_desde'];
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $whereQuery .= " AND (DATE(e.fecha_tentativa_envio) <= ? OR DATE(e.fecha_salida_real) <= ?)";
            $params[] = $filtros['fecha_hasta'];
            $params[] = $filtros['fecha_hasta'];
        }

        // Búsqueda libre multi-campo
        if (!empty(trim($query))) {
            $whereQuery .= " AND (e.folio LIKE ? 
                                 OR plan.folio LIKE ? 
                                 OR u.vin LIKE ? 
                                 OR ut.clave LIKE ? 
                                 OR pr.razon_social LIKE ? 
                                 OR o.nombre LIKE ? 
                                 OR d.nombre LIKE ? 
                                 OR c.razon_social LIKE ? 
                                 OR e.destino_nombre_libre LIKE ?)";
            $term = '%' . trim($query) . '%';
            for ($i = 0; $i < 9; $i++) {
                $params[] = $term;
            }
        }

        $sql = "SELECT DISTINCT
                    e.id_envio,
                    e.folio,
                    e.id_estado,
                    e.km_total,
                    e.costo_total,
                    e.fecha_tentativa_envio,
                    e.fecha_salida_real,
                    e.fecha_llegada_real,
                    e.id_proveedor,
                    COALESCE(pr.razon_social, 'Sin Proveedor') AS trasladista,
                    COALESCE(plan.id_planeacion, 0) AS id_planeacion,
                    COALESCE(plan.folio, CONCAT('PLN-', LPAD(plan.id_planeacion, 5, '0')), 'Sin Planeación') AS folio_planeacion,
                    plan.descripcion AS planeacion_desc,
                    COALESCE(
                        (SELECT u0.nombre FROM lgs_envios_nodos n0 LEFT JOIN lgs_cat_ubicaciones u0 ON n0.id_ubicacion = u0.id_ubicacion WHERE n0.id_envio = e.id_envio AND n0.orden = 0 LIMIT 1),
                        o.nombre,
                        'Origen'
                    ) AS origen,
                    COALESCE(
                        (SELECT COALESCE(uLast.nombre, nLast.destino_nombre_libre) FROM lgs_envios_nodos nLast LEFT JOIN lgs_cat_ubicaciones uLast ON nLast.id_ubicacion = uLast.id_ubicacion WHERE nLast.id_envio = e.id_envio ORDER BY nLast.orden DESC LIMIT 1),
                        c.razon_social,
                        d.nombre,
                        e.destino_nombre_libre,
                        'Sin Destino'
                    ) AS destino,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins,
                    (SELECT GROUP_CONCAT(DISTINCT COALESCE(u_sub.vin, ut_sub.clave) SEPARATOR ', ')
                     FROM lgs_envios_vins ev_sub
                     LEFT JOIN lgs_unidades_envios u_sub ON ev_sub.id_unidad = u_sub.id_unidad
                     LEFT JOIN mrp_unidades_terminadas ut_sub ON ev_sub.id_unidad = ut_sub.idunidad
                     WHERE ev_sub.id_envio = e.id_envio
                    ) AS vins_resumen
                FROM lgs_envios e
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_planeaciones_envios pe ON pe.id_envio = e.id_envio
                LEFT JOIN lgs_planeaciones plan ON pe.id_planeacion = plan.id_planeacion
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN cli_clientes c ON e.id_destino = c.idcliente
                LEFT JOIN lgs_cat_destinos d ON e.id_destino = d.id_destino
                LEFT JOIN lgs_envios_vins ev ON ev.id_envio = e.id_envio
                LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                WHERE e.deleted_at IS NULL {$whereQuery}
                ORDER BY e.id_envio DESC
                LIMIT 250";

        return $this->select_all($sql, $params) ?: [];
    }

    /**
     * Obtiene los VINs de un envío con información de entrega para cálculo de 'a bordo'
     */
    public function getVinsEnvio(int $idEnvio, ?string $fechaIncidente = null): array
    {
        $sql = "SELECT 
                    ev.id AS id_envio_vin,
                    ev.id_envio,
                    ev.id_unidad,
                    COALESCE(u.vin, ut.clave, CONCAT('VIN-', ev.id_unidad)) AS vin,
                    COALESCE(u.num_serie, ut.num_unidad, 'S/N') AS num_serie,
                    COALESCE(u.modelo, 'Unidad') AS modelo,
                    ev.posicion_acomodo,
                    ev.costo_unidad,
                    ev.fecha_entrega_real,
                    ev.recibe_nombre,
                    COALESCE(dest.nombre, ev.destino_nombre_libre, 'Destino') AS destino_vin,
                    CASE 
                        WHEN ev.fecha_entrega_real IS NOT NULL 
                             AND (? IS NULL OR ev.fecha_entrega_real <= ?) THEN 'ENTREGADO'
                        ELSE 'A_BORDO'
                    END AS estado_al_incidente
                FROM lgs_envios_vins ev
                LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                LEFT JOIN lgs_cat_ubicaciones dest ON ev.id_destino = dest.id_ubicacion
                WHERE ev.id_envio = ?
                ORDER BY ev.posicion_acomodo ASC, ev.id ASC";

        return $this->select_all($sql, [$fechaIncidente, $fechaIncidente, $idEnvio]) ?: [];
    }

    /**
     * Lista de incidencias para DataTable con filtros
     */
    public function getIncidenciasDataTable(array $filtros = []): array
    {
        $where = "WHERE i.deleted_at IS NULL";
        $params = [];

        if (!empty($filtros['id_estado'])) {
            $where .= " AND i.id_estado = ?";
            $params[] = intval($filtros['id_estado']);
        }

        if (!empty($filtros['id_tipo_incidencia'])) {
            $where .= " AND i.id_tipo_incidencia = ?";
            $params[] = intval($filtros['id_tipo_incidencia']);
        }

        if (!empty($filtros['id_proveedor'])) {
            $where .= " AND i.id_proveedor = ?";
            $params[] = intval($filtros['id_proveedor']);
        }

        if (isset($filtros['es_post_entrega']) && $filtros['es_post_entrega'] !== '') {
            $where .= " AND i.es_post_entrega = ?";
            $params[] = intval($filtros['es_post_entrega']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $where .= " AND DATE(i.fecha_incidente) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where .= " AND DATE(i.fecha_incidente) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $sql = "SELECT 
                    i.id_incidencia,
                    i.folio,
                    i.id_envio,
                    e.folio AS folio_envio,
                    e.id_estado AS estado_envio,
                    ti.nombre AS tipo_incidencia,
                    ti.clave AS clave_tipo_incidencia,
                    ti.requiere_dictamen,
                    COALESCE(pr.razon_social, 'Sin Proveedor') AS trasladista,
                    i.origen,
                    i.severidad,
                    i.fecha_incidente,
                    i.ubicacion_texto,
                    i.descripcion,
                    i.es_post_entrega,
                    i.id_absorcion,
                    ad.nombre AS absorcion_nombre,
                    ad.clave AS absorcion_clave,
                    i.id_estado,
                    (SELECT COUNT(*) FROM lgs_det_incidencias_vins iv WHERE iv.id_incidencia = i.id_incidencia) AS total_vins_afectados,
                    (SELECT GROUP_CONCAT(iv.vin SEPARATOR ', ') FROM lgs_det_incidencias_vins iv WHERE iv.id_incidencia = i.id_incidencia) AS vins_afectados_list,
                    (SELECT COUNT(*) FROM lgs_det_incidencias_evidencias ie WHERE ie.id_incidencia = i.id_incidencia) AS total_evidencias,
                    (SELECT COUNT(*) FROM lgs_tra_gastos_adicionales g WHERE g.id_incidencia = i.id_incidencia AND g.deleted_at IS NULL) AS total_gastos_ligados,
                    (SELECT COALESCE(SUM(g.monto_final), 0.00) FROM lgs_tra_gastos_adicionales g WHERE g.id_incidencia = i.id_incidencia AND g.deleted_at IS NULL AND g.id_estado IN (1, 2, 3, 5)) AS monto_total_gastos,
                    i.created_at
                FROM lgs_tra_incidencias i
                INNER JOIN lgs_envios e ON i.id_envio = e.id_envio
                INNER JOIN lgs_cat_tipos_incidencia ti ON i.id_tipo_incidencia = ti.id_tipo_incidencia
                LEFT JOIN prv_cat_proveedores pr ON i.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_absorcion_danios ad ON i.id_absorcion = ad.id_absorcion
                {$where}
                ORDER BY i.id_incidencia DESC";

        return $this->select_all($sql, $params) ?: [];
    }

    /**
     * Obtiene el detalle completo de una incidencia
     */
    public function getIncidenciaDetalle(int $idIncidencia): ?array
    {
        $sql = "SELECT 
                    i.*,
                    e.folio AS folio_envio,
                    e.id_estado AS estado_envio,
                    ti.nombre AS tipo_incidencia,
                    ti.clave AS clave_tipo_incidencia,
                    ti.requiere_dictamen,
                    ti.requiere_evidencia,
                    ti.id_tipo_gasto_sugerido,
                    COALESCE(pr.razon_social, 'Sin Proveedor') AS trasladista,
                    ad.nombre AS absorcion_nombre,
                    ad.clave AS absorcion_clave,
                    ad.naturaleza AS absorcion_naturaleza,
                    ad.documento AS absorcion_documento,
                    ad.genera_gasto AS absorcion_genera_gasto,
                    CONCAT(u_reg.nombres, ' ', u_reg.apellidos) AS registrado_por,
                    CONCAT(u_dict.nombres, ' ', u_dict.apellidos) AS dictaminado_por
                FROM lgs_tra_incidencias i
                INNER JOIN lgs_envios e ON i.id_envio = e.id_envio
                INNER JOIN lgs_cat_tipos_incidencia ti ON i.id_tipo_incidencia = ti.id_tipo_incidencia
                LEFT JOIN prv_cat_proveedores pr ON i.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_absorcion_danios ad ON i.id_absorcion = ad.id_absorcion
                LEFT JOIN persona u_reg ON i.created_by = u_reg.idpersona
                LEFT JOIN persona u_dict ON i.dictaminado_by = u_dict.idpersona
                WHERE i.id_incidencia = ? AND i.deleted_at IS NULL";

        $inc = $this->select($sql, [$idIncidencia]);
        if (empty($inc)) {
            return null;
        }

        // VINs afectados
        $sqlVins = "SELECT iv.*, ev.costo_unidad, ev.posicion_acomodo 
                    FROM lgs_det_incidencias_vins iv
                    LEFT JOIN lgs_envios_vins ev ON iv.id_envio_vin = ev.id
                    WHERE iv.id_incidencia = ?
                    ORDER BY iv.id ASC";
        $inc['vins_afectados'] = $this->select_all($sqlVins, [$idIncidencia]) ?: [];

        // Evidencias
        $sqlEv = "SELECT ie.*, CONCAT(p.nombres, ' ', p.apellidos) AS subido_por 
                  FROM lgs_det_incidencias_evidencias ie
                  LEFT JOIN persona p ON ie.created_by = p.idpersona
                  WHERE ie.id_incidencia = ?
                  ORDER BY ie.id_evidencia DESC";
        $inc['evidencias'] = $this->select_all($sqlEv, [$idIncidencia]) ?: [];

        // Historial / Logs
        $sqlLogs = "SELECT l.*, CONCAT(p.nombres, ' ', p.apellidos) AS usuario_nombre 
                    FROM log_lgs_incidencias l
                    LEFT JOIN persona p ON l.id_usuario = p.idpersona
                    WHERE l.id_incidencia = ?
                    ORDER BY l.id ASC";
        $inc['logs'] = $this->select_all($sqlLogs, [$idIncidencia]) ?: [];

        // Gastos vinculados
        $sqlGastos = "SELECT g.id_gasto, g.folio, g.descripcion, g.monto_final, g.id_estado, g.naturaleza, g.documento,
                             tg.nombre AS tipo_gasto, g.fecha_gasto, g.created_at
                      FROM lgs_tra_gastos_adicionales g
                      INNER JOIN lgs_cat_tipos_gasto_adicional tg ON g.id_tipo_gasto = tg.id_tipo_gasto
                      WHERE g.id_incidencia = ? AND g.deleted_at IS NULL
                      ORDER BY g.id_gasto ASC";
        $inc['gastos_vinculados'] = $this->select_all($sqlGastos, [$idIncidencia]) ?: [];

        return $inc;
    }

    /**
     * Inserta la cabecera de la incidencia
     */
    public function insertIncidencia(PDO $db, array $data): int
    {
        $sql = "INSERT INTO lgs_tra_incidencias (
                    folio, id_envio, id_tipo_incidencia, id_proveedor, origen, severidad,
                    fecha_incidente, ubicacion_texto, descripcion, estado_envio_al_registrar,
                    es_post_entrega, id_absorcion, porcentaje_proveedor, dictamen_notas,
                    dictaminado_by, dictaminado_at, id_estado, created_by
                ) VALUES (
                    :folio, :id_envio, :id_tipo_incidencia, :id_proveedor, :origen, :severidad,
                    :fecha_incidente, :ubicacion_texto, :descripcion, :estado_envio_al_registrar,
                    :es_post_entrega, :id_absorcion, :porcentaje_proveedor, :dictamen_notas,
                    :dictaminado_by, :dictaminado_at, :id_estado, :created_by
                )";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':folio'                     => $data['folio'],
            ':id_envio'                  => $data['id_envio'],
            ':id_tipo_incidencia'        => $data['id_tipo_incidencia'],
            ':id_proveedor'              => $data['id_proveedor'] ?? null,
            ':origen'                    => $data['origen'] ?? 'OPERACION',
            ':severidad'                 => $data['severidad'] ?? 'MEDIA',
            ':fecha_incidente'           => $data['fecha_incidente'],
            ':ubicacion_texto'           => $data['ubicacion_texto'] ?? null,
            ':descripcion'               => $data['descripcion'],
            ':estado_envio_al_registrar' => $data['estado_envio_al_registrar'],
            ':es_post_entrega'           => $data['es_post_entrega'] ?? 0,
            ':id_absorcion'              => $data['id_absorcion'] ?? null,
            ':porcentaje_proveedor'      => $data['porcentaje_proveedor'] ?? null,
            ':dictamen_notas'            => $data['dictamen_notas'] ?? null,
            ':dictaminado_by'            => $data['dictaminado_by'] ?? null,
            ':dictaminado_at'            => $data['dictaminado_at'] ?? null,
            ':id_estado'                 => $data['id_estado'] ?? 1,
            ':created_by'                => $data['created_by'],
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Inserta un VIN afectado
     */
    public function insertVinIncidencia(PDO $db, array $data): int
    {
        $sql = "INSERT INTO lgs_det_incidencias_vins (
                    id_incidencia, id_envio_vin, id_unidad, vin, estado_vin_al_incidente
                ) VALUES (
                    :id_incidencia, :id_envio_vin, :id_unidad, :vin, :estado_vin_al_incidente
                )";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_incidencia'            => $data['id_incidencia'],
            ':id_envio_vin'             => $data['id_envio_vin'],
            ':id_unidad'                => $data['id_unidad'],
            ':vin'                      => $data['vin'],
            ':estado_vin_al_incidente'  => $data['estado_vin_al_incidente'] ?? 'A_BORDO',
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Elimina todos los VINs afectados de una incidencia (para edición)
     */
    public function deleteVinsIncidencia(PDO $db, int $idIncidencia): void
    {
        $stmt = $db->prepare("DELETE FROM lgs_det_incidencias_vins WHERE id_incidencia = ?");
        $stmt->execute([$idIncidencia]);
    }

    /**
     * Inserta una evidencia multimedia
     */
    public function insertEvidenciaIncidencia(PDO $db, array $data): int
    {
        $sql = "INSERT INTO lgs_det_incidencias_evidencias (
                    id_incidencia, id_envio_vin, tipo, ruta_archivo, nombre_original, mime, tamano_bytes, created_by
                ) VALUES (
                    :id_incidencia, :id_envio_vin, :tipo, :ruta_archivo, :nombre_original, :mime, :tamano_bytes, :created_by
                )";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_incidencia'   => $data['id_incidencia'],
            ':id_envio_vin'    => $data['id_envio_vin'] ?? null,
            ':tipo'            => $data['tipo'] ?? 'FOTO',
            ':ruta_archivo'    => $data['ruta_archivo'],
            ':nombre_original' => $data['nombre_original'],
            ':mime'            => $data['mime'],
            ':tamano_bytes'    => $data['tamano_bytes'],
            ':created_by'      => $data['created_by'],
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Registra un cambio en la bitácora de la incidencia
     */
    public function insertLogIncidencia(PDO $db, int $idIncidencia, ?int $estadoAnt, int $estadoNuevo, ?string $comentario, int $userId): void
    {
        $sql = "INSERT INTO log_lgs_incidencias (id_incidencia, estado_anterior, estado_nuevo, comentario, id_usuario) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idIncidencia, $estadoAnt, $estadoNuevo, $comentario, $userId]);
    }

    /**
     * Actualiza cabecera de la incidencia
     */
    public function updateIncidencia(PDO $db, int $idIncidencia, array $data): void
    {
        $sql = "UPDATE lgs_tra_incidencias SET 
                    id_tipo_incidencia = :id_tipo_incidencia,
                    origen = :origen,
                    severidad = :severidad,
                    fecha_incidente = :fecha_incidente,
                    ubicacion_texto = :ubicacion_texto,
                    descripcion = :descripcion,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id_incidencia = :id_incidencia";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_tipo_incidencia' => $data['id_tipo_incidencia'],
            ':origen'             => $data['origen'] ?? 'OPERACION',
            ':severidad'          => $data['severidad'] ?? 'MEDIA',
            ':fecha_incidente'    => $data['fecha_incidente'],
            ':ubicacion_texto'    => $data['ubicacion_texto'] ?? null,
            ':descripcion'        => $data['descripcion'],
            ':updated_by'         => $data['updated_by'],
            ':id_incidencia'      => $idIncidencia,
        ]);
    }

    /**
     * Actualiza el estado de la incidencia
     */
    public function updateEstadoIncidencia(PDO $db, int $idIncidencia, int $nuevoEstado, ?string $comentario, int $userId): void
    {
        $stmtActual = $db->prepare("SELECT id_estado FROM lgs_tra_incidencias WHERE id_incidencia = ?");
        $stmtActual->execute([$idIncidencia]);
        $estadoActual = $stmtActual->fetchColumn();

        $sql = "UPDATE lgs_tra_incidencias SET id_estado = ?, updated_by = ?, updated_at = NOW() WHERE id_incidencia = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$nuevoEstado, $userId, $idIncidencia]);

        $this->insertLogIncidencia($db, $idIncidencia, (int)$estadoActual, $nuevoEstado, $comentario, $userId);
    }

    /**
     * Dictamina la absorción de la incidencia
     */
    public function dictaminarIncidencia(PDO $db, int $idIncidencia, int $idAbsorcion, ?float $porcentajeProv, string $notas, int $userId): void
    {
        // Consultar clave de absorción para ver si es improcedente
        $stmtAbs = $db->prepare("SELECT clave, genera_gasto FROM lgs_cat_absorcion_danios WHERE id_absorcion = ?");
        $stmtAbs->execute([$idAbsorcion]);
        $abs = $stmtAbs->fetch(PDO::FETCH_ASSOC);

        $nuevoEstado = 3; // Dictaminada por defecto
        if ($abs && intval($abs['genera_gasto']) === 0) {
            $nuevoEstado = 5; // Resuelta sin costo (p.ej. improcedente)
        }

        $stmtActual = $db->prepare("SELECT id_estado FROM lgs_tra_incidencias WHERE id_incidencia = ?");
        $stmtActual->execute([$idIncidencia]);
        $estadoActual = $stmtActual->fetchColumn();

        $sql = "UPDATE lgs_tra_incidencias SET 
                    id_absorcion = ?,
                    porcentaje_proveedor = ?,
                    dictamen_notas = ?,
                    dictaminado_by = ?,
                    dictaminado_at = NOW(),
                    id_estado = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id_incidencia = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idAbsorcion, $porcentajeProv, $notas, $userId, $nuevoEstado, $userId, $idIncidencia]);

        $comentario = "Dictamen emitido: " . ($abs['clave'] ?? 'Absorción') . " - " . mb_substr($notas, 0, 100);
        $this->insertLogIncidencia($db, $idIncidencia, (int)$estadoActual, $nuevoEstado, $comentario, $userId);
    }

    /**
     * Elimina una evidencia por ID y devuelve su ruta para borrarla del disco
     */
    public function deleteEvidencia(PDO $db, int $idEvidencia): ?string
    {
        $stmt = $db->prepare("SELECT ruta_archivo FROM lgs_det_incidencias_evidencias WHERE id_evidencia = ?");
        $stmt->execute([$idEvidencia]);
        $ruta = $stmt->fetchColumn();

        if ($ruta) {
            $stmtDel = $db->prepare("DELETE FROM lgs_det_incidencias_evidencias WHERE id_evidencia = ?");
            $stmtDel->execute([$idEvidencia]);
            return (string)$ruta;
        }

        return null;
    }

    /**
     * Revisa si todos los gastos de una incidencia están en estado final (3, 4, 5, 0)
     * y si es así, actualiza automáticamente la incidencia a Cerrada (6)
     */
    public function verificarYCerrarIncidencia(PDO $db, int $idIncidencia, int $userId): void
    {
        $stmt = $db->prepare("SELECT COUNT(*) AS total, 
                                     SUM(CASE WHEN id_estado IN (4, 5, 0) THEN 1 ELSE 0 END) AS finalizados
                              FROM lgs_tra_gastos_adicionales 
                              WHERE id_incidencia = ? AND deleted_at IS NULL");
        $stmt->execute([$idIncidencia]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = intval($res['total'] ?? 0);
        $finalizados = intval($res['finalizados'] ?? 0);

        if ($total > 0 && $total === $finalizados) {
            $stmtActual = $db->prepare("SELECT id_estado FROM lgs_tra_incidencias WHERE id_incidencia = ?");
            $stmtActual->execute([$idIncidencia]);
            $estadoActual = intval($stmtActual->fetchColumn());

            if ($estadoActual !== 6) {
                $this->updateEstadoIncidencia($db, $idIncidencia, 6, "Cierre automático: todos los gastos asociados han concluido su proceso", $userId);
            }
        }
    }
}
