<?php

class Lgs_planeacionesModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_planeaciones';

    const SCHEMA = [
        'lgs_planeaciones' => [
            'id_planeacion',
            'folio',
            'descripcion',
            'km_total',
            'costo_total',
            'id_estado',
            'obs_operador',
            'obs_aprobador',
            'created_by',
            'aprobado_by',
            'aprobado_at',
            'created_at',
            'updated_at'
        ],
        'lgs_planeaciones_envios' => [
            'id',
            'id_planeacion',
            'id_envio',
            'created_at'
        ]
    ];

    /**
     * Genera un nuevo folio transaccional EX-000001
     */
    public function generarFolioPlan(PDO $db): string
    {
        $sql = "SELECT folio FROM lgs_planeaciones ORDER BY id_planeacion DESC LIMIT 1 FOR UPDATE";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $ultimo = $stmt->fetchColumn();
        
        if (!$ultimo) {
            return 'EX-000001';
        }
        
        $num = intval(substr($ultimo, 3)) + 1;
        return 'EX-' . str_pad($num, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene el listado de planeaciones (Bandeja del Operador)
     */
    public function getPlaneacionesDataTable(): array
    {
        $sql = "SELECT 
                    p.id_planeacion,
                    p.folio,
                    p.descripcion,
                    p.km_total,
                    p.costo_total,
                    p.id_estado,
                    p.created_at,
                    (SELECT COUNT(*) FROM lgs_planeaciones_envios WHERE id_planeacion = p.id_planeacion) AS total_rutas
                FROM lgs_planeaciones p
                ORDER BY p.id_planeacion DESC";
        
        return $this->select_all($sql);
    }

    /**
     * Inserta la cabecera de la planeación
     */
    public function insertPlaneacion(PDO $db, array $data): int
    {
        $campos = $this->prepararCampos(self::SCHEMA['lgs_planeaciones'], $data);
        $keys = implode(', ', array_keys($campos));
        $placeholders = ':' . implode(', :', array_keys($campos));
        
        $sql = "INSERT INTO lgs_planeaciones ({$keys}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute($campos);
        
        return $db->lastInsertId();
    }

    /**
     * Vincula un envío (ruta) a la planeación
     */
    public function insertPlanEnvio(PDO $db, int $idPlaneacion, int $idEnvio): void
    {
        $sql = "INSERT INTO lgs_planeaciones_envios (id_planeacion, id_envio) VALUES (:plan, :envio)";
        $stmt = $db->prepare($sql);
        $stmt->execute(['plan' => $idPlaneacion, 'envio' => $idEnvio]);
    }

    /**
     * Obtiene los envíos listos para ser planeados (Estado 1 - Creado)
     */
    public function getEnviosDisponiblesPlan(): array
    {
        $sql = "SELECT 
                    e.id_envio, 
                    e.folio,
                    e.costo_total,
                    e.km_total,
                    o.nombre AS origen,
                    pr.razon_social AS trasladista,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins
                FROM lgs_envios e
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                WHERE e.id_estado = 1 AND e.deleted_at IS NULL";
        
        return $this->select_all($sql);
    }

    private function prepararCampos(array $schema, array $data): array
    {
        $campos = [];
        foreach ($schema as $campo) {
            if (array_key_exists($campo, $data)) {
                $campos[$campo] = $data[$campo];
            }
        }
        return $campos;
    }
}
