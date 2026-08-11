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
     * Obtiene los envíos en tránsito (Estado 6) o completados (Estado 7) para gestión de evidencias y entrega final
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
                    (SELECT COUNT(*) FROM lgs_evidencias WHERE id_envio = e.id_envio) AS total_evidencias
                FROM lgs_envios e
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                WHERE e.id_estado IN (6, 7) AND e.deleted_at IS NULL
                ORDER BY e.id_estado ASC, e.id_envio DESC";
        
        return $this->select_all($sql);
    }

    /**
     * Obtiene las evidencias multimedia asociadas a un envío
     */
    public function getEvidenciasPorEnvio(int $idEnvio): array
    {
        $sql = "SELECT id_evidencia, id_envio, id_unidad, tipo_evidencia, ruta_archivo, observaciones, created_at 
                FROM lgs_evidencias 
                WHERE id_envio = :id_envio
                ORDER BY created_at DESC";
        
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->execute(['id_envio' => $idEnvio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
     * Finaliza y marca el envío como Entregado en Destino (Estado 7)
     */
    public function registrarEntregaFinal(PDO $db, int $idEnvio, string $fechaLlegada, int $userId): void
    {
        $sql = "UPDATE lgs_envios 
                SET id_estado = 7, fecha_llegada_real = :fecha_llegada
                WHERE id_envio = :id_envio";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'fecha_llegada' => $fechaLlegada,
            'id_envio' => $idEnvio
        ]);
    }
}
