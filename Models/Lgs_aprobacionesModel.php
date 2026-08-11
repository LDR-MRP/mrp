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
     * Obtiene el listado de planeaciones pendientes de revisión o ya revisadas
     * Sólo trae estados 2 (Enviada/Pendiente), 3 (Regresada) y 5 (Aprobada)
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
                    p.obs_operador,
                    u.nombres AS creador,
                    (SELECT COUNT(*) FROM lgs_planeaciones_envios WHERE id_planeacion = p.id_planeacion) AS total_rutas
                FROM lgs_planeaciones p
                LEFT JOIN persona u ON p.created_by = u.idpersona
                WHERE p.id_estado IN (2, 3, 5)
                ORDER BY p.id_estado ASC, p.id_planeacion DESC";
        
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
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins
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
}
