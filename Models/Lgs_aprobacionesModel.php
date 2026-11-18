<?php

class Lgs_aprobacionesModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_aprobaciones'; // dummy, ya que las vistas manejan lgs_planeaciones y lgs_envios

    public function getTableName(): string {
        return $this->table;
    }

    public function getConexion(): PDO {
        return $this->conexion;
    }

    /**
     * Obtiene el listado de planeaciones pendientes de revisión, en solicitud de reapertura o ya revisadas
     */
    public function getPlaneacionesAprobacion(): array
    {
        $sql = "SELECT 
                    p.id_planeacion,
                    p.folio,
                    p.descripcion,
                    p.km_total,
                    p.costo_total,
                    p.id_estado,
                    p.created_at,
                    COALESCE(p.obs_operador, '') AS obs_operador,
                    COALESCE(p.obs_aprobador, '') AS obs_aprobador,
                    u.nombres AS creador,
                    (SELECT COUNT(*) FROM lgs_planeaciones_envios WHERE id_planeacion = p.id_planeacion) AS total_rutas
                FROM lgs_planeaciones p
                LEFT JOIN usuarios u ON p.created_by = u.idusuario
                WHERE p.id_estado IN (2, 3, 5, 6) OR p.obs_aprobador LIKE '%[Reapertura%'
                ORDER BY FIELD(p.id_estado, 6, 2, 5, 3, 1), p.id_planeacion DESC";
        
        return $this->select_all($sql);
    }

    /**
     * Obtiene los envíos agrupados dentro de una planeación específica
     */
    public function getEnviosPorPlaneacion(int $idPlaneacion): array
    {
        $sql = "SELECT 
                    e.id_envio, 
                    e.folio,
                    e.costo_total,
                    e.km_total,
                    o.nombre AS origen,
                    pr.razon_social AS trasladista,
                    tt.nombre AS tipo_traslado,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins,
                    (SELECT COUNT(*) FROM lgs_tra_incidencias inc WHERE inc.id_envio = e.id_envio AND inc.deleted_at IS NULL) AS total_incidencias,
                    (SELECT COALESCE(SUM(g.monto_final), 0) FROM lgs_tra_gastos_adicionales g WHERE g.id_envio = e.id_envio AND g.deleted_at IS NULL AND g.id_estado IN (3, 5)) AS total_gastos
                FROM lgs_planeaciones_envios pe
                INNER JOIN lgs_envios e ON pe.id_envio = e.id_envio
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                WHERE pe.id_planeacion = :id_planeacion";
        
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->execute(['id_planeacion' => $idPlaneacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza el estado de la planeación y guarda las observaciones del aprobador
     */
    public function updateEstadoPlaneacion(PDO $db, int $idPlaneacion, int $idEstado, string $observaciones, int $userId): void
    {
        $sql = "UPDATE lgs_planeaciones 
                SET id_estado = :estado, 
                    obs_aprobador = :obs, 
                    aprobado_by = :user, 
                    aprobado_at = NOW() 
                WHERE id_planeacion = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'estado' => $idEstado,
            'obs'    => $observaciones,
            'user'   => $userId,
            'id'     => $idPlaneacion
        ]);
    }

    /**
     * Actualiza el estado de TODOS los envíos de una planeación masivamente
     */
    public function updateEstadoEnviosMasivo(PDO $db, int $idPlaneacion, int $nuevoEstadoEnvio): void
    {
        $sql = "UPDATE lgs_envios e
                INNER JOIN lgs_planeaciones_envios pe ON e.id_envio = pe.id_envio
                SET e.id_estado = :estado
                WHERE pe.id_planeacion = :id_planeacion";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'estado' => $nuevoEstadoEnvio,
            'id_planeacion' => $idPlaneacion
        ]);
    }

    /**
     * Obtiene las solicitudes de autorización excepcional (Pre-envíos PE- / Envíos sin factura/pago/aut. movimiento)
     */
    public function getSolicitudesPreEnvioAprobacion(): array
    {
        if (file_exists('Models/Lgs_enviosModel.php')) {
            require_once 'Models/Lgs_enviosModel.php';
            (new Lgs_enviosModel())->ensureSolicitudesPreEnvioTable($this->getConexion());
        }
        $sql = "SELECT
                    s.id_solicitud,
                    s.folio_solicitud,
                    s.id_envio,
                    s.folio_preenvio,
                    s.folio_envio_generado,
                    s.estatus,
                    s.motivo_solicitud,
                    s.total_vins,
                    s.vins_con_observacion,
                    s.solicitado_by,
                    s.solicitado_at,
                    s.resuelto_by,
                    s.resuelto_at,
                    s.obs_aprobador,
                    e.folio AS folio_actual_envio,
                    COALESCE(e.es_preenvio, 0) AS es_preenvio_actual,
                    e.costo_total,
                    e.km_total,
                    e.id_estado AS estado_envio,
                    pr.razon_social AS trasladista,
                    tt.nombre AS tipo_traslado,
                    COALESCE(
                        (SELECT COALESCE(u0.nombre, n0.destino_nombre_libre) FROM lgs_envios_nodos n0 LEFT JOIN lgs_cat_ubicaciones u0 ON n0.id_ubicacion = u0.id_ubicacion WHERE n0.id_envio = e.id_envio AND n0.orden = 0 LIMIT 1),
                        o.nombre,
                        'Origen'
                    ) AS origen,
                    COALESCE(
                        (SELECT COALESCE(uL.nombre, nL.destino_nombre_libre) FROM lgs_envios_nodos nL LEFT JOIN lgs_cat_ubicaciones uL ON nL.id_ubicacion = uL.id_ubicacion WHERE nL.id_envio = e.id_envio ORDER BY nL.orden DESC LIMIT 1),
                        e.destino_nombre_libre,
                        'Destino'
                    ) AS destino,
                    TRIM(CONCAT(COALESCE(us.nombres, ''), ' ', COALESCE(us.apellidos, ''))) AS solicitante_nombre,
                    TRIM(CONCAT(COALESCE(ur.nombres, ''), ' ', COALESCE(ur.apellidos, ''))) AS aprobador_nombre
                FROM lgs_solicitudes_preenvio_envio s
                LEFT JOIN lgs_envios e ON e.id_envio = s.id_envio
                LEFT JOIN prv_cat_proveedores pr ON pr.id_proveedor = e.id_proveedor
                LEFT JOIN lgs_cat_tipo_traslado tt ON tt.id_tipo_traslado = e.id_tipo_traslado
                LEFT JOIN lgs_cat_origenes o ON o.id_origen = e.id_origen
                LEFT JOIN usuarios us ON us.idusuario = s.solicitado_by
                LEFT JOIN usuarios ur ON ur.idusuario = s.resuelto_by
                ORDER BY FIELD(s.estatus, 'PENDIENTE', 'RECHAZADA', 'APROBADA', 'CANCELADA'), s.id_solicitud DESC";
        return $this->select_all($sql) ?: [];
    }

    /**
     * Obtiene el detalle completo de una solicitud excepcional incluyendo ruta y VINs observados
     */
    public function getDetalleSolicitudPreEnvio(int $idSolicitud): array
    {
        if (file_exists('Models/Lgs_enviosModel.php')) {
            require_once 'Models/Lgs_enviosModel.php';
        }
        $envModel = new Lgs_enviosModel();
        $envModel->ensureSolicitudesPreEnvioTable($this->getConexion());

        $sol = $this->select("
            SELECT s.*,
                   TRIM(CONCAT(COALESCE(us.nombres, ''), ' ', COALESCE(us.apellidos, ''))) AS solicitante_nombre,
                   us.email_user AS solicitante_email,
                   TRIM(CONCAT(COALESCE(ur.nombres, ''), ' ', COALESCE(ur.apellidos, ''))) AS aprobador_nombre
            FROM lgs_solicitudes_preenvio_envio s
            LEFT JOIN usuarios us ON us.idusuario = s.solicitado_by
            LEFT JOIN usuarios ur ON ur.idusuario = s.resuelto_by
            WHERE s.id_solicitud = ?
            LIMIT 1
        ", [$idSolicitud]);
        if (empty($sol)) {
            throw new Exception("La solicitud de autorización no existe.");
        }

        $idEnvio = intval($sol['id_envio']);
        $cab = $envModel->getEnvioCabecera($idEnvio);
        $nodos = $envModel->getNodosEnvio($idEnvio);
        $vinsSnapshot = json_decode((string)($sol['detalle_observaciones_json'] ?? '[]'), true);
        if (!is_array($vinsSnapshot)) {
            $vinsSnapshot = [];
        }

        // Si sigue pendiente, refrescar el estado actual de Factura/Pago/Aut. Movimiento por si ya regularizaron algo
        $vinsLive = $vinsSnapshot;
        if (($sol['estatus'] ?? '') === 'PENDIENTE' && !empty($cab)) {
            try {
                $val = $envModel->validarRequisitosConversionEnvio($idEnvio);
                if (!empty($val['vins_detalle'])) {
                    $vinsLive = $val['vins_detalle'];
                }
            } catch (Throwable $e) {
                // Mantener snapshot si hubo error parcial de lectura
            }
        }

        return [
            'solicitud' => $sol,
            'envio'     => $cab,
            'nodos'     => $nodos,
            'vins'      => $vinsLive,
        ];
    }
}
