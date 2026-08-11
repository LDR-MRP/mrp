<?php

class Lgs_ejecucionModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_solicitudes_entrega';

    public function getTableName(): string {
        return $this->table;
    }

    public function getConexion(): PDO {
        return $this->conexion;
    }

    /**
     * Obtiene todos los envíos aprobados (Estado 3) o en tránsito (Estado 6) para la mesa de despacho
     */
    public function getEnviosParaEjecucion(): array
    {
        $sql = "SELECT 
                    e.id_envio,
                    e.folio,
                    e.fecha_salida_real,
                    e.id_estado,
                    e.km_total,
                    e.costo_total,
                    o.nombre AS origen,
                    pr.razon_social AS trasladista,
                    tt.nombre AS tipo_traslado,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins,
                    (SELECT COUNT(*) FROM lgs_solicitudes_entrega WHERE id_envio = e.id_envio AND confirmado = 1) AS vins_entregados
                FROM lgs_envios e
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                WHERE e.id_estado IN (3, 6) AND e.deleted_at IS NULL
                ORDER BY e.id_estado ASC, e.id_envio DESC";
        
        return $this->select_all($sql);
    }

    /**
     * Obtiene los VINs de un envío con su orden de acomodo y estatus de solicitud de entrega
     */
    public function getVinsAcomodoConSolicitud(int $idEnvio): array
    {
        $sql = "SELECT 
                    ev.id AS id_envio_vin,
                    ev.id_unidad,
                    ev.posicion_acomodo,
                    ev.id_madrina,
                    ev.id_chofer,
                    u.vin,
                    u.modelo,
                    u.color,
                    se.id_solicitud,
                    se.confirmado,
                    se.fecha_confirmacion,
                    se.confirmado_by
                FROM lgs_envios_vins ev
                INNER JOIN mrp_unidades_terminadas u ON ev.id_unidad = u.id_unidad
                LEFT JOIN lgs_solicitudes_entrega se ON ev.id_envio = se.id_envio AND ev.id_unidad = se.id_unidad
                WHERE ev.id_envio = :id_envio
                ORDER BY ev.posicion_acomodo ASC";
        
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->execute(['id_envio' => $idEnvio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registrar/Crear solicitudes de entrega para planta si no existen
     */
    public function crearSolicitudesEntrega(PDO $db, int $idEnvio): void
    {
        $sql = "INSERT INTO lgs_solicitudes_entrega (id_envio, id_unidad, orden_acomodo, confirmado)
                SELECT id_envio, id_unidad, posicion_acomodo, 0
                FROM lgs_envios_vins
                WHERE id_envio = :id_envio
                ON DUPLICATE KEY UPDATE orden_acomodo = VALUES(orden_acomodo)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['id_envio' => $idEnvio]);
    }

    /**
     * Registrar la fecha de salida real y evidencias multimedia del despacho
     */
    public function registrarDespachoEnvio(PDO $db, int $idEnvio, string $fechaSalida, ?string $evidenciasJson): void
    {
        $sql = "UPDATE lgs_envios 
                SET fecha_salida_real = :fecha_salida
                WHERE id_envio = :id_envio";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'fecha_salida' => $fechaSalida,
            'id_envio' => $idEnvio
        ]);

        if (!empty($evidenciasJson)) {
            $sqlEv = "INSERT INTO lgs_envios_evidencias (id_envio, tipo_evidencia, archivos_json) 
                      VALUES (:id_envio, 'salida', :json)
                      ON DUPLICATE KEY UPDATE archivos_json = VALUES(archivos_json)";
            $stmtEv = $db->prepare($sqlEv);
            $stmtEv->execute([
                'id_envio' => $idEnvio,
                'json' => $evidenciasJson
            ]);
        }
    }

    /**
     * Confirmar la entrega física de un VIN en planta al trasladista
     */
    public function confirmarEntregaVinPlanta(PDO $db, int $idEnvio, int $idUnidad, int $userId): void
    {
        $sql = "UPDATE lgs_solicitudes_entrega 
                SET confirmado = 1, fecha_confirmacion = NOW(), confirmado_by = :user
                WHERE id_envio = :id_envio AND id_unidad = :id_unidad";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'user' => $userId,
            'id_envio' => $idEnvio,
            'id_unidad' => $idUnidad
        ]);
    }

    /**
     * Verifica si todos los VINs de un envío ya fueron entregados en planta
     */
    public function checkTodosVinsEntregados(PDO $db, int $idEnvio): bool
    {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = :id1) AS total,
                    (SELECT COUNT(*) FROM lgs_solicitudes_entrega WHERE id_envio = :id2 AND confirmado = 1) AS confirmados";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id1' => $idEnvio, 'id2' => $idEnvio]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($res && (int)$res['total'] > 0 && (int)$res['total'] === (int)$res['confirmados']);
    }

    /**
     * Actualiza el estado del envío (Ej: a 6 = En Tránsito)
     */
    public function updateEstadoEnvio(PDO $db, int $idEnvio, int $idEstado): void
    {
        $sql = "UPDATE lgs_envios SET id_estado = :estado WHERE id_envio = :id_envio";
        $stmt = $db->prepare($sql);
        $stmt->execute(['estado' => $idEstado, 'id_envio' => $idEnvio]);
    }
}
