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

    public function getTableName(): string {
        return $this->table;
    }

    public function getConexion(): PDO {
        return $this->conexion;
    }

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

    /**
     * Obtiene el desglose completo de una planeación con sus envíos, madrinas, VINs, modelos, destinos y costos
     */
    public function getDetalleCompletoPlan(int $idPlaneacion): array
    {
        // 1. Cabecera de la planeación
        $sqlPlan = "SELECT 
                        p.id_planeacion,
                        p.folio,
                        p.descripcion,
                        p.km_total,
                        p.costo_total,
                        p.id_estado,
                        p.obs_operador,
                        p.obs_aprobador,
                        p.created_at,
                        u.nombres AS creador
                    FROM lgs_planeaciones p
                    LEFT JOIN usuarios u ON p.created_by = u.idusuario
                    WHERE p.id_planeacion = ?";
        $plan = $this->select($sqlPlan, [$idPlaneacion]);
        if (empty($plan)) return [];

        // 2. Envíos incluidos en esta planeación
        $sqlEnvios = "SELECT 
                        e.id_envio,
                        e.folio,
                        e.km_total,
                        e.costo_total,
                        e.id_tipo_traslado,
                        tt.nombre AS tipo_traslado,
                        pr.razon_social AS trasladista,
                        o.nombre AS origen,
                        e.id_estado
                      FROM lgs_planeaciones_envios pe
                      INNER JOIN lgs_envios e ON pe.id_envio = e.id_envio
                      LEFT JOIN lgs_cat_tipo_traslado tt ON e.id_tipo_traslado = tt.id_tipo_traslado
                      LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                      LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                      WHERE pe.id_planeacion = ?";
        $envios = $this->select_all($sqlEnvios, [$idPlaneacion]) ?: [];

        // 3. Traer los VINs, modelos, segmentos y costos de cada envío
        foreach ($envios as &$env) {
            $sqlVins = "SELECT 
                            v.id,
                            v.id_unidad,
                            v.id_madrina,
                            v.id_chofer,
                            v.posicion_acomodo,
                            v.costo_unidad,
                            COALESCE(u.vin, ut.clave, 'S/VIN') AS vin,
                            COALESCE(u.num_serie, ut.num_unidad, 'S/N') AS num_serie,
                            COALESCE(u.modelo, 'Unidad Terminada') AS modelo,
                            COALESCE(m.numero_economico, 'Sin Madrina') AS madrina,
                            COALESCE(CONCAT(c.nombre, ' ', c.apellidos), 'Sin Chofer') AS chofer,
                            COALESCE(NULLIF(cli.nombre_comercial, ''), cli.razon_social, d.nombre, p.destino_nombre_libre, 'Destino General') AS destino_parada,
                            p.orden AS orden_parada
                        FROM lgs_envios_vins v
                        LEFT JOIN lgs_unidades_envios u ON v.id_unidad = u.id_unidad
                        LEFT JOIN mrp_unidades_terminadas ut ON v.id_unidad = ut.idunidad
                        LEFT JOIN prv_det_madrinas m ON v.id_madrina = m.id_madrina
                        LEFT JOIN prv_det_choferes c ON v.id_chofer = c.id_chofer
                        LEFT JOIN lgs_envios_paradas p ON v.id_parada = p.id_parada
                        LEFT JOIN cli_clientes cli ON p.id_destino_cat = cli.idcliente
                        LEFT JOIN lgs_cat_destinos d ON p.id_destino_cat = d.id_destino
                        WHERE v.id_envio = ?
                        ORDER BY v.posicion_acomodo ASC, v.id ASC";
            $vins = $this->select_all($sqlVins, [$env['id_envio']]) ?: [];
            $env['vins'] = $vins;
            $env['total_vins'] = count($vins);
        }
        unset($env);

        $plan['envios'] = $envios;
        return $plan;
    }

    /**
     * Reabre una planeación rechazada, regresándola y a sus envíos a estado borrador (1)
     */
    public function reabrirPlaneacion(PDO $db, int $idPlaneacion): void
    {
        // 1. Cambiar estado de la planeación a 1 (Borrador / Reabierto)
        $sql = "UPDATE lgs_planeaciones 
                SET id_estado = 1, 
                    obs_aprobador = CONCAT(COALESCE(obs_aprobador, ''), ' [Reabierto para corrección]')
                WHERE id_planeacion = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idPlaneacion]);

        // 2. Desbloquear los envíos para que vuelvan a estado 1 (Creado / Editable)
        $sqlEnvios = "UPDATE lgs_envios e
                      INNER JOIN lgs_planeaciones_envios pe ON e.id_envio = pe.id_envio
                      SET e.id_estado = 1
                      WHERE pe.id_planeacion = ?";
        $stmtEnv = $db->prepare($sqlEnvios);
        $stmtEnv->execute([$idPlaneacion]);
    }

    /**
     * Envía una planeación existente (en borrador 1) a Aprobación (estado 2)
     */
    public function enviarAprobacion(PDO $db, int $idPlaneacion): void
    {
        // 1. Cambiar estado de la planeación a 2 (Pendiente Aprobación)
        $sql = "UPDATE lgs_planeaciones SET id_estado = 2, updated_at = NOW() WHERE id_planeacion = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idPlaneacion]);

        // 2. Cambiar estado de los envíos dentro a 2 (En Revisión)
        $sqlEnvios = "UPDATE lgs_envios e
                      INNER JOIN lgs_planeaciones_envios pe ON e.id_envio = pe.id_envio
                      SET e.id_estado = 2
                      WHERE pe.id_planeacion = ?";
        $stmtEnv = $db->prepare($sqlEnvios);
        $stmtEnv->execute([$idPlaneacion]);
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
