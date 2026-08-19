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
     * Obtiene todos los envíos aprobados (Estado 3), recolección (Estado 5) o en tránsito (Estado 6) para la mesa de despacho, filtrado por planta.
     */
    public function getEnviosParaEjecucion(?int $plantaId = null): array
    {
        // 1. Asegurar tabla lgs_solicitudes_entrega
        try {
            $this->crearTablaSolicitudesSiNoExiste();
        } catch (Throwable $ignored) {}

        try {
            $wherePlanta = "";
            $params = [];
            if ($plantaId !== null && $plantaId > 0) {
                $wherePlanta = " AND e.id_origen = :planta_id ";
                $params['planta_id'] = $plantaId;
            }

            $sql = "SELECT 
                        e.id_envio,
                        e.folio,
                        e.fecha_salida_real,
                        e.id_estado,
                        e.km_total,
                        e.costo_total,
                        e.fecha_confirmada_recoleccion,
                        o.nombre AS origen,
                        pr.razon_social AS trasladista,
                        tt.nombre AS tipo_traslado,
                        (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins,
                        (SELECT COUNT(*) FROM lgs_solicitudes_entrega WHERE id_envio = e.id_envio AND confirmado = 1) AS vins_entregados
                    FROM lgs_envios e
                    LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                    LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                    LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                    WHERE e.id_estado IN (3, 5, 6) AND e.deleted_at IS NULL {$wherePlanta}
                    ORDER BY e.id_estado ASC, e.id_envio DESC";
            
            $stmt = $this->getConexion()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $wherePlanta = "";
            $params = [];
            if ($plantaId !== null && $plantaId > 0) {
                $wherePlanta = " AND e.id_origen = :planta_id ";
                $params['planta_id'] = $plantaId;
            }
            $sqlFallback = "SELECT 
                        e.id_envio,
                        e.folio,
                        e.fecha_salida_real,
                        e.id_estado,
                        e.km_total,
                        e.costo_total,
                        NULL AS fecha_confirmada_recoleccion,
                        o.nombre AS origen,
                        pr.razon_social AS trasladista,
                        tt.nombre AS tipo_traslado,
                        (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins,
                        0 AS vins_entregados
                    FROM lgs_envios e
                    LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                    LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                    LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                    WHERE e.id_estado IN (3, 5, 6) AND e.deleted_at IS NULL {$wherePlanta}
                    ORDER BY e.id_estado ASC, e.id_envio DESC";
            $stmt = $this->getConexion()->prepare($sqlFallback);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    public function crearTablaSolicitudesSiNoExiste(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS lgs_solicitudes_entrega (
            id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
            id_envio INT NOT NULL,
            id_unidad INT NOT NULL,
            orden_acomodo INT DEFAULT 1,
            confirmado TINYINT(1) DEFAULT 0,
            fecha_confirmacion DATETIME NULL,
            confirmado_by INT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_envio_unidad (id_envio, id_unidad)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->getConexion()->exec($sql);
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
                    ev.estado_unidad_fisico,
                    COALESCE(u.vin, ut.clave, 'S/VIN') AS vin,
                    COALESCE(u.modelo, 'Unidad Terminada') AS modelo,
                    COALESCE(u.color, ut.color, 'Blanco') AS color,
                    COALESCE(m.numero_economico, 'Sin Madrina') AS madrina,
                    COALESCE(CONCAT(c.nombre, ' ', c.apellidos), 'Sin Chofer') AS chofer,
                    se.id_solicitud,
                    se.confirmado,
                    se.fecha_confirmacion,
                    se.confirmado_by
                FROM lgs_envios_vins ev
                LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                LEFT JOIN prv_det_madrinas m ON ev.id_madrina = m.id_madrina
                LEFT JOIN prv_det_choferes c ON ev.id_chofer = c.id_chofer
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
     * Actualiza el estado del envío
     */
    public function updateEstadoEnvio(PDO $db, int $idEnvio, int $idEstado): void
    {
        $sql = "UPDATE lgs_envios SET id_estado = :estado WHERE id_envio = :id_envio";
        $stmt = $db->prepare($sql);
        $stmt->execute(['estado' => $idEstado, 'id_envio' => $idEnvio]);
    }

    /**
     * Confirma la fecha pactada de recolección y cambia los estados
     */
    public function confirmarFechaRecoleccion(PDO $db, int $idEnvio, string $fechaRecoleccion): void
    {
        $sql = "UPDATE lgs_envios 
                SET fecha_confirmada_recoleccion = :fecha, id_estado = 5 
                WHERE id_envio = :id_envio";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'fecha' => $fechaRecoleccion,
            'id_envio' => $idEnvio
        ]);

        // Mover unidades del envío a 'EN_ENTREGAS'
        $this->updateEstadoUnidadesFisico($db, $idEnvio, 'EN_ENTREGAS');
    }

    /**
     * Actualiza el estado físico de todos los VINs de un envío
     */
    public function updateEstadoUnidadesFisico(PDO $db, int $idEnvio, string $estadoUnidades): void
    {
        $sql = "UPDATE lgs_envios_vins 
                SET estado_unidad_fisico = :estado 
                WHERE id_envio = :id_envio";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'estado' => $estadoUnidades,
            'id_envio' => $idEnvio
        ]);
    }

    /**
     * Guarda el checklist digital de un traslado
     */
    public function registrarChecklist(PDO $db, int $idEnvio, int $idUnidad, string $tipoChecklist, string $vin, int $userId, ?string $comentarios): int
    {
        $sql = "INSERT INTO lgs_trasladistas_checklist (id_envio, id_unidad, tipo_checklist, vin_escaneado, usuario_registro_id, comentarios)
                VALUES (:id_envio, :id_unidad, :tipo, :vin, :user, :comentarios)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id_envio' => $idEnvio,
            'id_unidad' => $idUnidad,
            'tipo' => $tipoChecklist,
            'vin' => $vin,
            'user' => $userId,
            'comentarios' => $comentarios
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Inserta una foto de evidencia ligada al checklist
     */
    public function registrarChecklistEvidencia(PDO $db, int $idChecklist, string $tipoFoto, string $rutaArchivo): void
    {
        $sql = "INSERT INTO lgs_checklist_evidencias (id_checklist, tipo_foto, ruta_archivo)
                VALUES (:id_checklist, :tipo, :ruta)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id_checklist' => $idChecklist,
            'tipo' => $tipoFoto,
            'ruta' => $rutaArchivo
        ]);
    }

    /**
     * Obtiene el listado de envíos asignados a un chofer
     */
    public function getEnviosPorChofer(int $idChofer): array
    {
        $sql = "SELECT DISTINCT e.id_envio, e.folio, e.id_estado, e.fecha_confirmada_recoleccion,
                       o.nombre AS origen, o.direccion AS origen_dir,
                       COALESCE(d.nombre, e.destino_nombre_libre, 'Sin Destino') AS destino,
                       d.direccion AS destino_dir
                FROM lgs_envios e
                INNER JOIN lgs_envios_vins ev ON e.id_envio = ev.id_envio
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN lgs_cat_destinos d ON ev.id_destino = d.id_destino
                WHERE ev.id_chofer = :chofer AND e.id_estado IN (5, 6) AND e.deleted_at IS NULL";
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->execute(['chofer' => $idChofer]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
