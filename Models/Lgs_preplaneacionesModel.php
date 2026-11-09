<?php

class Lgs_preplaneacionesModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_preplaneaciones';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTables();
    }

    /**
     * Asegura de forma idempotente que las tablas de preplaneaciones existan
     */
    public function ensureTables(): void
    {
        static $checked = false;
        if ($checked) return;

        try {
            $db = $this->getConexion();
            if ($db->inTransaction()) return;

            $checked = true;

            $db->exec("
                CREATE TABLE IF NOT EXISTS `lgs_preplaneaciones` (
                  `id_preplaneacion` int NOT NULL AUTO_INCREMENT,
                  `folio` varchar(30) NOT NULL,
                  `id_planta` int NOT NULL,
                  `fecha_tentativa_recoleccion` datetime DEFAULT NULL,
                  `contacto_planta` varchar(150) DEFAULT NULL,
                  `fecha_confirmacion` datetime DEFAULT NULL,
                  `notas_comunicacion` text DEFAULT NULL,
                  `id_estado` int NOT NULL DEFAULT 1 COMMENT '1: Enviado a Planta, 2: Confirmado por Planta, 3: Convertido a Planeación, 4: Cancelado',
                  `id_planeacion_generada` int DEFAULT NULL,
                  `created_by` int NOT NULL,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_by` int DEFAULT NULL,
                  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                  `deleted_at` datetime DEFAULT NULL,
                  PRIMARY KEY (`id_preplaneacion`),
                  UNIQUE KEY `uq_preplaneacion_folio` (`folio`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $db->exec("
                CREATE TABLE IF NOT EXISTS `lgs_det_preplaneaciones_vins` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `id_preplaneacion` int NOT NULL,
                  `id_unidad` int DEFAULT NULL,
                  `vin` varchar(30) NOT NULL,
                  `modelo` varchar(100) DEFAULT NULL,
                  `destino_sugerido` varchar(150) DEFAULT NULL,
                  `disponible_planta` tinyint(1) DEFAULT NULL COMMENT 'NULL: Pendiente, 1: Sí disponible, 0: No disponible',
                  `motivo_no_disponible` varchar(255) DEFAULT NULL,
                  `confirmado_by` int DEFAULT NULL,
                  `confirmado_at` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `idx_preplan_id` (`id_preplaneacion`),
                  CONSTRAINT `fk_det_preplaneacion` FOREIGN KEY (`id_preplaneacion`) REFERENCES `lgs_preplaneaciones` (`id_preplaneacion`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Throwable $e) {
            // Silencioso en caso de no contar con permisos DDL
        }
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function getConexion(): PDO
    {
        return $this->conexion;
    }

    /**
     * Genera un folio secuencial transaccional para preplaneaciones (ej. PRE-00001)
     */
    public function generarFolioTransaccional(PDO $db): string
    {
        $stmt = $db->query("SELECT MAX(id_preplaneacion) AS max_id FROM lgs_preplaneaciones");
        $nextId = (int)$stmt->fetchColumn() + 1;
        return "PRE-" . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Lista todas las preplaneaciones con resumen de unidades solicitadas vs confirmadas
     */
    public function getPreplaneaciones(): array
    {
        $sql = "SELECT 
                    p.id_preplaneacion,
                    p.folio,
                    p.id_planta,
                    COALESCE(pl.nombre_planta, 'Planta') AS planta_nombre,
                    p.fecha_tentativa_recoleccion,
                    p.contacto_planta,
                    p.fecha_confirmacion,
                    p.notas_comunicacion,
                    p.id_estado,
                    p.id_planeacion_generada,
                    plan.folio AS folio_planeacion_generada,
                    u.nombres AS usuario_creador,
                    p.created_at,
                    (SELECT COUNT(*) FROM lgs_det_preplaneaciones_vins d WHERE d.id_preplaneacion = p.id_preplaneacion) AS total_vins_solicitados,
                    (SELECT COUNT(*) FROM lgs_det_preplaneaciones_vins d WHERE d.id_preplaneacion = p.id_preplaneacion AND d.disponible_planta = 1) AS total_vins_confirmados,
                    (SELECT COUNT(*) FROM lgs_det_preplaneaciones_vins d WHERE d.id_preplaneacion = p.id_preplaneacion AND d.disponible_planta = 0) AS total_vins_rechazados
                FROM lgs_preplaneaciones p
                LEFT JOIN mrp_planta pl ON p.id_planta = pl.idplanta
                LEFT JOIN lgs_planeaciones plan ON p.id_planeacion_generada = plan.id_planeacion
                LEFT JOIN usuarios u ON p.created_by = u.idusuario
                WHERE p.deleted_at IS NULL
                ORDER BY p.id_preplaneacion DESC";

        return $this->select_all($sql) ?: [];
    }

    /**
     * Obtiene la cabecera de una preplaneación por ID
     */
    public function getPreplaneacion(int $idPreplaneacion): ?array
    {
        $sql = "SELECT 
                    p.id_preplaneacion,
                    p.folio,
                    p.id_planta,
                    COALESCE(pl.nombre_planta, 'Planta') AS planta_nombre,
                    p.fecha_tentativa_recoleccion,
                    p.contacto_planta,
                    p.fecha_confirmacion,
                    p.notas_comunicacion,
                    p.id_estado,
                    p.id_planeacion_generada,
                    plan.folio AS folio_planeacion_generada,
                    u.nombres AS usuario_creador,
                    p.created_at
                FROM lgs_preplaneaciones p
                LEFT JOIN mrp_planta pl ON p.id_planta = pl.idplanta
                LEFT JOIN lgs_planeaciones plan ON p.id_planeacion_generada = plan.id_planeacion
                LEFT JOIN usuarios u ON p.created_by = u.idusuario
                WHERE p.id_preplaneacion = ? AND p.deleted_at IS NULL";

        return $this->select($sql, [$idPreplaneacion]) ?: null;
    }

    /**
     * Obtiene los VINs asociados a una preplaneación con su estado de disponibilidad
     */
    public function getVinsPreplaneacion(int $idPreplaneacion): array
    {
        $sql = "SELECT 
                    d.id,
                    d.id_preplaneacion,
                    d.id_unidad,
                    d.vin,
                    d.modelo,
                    d.destino_sugerido,
                    d.disponible_planta,
                    d.motivo_no_disponible,
                    d.confirmado_by,
                    d.confirmado_at
                FROM lgs_det_preplaneaciones_vins d
                WHERE d.id_preplaneacion = ?
                ORDER BY d.id ASC";

        return $this->select_all($sql, [$idPreplaneacion]) ?: [];
    }

    /**
     * Obtiene las plantas disponibles para el select
     */
    public function getPlantas(): array
    {
        $sql = "SELECT idplanta, cve_planta, nombre_planta, direccion 
                FROM mrp_planta 
                WHERE nombre_planta IS NOT NULL AND TRIM(nombre_planta) <> ''
                ORDER BY nombre_planta ASC";

        return $this->select_all($sql) ?: [];
    }

    /**
     * Obtiene las unidades / VINs listos o terminados disponibles para ser preplaneados
     */
    public function getVinsDisponibles(int $idPlanta = 0): array
    {
        // Consultar unidades de la bandeja de salida o terminadas que aún no tengan envío activo
        $sql = "SELECT 
                    u.id_unidad,
                    u.vin,
                    COALESCE(u.num_serie, 'S/N') AS num_serie,
                    COALESCE(u.modelo, 'Unidad Terminada') AS modelo,
                    COALESCE(u.destino, 'Por Asignar') AS destino_sugerido
                FROM lgs_unidades_envios u
                WHERE u.estatus = 'DISPONIBLE'
                   OR NOT EXISTS (
                        SELECT 1 FROM lgs_envios_vins ev
                        INNER JOIN lgs_envios e ON ev.id_envio = e.id_envio
                        WHERE ev.id_unidad = u.id_unidad AND e.id_estado NOT IN (7, 4) AND e.deleted_at IS NULL
                   )
                ORDER BY u.id_unidad DESC
                LIMIT 100";

        $rows = $this->select_all($sql) ?: [];

        // Si lgs_unidades_envios está vacío, consultar mrp_unidades_terminadas
        if (empty($rows)) {
            $sqlTerminadas = "SELECT 
                                ut.idunidad AS id_unidad,
                                ut.clave AS vin,
                                ut.num_unidad AS num_serie,
                                'Unidad Terminada' AS modelo,
                                'Por Asignar' AS destino_sugerido
                              FROM mrp_unidades_terminadas ut
                              LIMIT 100";
            $rows = $this->select_all($sqlTerminadas) ?: [];
        }

        return $rows;
    }

    /**
     * Guarda una nueva preplaneación con sus VINs
     */
    public function insertPreplaneacion(array $data, array $vins): array
    {
        $db = $this->getConexion();
        $db->beginTransaction();

        try {
            $folio = $this->generarFolioTransaccional($db);

            $sql = "INSERT INTO lgs_preplaneaciones (
                        folio, id_planta, fecha_tentativa_recoleccion, 
                        id_estado, created_by, created_at
                    ) VALUES (?, ?, ?, 1, ?, NOW())";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $folio,
                $data['id_planta'],
                $data['fecha_tentativa_recoleccion'],
                $data['created_by']
            ]);

            $idPreplaneacion = (int)$db->lastInsertId();

            // Insertar detalle de VINs
            $sqlVin = "INSERT INTO lgs_det_preplaneaciones_vins (
                           id_preplaneacion, id_unidad, vin, modelo, destino_sugerido
                       ) VALUES (?, ?, ?, ?, ?)";
            $stmtVin = $db->prepare($sqlVin);

            foreach ($vins as $v) {
                $stmtVin->execute([
                    $idPreplaneacion,
                    $v['id_unidad'],
                    $v['vin'],
                    $v['modelo'] ?? 'Unidad',
                    $v['destino_sugerido'] ?? 'Por Asignar'
                ]);
            }

            $db->commit();

            return [
                'id_preplaneacion' => $idPreplaneacion,
                'folio' => $folio,
                'total_vins' => count($vins)
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Registra la confirmación de disponibilidad acordada con Planta
     */
    public function registrarConfirmacionPlanta(
        int $idPreplaneacion, 
        string $contacto, 
        string $notas, 
        array $unidades, 
        int $userId
    ): void {
        $db = $this->getConexion();
        $db->beginTransaction();

        try {
            // 1. Actualizar estado por unidad en lgs_det_preplaneaciones_vins
            $sqlUpVin = "UPDATE lgs_det_preplaneaciones_vins 
                         SET disponible_planta = ?, motivo_no_disponible = ?, confirmado_by = ?, confirmado_at = NOW()
                         WHERE id_preplaneacion = ? AND (vin = ? OR id = ?)";
            $stmtUpVin = $db->prepare($sqlUpVin);

            foreach ($unidades as $u) {
                $disp = !empty($u['disponible']) ? 1 : 0;
                $motivo = ($disp === 1) ? null : ($u['motivo'] ?? 'No disponible en patio');
                $vin = $u['vin'] ?? '';
                $idDet = intval($u['id'] ?? 0);

                $stmtUpVin->execute([$disp, $motivo, $userId, $idPreplaneacion, $vin, $idDet]);
            }

            // 2. Actualizar cabecera a estado 2 (Confirmada por Planta)
            $sqlUpCab = "UPDATE lgs_preplaneaciones 
                         SET id_estado = 2, contacto_planta = ?, fecha_confirmacion = NOW(), notas_comunicacion = ?
                         WHERE id_preplaneacion = ?";
            $stmtUpCab = $db->prepare($sqlUpCab);
            $stmtUpCab->execute([$contacto, $notas, $idPreplaneacion]);

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Convierte una preplaneación confirmada en una Planeación Definitiva en lgs_planeaciones
     * utilizando únicamente los VINs marcados con disponible_planta = 1
     */
    public function convertirAPlaneacionDefinitiva(int $idPreplaneacion, int $userId): array
    {
        $db = $this->getConexion();
        $db->beginTransaction();

        try {
            $pre = $this->getPreplaneacion($idPreplaneacion);
            if (empty($pre)) {
                throw new Exception("La preplaneación solicitada no existe.");
            }

            if (intval($pre['id_estado']) === 3 && !empty($pre['id_planeacion_generada'])) {
                throw new Exception("Esta preplaneación ya fue convertida a la planeación " . $pre['folio_planeacion_generada']);
            }

            // Obtener VINs que sí están disponibles
            $sqlVinsConfirmados = "SELECT id_unidad, vin, modelo, destino_sugerido 
                                   FROM lgs_det_preplaneaciones_vins 
                                   WHERE id_preplaneacion = ? AND disponible_planta = 1";
            $stmt = $db->prepare($sqlVinsConfirmados);
            $stmt->execute([$idPreplaneacion]);
            $vinsListos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($vinsListos)) {
                throw new Exception("No hay ninguna unidad confirmada disponible para generar una planeación.");
            }

            // Generar folio de planeación
            $stmtPlanId = $db->query("SELECT MAX(id_planeacion) AS max_id FROM lgs_planeaciones");
            $nextPlanId = (int)$stmtPlanId->fetchColumn() + 1;
            $folioPlan = "PLN-" . str_pad((string)$nextPlanId, 5, '0', STR_PAD_LEFT);

            $descPlan = "Planeación generada desde Preplaneación " . $pre['folio'] . " (" . $pre['planta_nombre'] . ")";

            // Insertar en lgs_planeaciones
            $sqlPlan = "INSERT INTO lgs_planeaciones (
                            folio, descripcion, km_total, costo_total, id_estado, 
                            obs_operador, created_by, created_at
                        ) VALUES (?, ?, 0, 0, 1, ?, ?, NOW())";
            $stmtInsPlan = $db->prepare($sqlPlan);
            $obsOp = "Generada automáticamente desde Preplaneación " . $pre['folio'] . ". Contacto Planta: " . ($pre['contacto_planta'] ?: 'N/A');
            $stmtInsPlan->execute([$folioPlan, $descPlan, $obsOp, $userId]);
            $idPlaneacion = (int)$db->lastInsertId();

            // Actualizar preplaneación a estado 3 (Convertida a Planeación)
            $sqlUpPre = "UPDATE lgs_preplaneaciones 
                         SET id_estado = 3, id_planeacion_generada = ? 
                         WHERE id_preplaneacion = ?";
            $stmtUpPre = $db->prepare($sqlUpPre);
            $stmtUpPre->execute([$idPlaneacion, $idPreplaneacion]);

            $db->commit();

            return [
                'id_planeacion' => $idPlaneacion,
                'folio_planeacion' => $folioPlan,
                'total_vins_convertidos' => count($vinsListos)
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}

