<?php

class Lgs_evidenciasModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_evidencias';

    public function getTableName(): string {
        return $this->table;
    }

    public function getConexion(): PDO {
        return $this->conexion;
    }

    /**
     * Obtiene los envíos aprobados, programados, en tránsito o completados (Estados 3, 5, 6, 7) para gestión de evidencias
     */
    public function getEnviosParaEvidencias(): array
    {
        $sql = "SELECT
                    e.id_envio,
                    e.folio,
                    e.fecha_salida_real,
                    e.fecha_llegada_real,
                    e.id_estado,
                    o.nombre AS origen,
                    pr.razon_social AS trasladista,
                    tt.nombre AS tipo_traslado,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins,
                    (
                        SELECT GROUP_CONCAT(DISTINCT COALESCE(u.vin, ut.clave) ORDER BY ev.posicion_acomodo ASC SEPARATOR ', ')
                        FROM lgs_envios_vins ev
                        LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                        LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                        WHERE ev.id_envio = e.id_envio
                    ) AS vins_lista,
                    (
                        SELECT JSON_ARRAYAGG(
                            JSON_OBJECT(
                                'id_unidad', ev.id_unidad,
                                'vin', COALESCE(u.vin, ut.clave, 'S/VIN'),
                                'modelo', COALESCE(u.modelo, 'Unidad'),
                                'posicion', COALESCE(ev.posicion_acomodo, 1),
                                'estado_fisico', COALESCE(ev.estado_unidad_fisico, 'EN_PATIO')
                            )
                        )
                        FROM lgs_envios_vins ev
                        LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                        LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                        WHERE ev.id_envio = e.id_envio
                    ) AS vins_detalle_json,
                    (SELECT COUNT(*) FROM lgs_evidencias WHERE id_envio = e.id_envio AND tipo_evidencia = 1) AS total_evidencias_planta,
                    (SELECT COUNT(*) FROM lgs_evidencias WHERE id_envio = e.id_envio AND tipo_evidencia = 2) AS total_evidencias_destino,
                    (SELECT COUNT(*) FROM lgs_evidencias WHERE id_envio = e.id_envio) AS total_evidencias
                FROM lgs_envios e
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                WHERE e.id_estado IN (3, 5, 6, 7) AND e.deleted_at IS NULL
                ORDER BY e.id_estado ASC, e.id_envio DESC";
        
        return $this->select_all($sql);
    }

    /**
     * Obtiene las evidencias multimedia asociadas a un envío, incluyendo datos del VIN si aplica
     */
    public function getEvidenciasPorEnvio(int $idEnvio): array
    {
        $sql = "SELECT 
                    ev.id_evidencia, 
                    ev.id_envio, 
                    ev.id_unidad, 
                    ev.tipo_evidencia, 
                    ev.ruta_archivo, 
                    ev.observaciones, 
                    ev.created_at,
                    COALESCE(u.vin, ut.clave, 'General de Envío') AS vin,
                    COALESCE(u.modelo, 'General') AS modelo,
                    CONCAT(COALESCE(usr.nombres, ''), ' ', COALESCE(usr.apellidos, '')) AS usuario_nombre
                FROM lgs_evidencias ev
                LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                LEFT JOIN usuarios usr ON ev.created_by = usr.idusuario
                WHERE ev.id_envio = :id_envio
                ORDER BY ev.tipo_evidencia ASC, ev.created_at DESC";
        
        try {
            $stmt = $this->getConexion()->prepare($sql);
            $stmt->execute(['id_envio' => $idEnvio]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $sqlFallback = "SELECT 
                        ev.id_evidencia, 
                        ev.id_envio, 
                        ev.id_unidad, 
                        ev.tipo_evidencia, 
                        ev.ruta_archivo, 
                        ev.observaciones, 
                        ev.created_at,
                        COALESCE(u.vin, ut.clave, 'General de Envío') AS vin,
                        COALESCE(u.modelo, 'General') AS modelo,
                        '' AS usuario_nombre
                    FROM lgs_evidencias ev
                    LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                    LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                    WHERE ev.id_envio = :id_envio
                    ORDER BY ev.tipo_evidencia ASC, ev.created_at DESC";
            $stmt = $this->getConexion()->prepare($sqlFallback);
            $stmt->execute(['id_envio' => $idEnvio]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Inserta un nuevo registro de evidencia multimedia
     */
    public function insertEvidencia(PDO $db, array $data): int
    {
        $sql = "INSERT INTO lgs_evidencias (id_envio, id_unidad, tipo_evidencia, ruta_archivo, observaciones, created_by)
                VALUES (:id_envio, :id_unidad, :tipo_evidencia, :ruta_archivo, :observaciones, :created_by)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id_envio' => $data['id_envio'],
            'id_unidad' => $data['id_unidad'] ?? null,
            'tipo_evidencia' => $data['tipo_evidencia'], // 1: Salida, 2: Llegada
            'ruta_archivo' => $data['ruta_archivo'],
            'observaciones' => $data['observaciones'] ?? '',
            'created_by' => $data['created_by']
        ]);

        return $db->lastInsertId();
    }

    /**
     * Elimina un registro de evidencia
     */
    public function deleteEvidencia(PDO $db, int $idEvidencia): void
    {
        $sql = "DELETE FROM lgs_evidencias WHERE id_evidencia = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $idEvidencia]);
    }

    /**
     * Finaliza y marca el envío y todas sus unidades como Entregado en Destino (Estado 7)
     */
    public function registrarEntregaFinal(PDO $db, int $idEnvio, string $fechaLlegada, int $userId): void
    {
        // 1. Actualizar el envío a estado 7 (Entregado) y registrar fecha real de llegada
        $sqlEnvio = "UPDATE lgs_envios 
                     SET id_estado = 7, fecha_llegada_real = :fecha_llegada
                     WHERE id_envio = :id_envio";
        $stmtEnvio = $db->prepare($sqlEnvio);
        $stmtEnvio->execute([
            'fecha_llegada' => $fechaLlegada,
            'id_envio' => $idEnvio
        ]);

        // 2. Marcar todos los VINs asignados al envío como ENTREGADO con su fecha real
        $sqlVins = "UPDATE lgs_envios_vins 
                    SET estado_unidad_fisico = 'ENTREGADO', 
                        fecha_entrega_real = COALESCE(fecha_entrega_real, :fecha_llegada)
                    WHERE id_envio = :id_envio";
        $stmtVins = $db->prepare($sqlVins);
        $stmtVins->execute([
            'fecha_llegada' => $fechaLlegada,
            'id_envio' => $idEnvio
        ]);

        // 3. Sincronizar lgs_unidades (Bandeja de Unidades) a id_estado_proceso = 3 (Entregado)
        $stmtIds = $db->prepare("SELECT id_unidad FROM lgs_envios_vins WHERE id_envio = ?");
        $stmtIds->execute([$idEnvio]);
        $unitIds = $stmtIds->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($unitIds)) {
            $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
            $sqlUnidades = "UPDATE lgs_unidades 
                            SET id_estado_proceso = 3, 
                                fecha_llegada = COALESCE(fecha_llegada, ?),
                                updated_by = ?,
                                updated_at = NOW() 
                            WHERE id_unidad IN ({$placeholders})";
            $paramsUnidades = array_merge([$fechaLlegada, $userId], $unitIds);
            $stmtUnidades = $db->prepare($sqlUnidades);
            $stmtUnidades->execute($paramsUnidades);

            // 4. Actualizar estatus en lgs_unidades_envios
            $sqlEnviosUnidades = "UPDATE lgs_unidades_envios 
                                  SET estatus = 'entregado' 
                                  WHERE id_unidad IN ({$placeholders})";
            $stmtEnviosUnidades = $db->prepare($sqlEnviosUnidades);
            $stmtEnviosUnidades->execute($unitIds);
        }
    }
}
