<?php

class Lgs_enviosModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_envios';

    const SCHEMA = [
        'lgs_envios' => [
            'id_envio',
            'folio',
            'id_tipo_traslado',
            'id_motivo',
            'id_proveedor',
            'id_origen',
            'km_total',
            'costo_total',
            'fecha_tentativa_envio',
            'fecha_tentativa_llegada',
            'fecha_salida_real',
            'fecha_llegada_real',
            'observaciones',
            'id_estado',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
        'lgs_envios_vins' => [
            'id',
            'id_envio',
            'id_unidad',
            'id_destino',
            'destino_nombre_libre',
            'id_madrina',
            'id_chofer',
            'posicion_acomodo',
            'costo_unidad',
            'fecha_entrega_real',
            'recibe_nombre',
            'id_estado',
            'created_at',
        ]
    ];

    /**
     * Genera un nuevo folio transaccional EN-000001
     */
    public function generarFolioTransaccional(PDO $db): string
    {
        $sql = "SELECT folio FROM lgs_envios ORDER BY id_envio DESC LIMIT 1 FOR UPDATE";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $ultimo = $stmt->fetchColumn();
        
        if (!$ultimo) {
            return 'EN-000001';
        }
        
        // Extraer número y sumar 1
        $num = intval(substr($ultimo, 3)) + 1;
        return 'EN-' . str_pad($num, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene todos los envíos activos para el Datatable
     */
    public function getEnviosDataTable(): array
    {
        $sql = "SELECT 
                    e.id_envio, 
                    e.folio, 
                    tt.nombre AS tipo_traslado,
                    mo.descripcion AS motivo,
                    pr.razon_social AS trasladista,
                    o.nombre AS origen,
                    e.km_total,
                    e.costo_total,
                    e.fecha_tentativa_envio,
                    e.id_estado,
                    (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins
                FROM lgs_envios e
                LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                LEFT JOIN lgs_cat_motivo_envio mo ON e.id_motivo = mo.id_motivo
                LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                WHERE e.deleted_at IS NULL
                ORDER BY e.id_envio DESC";
        
        $request = $this->select_all($sql);
        return $request;
    }

    /**
     * Inserta la cabecera del envío
     */
    public function insertEnvio(PDO $db, array $data): int
    {
        $campos = $this->prepararCampos(self::SCHEMA['lgs_envios'], $data);
        $keys = implode(', ', array_keys($campos));
        $placeholders = ':' . implode(', :', array_keys($campos));
        
        $sql = "INSERT INTO lgs_envios ({$keys}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute($campos);
        
        return $db->lastInsertId();
    }

    /**
     * Inserta un VIN al envío
     */
    public function insertVin(PDO $db, array $data): int
    {
        $campos = $this->prepararCampos(self::SCHEMA['lgs_envios_vins'], $data);
        $keys = implode(', ', array_keys($campos));
        $placeholders = ':' . implode(', :', array_keys($campos));
        
        $sql = "INSERT INTO lgs_envios_vins ({$keys}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute($campos);
        
        return $db->lastInsertId();
    }

    /**
     * Helper para filtrar los campos según el SCHEMA
     */
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
