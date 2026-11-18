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
                  `origen_almacen` varchar(150) DEFAULT NULL,
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

            $colOrigen = $db->query("SHOW COLUMNS FROM `lgs_det_preplaneaciones_vins` LIKE 'origen_almacen'")->fetch(PDO::FETCH_ASSOC);
            if (!$colOrigen) {
                $db->exec("ALTER TABLE `lgs_det_preplaneaciones_vins` ADD COLUMN `origen_almacen` varchar(150) DEFAULT NULL AFTER `modelo`");
            }
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
                    COALESCE(
                        pl.nombre_planta,
                        (SELECT NULLIF(TRIM(d.origen_almacen), '') FROM lgs_det_preplaneaciones_vins d WHERE d.id_preplaneacion = p.id_preplaneacion AND d.origen_almacen IS NOT NULL AND TRIM(d.origen_almacen) <> '' LIMIT 1),
                        'Almacén / Planta'
                    ) AS planta_nombre,
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
                    COALESCE(
                        pl.nombre_planta,
                        (SELECT NULLIF(TRIM(d.origen_almacen), '') FROM lgs_det_preplaneaciones_vins d WHERE d.id_preplaneacion = p.id_preplaneacion AND d.origen_almacen IS NOT NULL AND TRIM(d.origen_almacen) <> '' LIMIT 1),
                        'Almacén / Planta'
                    ) AS planta_nombre,
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
                    COALESCE(NULLIF(TRIM(d.origen_almacen), ''), NULLIF(TRIM(u.origen), ''), 'Planta / Almacén') AS origen_almacen,
                    d.destino_sugerido,
                    d.disponible_planta,
                    d.motivo_no_disponible,
                    d.confirmado_by,
                    d.confirmado_at
                FROM lgs_det_preplaneaciones_vins d
                LEFT JOIN lgs_unidades_envios u ON d.id_unidad = u.id_unidad
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
     * Obtiene las unidades / VINs disponibles desde WMS PT, bandeja logística y unidades terminadas,
     * incluyendo su almacén y planta de origen como datos informativos.
     */
    public function getVinsDisponibles(int $idPlanta = 0, string $busqueda = ''): array
    {
        if (!class_exists('Lgs_bandejaModel', false)) {
            require_once "Models/Lgs_bandejaModel.php";
        }

        $bandejaModel = new Lgs_bandejaModel();
        $unidades = $bandejaModel->getUnidadesPTGlobales();
        $busqLower = mb_strtolower(trim($busqueda), 'UTF-8');
        $rows = [];

        foreach ($unidades as $u) {
            if (intval($u['envio_id'] ?? 0) !== 0) {
                continue;
            }

            $uIdPlanta = intval($u['id_planta'] ?? 0);
            if ($idPlanta > 0 && $uIdPlanta !== $idPlanta) {
                continue;
            }

            $vin = trim((string)($u['vin'] ?? ''));
            if ($vin === '') {
                continue;
            }

            $numSerie      = trim((string)($u['num_serie'] ?? '')) ?: 'S/N';
            $modelo        = trim((string)($u['modelo'] ?? '')) ?: 'Unidad Terminada';
            $plantaNombre  = trim((string)($u['planta_nombre'] ?? '')) ?: 'Sin planta asignada';
            $cveAlmacen    = trim((string)($u['cve_almacen'] ?? ''));
            $almacenNombre = trim((string)($u['almacen_nombre'] ?? ''));
            $destinoSug    = trim((string)($u['destino_sugerido'] ?? '')) ?: 'Por Asignar';

            if ($cveAlmacen !== '' && !in_array($cveAlmacen, ['SIN-ALM', 'LGS', 'PT'], true)) {
                $origenInformativo = $almacenNombre !== ''
                    ? "{$cveAlmacen} · {$almacenNombre}"
                    : $cveAlmacen;
                if ($plantaNombre !== '' && $plantaNombre !== 'Sin planta asignada') {
                    $origenInformativo .= " ({$plantaNombre})";
                }
            } else {
                $origenInformativo = $plantaNombre;
            }

            if ($busqLower !== '') {
                $haystack = mb_strtolower(
                    "{$vin} {$numSerie} {$modelo} {$plantaNombre} {$cveAlmacen} {$almacenNombre} {$origenInformativo}",
                    'UTF-8'
                );
                if (mb_strpos($haystack, $busqLower) === false) {
                    continue;
                }
            }

            $rows[] = [
                'id_unidad'          => intval($u['id_unidad'] ?? 0),
                'vin'                => $vin,
                'num_serie'          => $numSerie,
                'modelo'             => $modelo,
                'id_planta'          => $uIdPlanta,
                'planta_nombre'      => $plantaNombre,
                'cve_almacen'        => $cveAlmacen,
                'almacen_nombre'     => $almacenNombre,
                'origen_informativo' => $origenInformativo,
                'destino_sugerido'   => $destinoSug,
            ];
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
            $idPlanta = intval($data['id_planta'] ?? 0);

            if ($idPlanta <= 0) {
                foreach ($vins as $v) {
                    $candPlanta = intval($v['id_planta'] ?? 0);
                    if ($candPlanta > 0) {
                        $idPlanta = $candPlanta;
                        break;
                    }
                }
            }

            if ($idPlanta <= 0 && !empty($vins[0]['vin'])) {
                $stmtPl = $db->prepare("
                    SELECT COALESCE(a.plantaid, 0)
                    FROM wms_numeros_series ns
                    INNER JOIN wms_almacenes a ON a.idalmacen = ns.almacenid
                    WHERE UPPER(TRIM(ns.numero_serie)) = UPPER(TRIM(?))
                    LIMIT 1
                ");
                $stmtPl->execute([trim((string)$vins[0]['vin'])]);
                $idPlanta = intval($stmtPl->fetchColumn() ?: 0);
            }

            $sql = "INSERT INTO lgs_preplaneaciones (
                        folio, id_planta, fecha_tentativa_recoleccion, 
                        id_estado, created_by, created_at
                    ) VALUES (?, ?, ?, 1, ?, NOW())";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $folio,
                $idPlanta,
                $data['fecha_tentativa_recoleccion'],
                $data['created_by']
            ]);

            $idPreplaneacion = (int)$db->lastInsertId();

            $stmtCheckUnidad = $db->prepare("SELECT id_unidad FROM lgs_unidades_envios WHERE UPPER(TRIM(vin)) = ? LIMIT 1");
            $stmtInsUnidad = $db->prepare("
                INSERT INTO lgs_unidades_envios (vin, num_serie, modelo, origen, destino, estatus)
                VALUES (?, ?, ?, ?, ?, 'disponible')
            ");

            // Insertar detalle de VINs
            $sqlVin = "INSERT INTO lgs_det_preplaneaciones_vins (
                           id_preplaneacion, id_unidad, vin, modelo, origen_almacen, destino_sugerido
                       ) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtVin = $db->prepare($sqlVin);

            foreach ($vins as $v) {
                $vinClean = strtoupper(trim((string)($v['vin'] ?? '')));
                $idUnidad = intval($v['id_unidad'] ?? 0);
                $modelo = trim((string)($v['modelo'] ?? '')) ?: 'Unidad';
                $origenAlmacen = trim((string)($v['origen_almacen'] ?? $v['origen_informativo'] ?? ''));
                if ($origenAlmacen === '') {
                    $origenAlmacen = trim((string)($v['planta_nombre'] ?? 'Planta / Almacén')) ?: 'Planta / Almacén';
                }
                $destinoSug = trim((string)($v['destino_sugerido'] ?? '')) ?: 'Por Asignar';

                if ($idUnidad <= 0 && $vinClean !== '') {
                    $stmtCheckUnidad->execute([$vinClean]);
                    $idExist = $stmtCheckUnidad->fetchColumn();
                    if ($idExist) {
                        $idUnidad = intval($idExist);
                    } else {
                        $numSerie = trim((string)($v['num_serie'] ?? substr($vinClean, -6)));
                        $stmtInsUnidad->execute([
                            mb_substr($vinClean, 0, 50),
                            mb_substr($numSerie, 0, 50),
                            mb_substr($modelo, 0, 100),
                            mb_substr($origenAlmacen, 0, 150),
                            mb_substr($destinoSug, 0, 150),
                        ]);
                        $idUnidad = intval($db->lastInsertId());
                    }
                }

                $stmtVin->execute([
                    $idPreplaneacion,
                    $idUnidad > 0 ? $idUnidad : null,
                    mb_substr($vinClean, 0, 30),
                    mb_substr($modelo, 0, 100),
                    mb_substr($origenAlmacen, 0, 150),
                    mb_substr($destinoSug, 0, 150),
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

