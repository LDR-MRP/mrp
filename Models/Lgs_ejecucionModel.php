<?php

class Lgs_ejecucionModel extends Mysql
{
    use Auditable;

    protected string $table = 'lgs_solicitudes_entrega';

    public function __construct() {
        parent::__construct();
        try {
            $this->crearTablaSolicitudesSiNoExiste();
        } catch (Throwable $ignored) {}
    }

    /**
     * Reinicia el estado de un envío (y sus VINs / solicitudes) a estado 3 (Aprobado / Pendiente de Despacho) para pruebas
     */
    public function resetEnvioParaPrueba(int $idEnvio = 16): void
    {
        $db = $this->getConexion();
        try {
            // 1. Regresar estado del envío a 3 (Aprobado)
            $sql1 = "UPDATE lgs_envios SET id_estado = 3, fecha_salida_real = NULL, fecha_llegada_real = NULL WHERE id_envio = :id";
            $stmt1 = $db->prepare($sql1);
            $stmt1->execute(['id' => $idEnvio]);

            // 2. Regresar todos los VINs del envío a 'EN_PATIO'
            $sql2 = "UPDATE lgs_envios_vins SET estado_unidad_fisico = 'EN_PATIO', fecha_entrega_real = NULL, recibe_nombre = NULL WHERE id_envio = :id";
            $stmt2 = $db->prepare($sql2);
            $stmt2->execute(['id' => $idEnvio]);

            // 3. Reiniciar solicitudes de entrega
            $sql3 = "UPDATE lgs_solicitudes_entrega SET confirmado = 0, fecha_confirmacion = NULL, confirmado_by = NULL WHERE id_envio = :id";
            $stmt3 = $db->prepare($sql3);
            $stmt3->execute(['id' => $idEnvio]);

            // 4. Limpiar checklists de prueba
            try {
                $sql4 = "DELETE FROM lgs_trasladistas_checklist WHERE id_envio = :id";
                $stmt4 = $db->prepare($sql4);
                $stmt4->execute(['id' => $idEnvio]);
            } catch (Throwable $e) {}
        } catch (Throwable $e) {}
    }

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
                        e.fecha_tentativa_envio AS fecha_programada,
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
                    WHERE e.id_estado IN (3, 5, 6, 7) AND e.deleted_at IS NULL {$wherePlanta}
                    ORDER BY e.id_estado ASC, e.fecha_tentativa_envio ASC, e.id_envio DESC";
            
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
                        e.fecha_tentativa_envio AS fecha_programada,
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
                    WHERE e.id_estado IN (3, 5, 6, 7) AND e.deleted_at IS NULL {$wherePlanta}
                    ORDER BY e.id_estado ASC, e.fecha_tentativa_envio ASC, e.id_envio DESC";
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS lgs_evidencias (
            id_evidencia BIGINT AUTO_INCREMENT PRIMARY KEY,
            id_envio BIGINT NOT NULL,
            id_vin BIGINT DEFAULT NULL,
            tipo TINYINT DEFAULT 1,
            nombre_archivo VARCHAR(255) NOT NULL,
            tipo_archivo VARCHAR(10) DEFAULT 'jpg',
            created_by BIGINT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ev_envio (id_envio),
            KEY idx_ev_vin (id_vin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS lgs_trasladistas_checklist (
            id_checklist BIGINT AUTO_INCREMENT PRIMARY KEY,
            id_envio BIGINT NOT NULL,
            id_unidad BIGINT NOT NULL,
            tipo_checklist ENUM('entrada_trasladista','salida_planta','entrega_destino') NOT NULL DEFAULT 'entrada_trasladista',
            vin_escaneado VARCHAR(50) DEFAULT NULL,
            usuario_registro_id INT DEFAULT NULL,
            comentarios TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_chk_envio_unidad (id_envio, id_unidad)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS lgs_checklist_evidencias (
            id_evidencia BIGINT AUTO_INCREMENT PRIMARY KEY,
            id_checklist BIGINT NOT NULL,
            tipo_foto VARCHAR(50) NOT NULL,
            ruta_archivo VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_chk_ev (id_checklist)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->getConexion()->exec($sql);
    }

    /**
     * Obtiene los VINs de un envío con su orden de acomodo y estatus de solicitud de entrega
     */
    public function getVinsAcomodoConSolicitud(int $idEnvio): array
    {
        try {
            $sql = "SELECT 
                        ev.id AS id_envio_vin,
                        ev.id_unidad,
                        COALESCE(ev.posicion_acomodo, 1) AS posicion_acomodo,
                        ev.id_madrina,
                        ev.id_chofer,
                        COALESCE(ev.estado_unidad_fisico, 'EN_PATIO') AS estado_unidad_fisico,
                        COALESCE(u.vin, ut.clave, 'S/VIN') AS vin,
                        COALESCE(u.modelo, 'Unidad Terminada') AS modelo,
                        'Blanco' AS color,
                        COALESCE(m.numero_economico, 'Sin Madrina') AS madrina,
                        COALESCE(CONCAT(c.nombre, ' ', c.apellidos), 'Sin Chofer') AS chofer,
                        se.id_solicitud,
                        CASE 
                            WHEN COALESCE(se.confirmado, 0) = 1 THEN 1 
                            WHEN ev.estado_unidad_fisico IN ('EN_ENTREGAS', 'EN_RUTA', 'ENTREGADO') THEN 1 
                            ELSE 0 
                        END AS confirmado,
                        se.fecha_confirmacion,
                        se.confirmado_by,
                        (
                            (SELECT COUNT(*) FROM lgs_evidencias WHERE id_envio = ev.id_envio AND (id_vin = ev.id_unidad OR id_vin IS NULL))
                            +
                            (SELECT COUNT(*) FROM lgs_checklist_evidencias ce 
                             INNER JOIN lgs_trasladistas_checklist tc ON ce.id_checklist = tc.id_checklist 
                             WHERE tc.id_envio = ev.id_envio AND tc.id_unidad = ev.id_unidad)
                        ) AS total_evidencias
                    FROM lgs_envios_vins ev
                    LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                    LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                    LEFT JOIN prv_det_madrinas m ON ev.id_madrina = m.id_madrina
                    LEFT JOIN prv_det_choferes c ON ev.id_chofer = c.id_chofer
                    LEFT JOIN lgs_solicitudes_entrega se ON ev.id_envio = se.id_envio AND ev.id_unidad = se.id_unidad
                    WHERE ev.id_envio = :id_envio
                    ORDER BY ev.posicion_acomodo ASC, ev.id ASC";
            
            $stmt = $this->getConexion()->prepare($sql);
            $stmt->execute(['id_envio' => $idEnvio]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            // Fallback en caso de que alguna tabla opcional de catálogos o solicitudes no esté vinculada
            try {
                $sqlFallback = "SELECT 
                                    ev.id AS id_envio_vin,
                                    ev.id_unidad,
                                    COALESCE(ev.posicion_acomodo, 1) AS posicion_acomodo,
                                    ev.id_madrina,
                                    ev.id_chofer,
                                    COALESCE(ev.estado_unidad_fisico, 'EN_PATIO') AS estado_unidad_fisico,
                                    COALESCE(u.vin, 'S/VIN') AS vin,
                                    COALESCE(u.modelo, 'Unidad') AS modelo,
                                    'Blanco' AS color,
                                    'Sin Madrina' AS madrina,
                                    'Sin Chofer' AS chofer,
                                    NULL AS id_solicitud,
                                    CASE 
                                        WHEN ev.estado_unidad_fisico IN ('EN_ENTREGAS', 'EN_RUTA', 'ENTREGADO') THEN 1 
                                        ELSE 0 
                                    END AS confirmado,
                                    NULL AS fecha_confirmacion,
                                    NULL AS confirmado_by,
                                    0 AS total_evidencias
                                FROM lgs_envios_vins ev
                                LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                                WHERE ev.id_envio = :id_envio
                                ORDER BY ev.id ASC";
                $stmtF = $this->getConexion()->prepare($sqlFallback);
                $stmtF->execute(['id_envio' => $idEnvio]);
                return $stmtF->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e2) {
                return [];
            }
        }
    }

    /**
     * Registrar/Crear solicitudes de entrega para planta si no existen
     */
    public function crearSolicitudesEntrega(PDO $db, int $idEnvio): void
    {
        try {
            $sql = "INSERT INTO lgs_solicitudes_entrega (id_envio, id_unidad, orden_acomodo, confirmado)
                    SELECT id_envio, id_unidad, COALESCE(posicion_acomodo, 1), 0
                    FROM lgs_envios_vins
                    WHERE id_envio = :id_envio
                    ON DUPLICATE KEY UPDATE orden_acomodo = VALUES(orden_acomodo)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['id_envio' => $idEnvio]);
        } catch (Throwable $e) {}
    }

    /**
     * Registrar la fecha de salida real y evidencias multimedia del despacho
     */
    public function registrarDespachoEnvio(PDO $db, int $idEnvio, string $fechaSalida, ?string $evidenciasJson): void
    {
        $sql = "UPDATE lgs_envios 
                SET fecha_salida_real = :fecha_salida,
                    id_estado = 6
                WHERE id_envio = :id_envio";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'fecha_salida' => $fechaSalida,
            'id_envio' => $idEnvio
        ]);

        if (!empty($evidenciasJson)) {
            try {
                $sqlEv = "INSERT INTO lgs_envios_evidencias (id_envio, tipo_evidencia, archivos_json) 
                          VALUES (:id_envio, 'salida', :json)
                          ON DUPLICATE KEY UPDATE archivos_json = VALUES(archivos_json)";
                $stmtEv = $db->prepare($sqlEv);
                $stmtEv->execute([
                    'id_envio' => $idEnvio,
                    'json' => $evidenciasJson
                ]);
            } catch (Throwable $e) {}
        }
    }

    /**
     * Confirmar la entrega física de un VIN en planta al trasladista
     */
    public function confirmarEntregaVinPlanta(PDO $db, int $idEnvio, int $idUnidad, int $userId): void
    {
        // 1. Actualizar estado de unidad físico en lgs_envios_vins
        try {
            $sqlVin = "UPDATE lgs_envios_vins 
                       SET estado_unidad_fisico = 'EN_ENTREGAS' 
                       WHERE id_envio = :id_envio AND id_unidad = :id_unidad";
            $stmtV = $db->prepare($sqlVin);
            $stmtV->execute([
                'id_envio' => $idEnvio,
                'id_unidad' => $idUnidad
            ]);
        } catch (Throwable $e) {}

        // 2. Insertar o actualizar registro en lgs_solicitudes_entrega
        try {
            $stmtCheck = $db->prepare("SELECT id_solicitud FROM lgs_solicitudes_entrega WHERE id_envio = :id_envio AND id_unidad = :id_unidad LIMIT 1");
            $stmtCheck->execute(['id_envio' => $idEnvio, 'id_unidad' => $idUnidad]);
            $idSol = $stmtCheck->fetchColumn();

            if ($idSol) {
                $stmtUp = $db->prepare("UPDATE lgs_solicitudes_entrega SET confirmado = 1, fecha_confirmacion = NOW(), confirmado_by = :user WHERE id_solicitud = :id_sol");
                $stmtUp->execute(['user' => $userId, 'id_sol' => $idSol]);
            } else {
                $stmtIns = $db->prepare("INSERT INTO lgs_solicitudes_entrega (id_envio, id_unidad, orden_acomodo, confirmado, fecha_confirmacion, confirmado_by) VALUES (:id_envio, :id_unidad, 1, 1, NOW(), :user)");
                $stmtIns->execute(['id_envio' => $idEnvio, 'id_unidad' => $idUnidad, 'user' => $userId]);
            }
        } catch (Throwable $e) {}
    }

    /**
     * Verifica si todos los VINs de un envío ya fueron entregados en planta
     */
    public function checkTodosVinsEntregados(PDO $db, int $idEnvio): bool
    {
        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = :id1) AS total,
                        (SELECT COUNT(*) FROM lgs_solicitudes_entrega WHERE id_envio = :id2 AND confirmado = 1) AS confirmados";
            $stmt = $db->prepare($sql);
            $stmt->execute(['id1' => $idEnvio, 'id2' => $idEnvio]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            return ($res && (int)$res['total'] > 0 && (int)$res['total'] <= (int)$res['confirmados']);
        } catch (Throwable $e) {
            return false;
        }
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
     * Inserta un registro espejo en lgs_evidencias para que las fotos del chofer/móvil
     * queden unificadas y visibles en el panel general de evidencias de administración.
     */
    public function insertarEvidenciaUnificada(PDO $db, int $idEnvio, int $idUnidad, int $tipoEvidencia, string $rutaArchivo, ?string $observaciones, int $userId): void
    {
        try {
            // Columnas reales de lgs_evidencias: id_envio, id_vin, tipo, nombre_archivo, tipo_archivo, created_by
            $ext = pathinfo($rutaArchivo, PATHINFO_EXTENSION) ?: 'jpg';
            $sql = "INSERT INTO lgs_evidencias (id_envio, id_vin, tipo, nombre_archivo, tipo_archivo, created_by)
                    VALUES (:id_envio, :id_vin, :tipo, :nombre, :tipo_arch, :user)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'id_envio'   => $idEnvio,
                'id_vin'     => $idUnidad,
                'tipo'       => $tipoEvidencia,
                'nombre'     => $rutaArchivo,
                'tipo_arch'  => $ext,
                'user'       => $userId
            ]);
        } catch (Throwable $e) {
            error_log("Error insertando evidencia unificada: " . $e->getMessage());
        }
    }

    public function getEvidenciasPorUnidad(int $idEnvio, int $idUnidad): array
    {
        try {
            // Columnas reales: lgs_evidencias(id_evidencia, id_envio, id_vin, tipo, nombre_archivo, tipo_archivo, created_by, created_at)
            // Columnas reales: lgs_checklist_evidencias(id_evidencia, id_checklist, tipo_foto, ruta_archivo, created_at)
            $sql = "SELECT 
                        ce.id_evidencia,
                        1 AS tipo_evidencia,
                        ce.tipo_foto,
                        ce.ruta_archivo,
                        COALESCE(tc.comentarios, '') AS comentarios,
                        COALESCE(tc.vin_escaneado, '') AS vin_escaneado,
                        CONCAT('Foto ', UPPER(ce.tipo_foto), ' (', COALESCE(tc.vin_escaneado, 'VIN'), ')') AS observaciones,
                        ce.created_at
                    FROM lgs_checklist_evidencias ce
                    INNER JOIN lgs_trasladistas_checklist tc ON ce.id_checklist = tc.id_checklist
                    WHERE tc.id_envio = ? AND tc.id_unidad = ?
                    
                    UNION ALL
                    
                    SELECT 
                        ev.id_evidencia, 
                        ev.tipo AS tipo_evidencia, 
                        'general' AS tipo_foto,
                        ev.nombre_archivo AS ruta_archivo, 
                        '' AS comentarios,
                        '' AS vin_escaneado,
                        CONCAT('Evidencia tipo ', ev.tipo) AS observaciones, 
                        ev.created_at 
                    FROM lgs_evidencias ev
                    WHERE ev.id_envio = ? AND (ev.id_vin = ? OR ev.id_vin IS NULL)
                    
                    ORDER BY created_at DESC";
            
            $stmt = $this->getConexion()->prepare($sql);
            $stmt->execute([$idEnvio, $idUnidad, $idEnvio, $idUnidad]);
            $rawRes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Buscar comentarios del checklist si alguna fila los tiene o consultarlos
            $comentarioGeneral = '';
            foreach ($rawRes as $r) {
                if (!empty($r['comentarios'])) {
                    $comentarioGeneral = $r['comentarios'];
                    break;
                }
            }

            if (empty($comentarioGeneral)) {
                $sqlComent = "SELECT comentarios FROM lgs_trasladistas_checklist WHERE id_envio = ? AND id_unidad = ? ORDER BY id_checklist DESC LIMIT 1";
                $stmtC = $this->getConexion()->prepare($sqlComent);
                $stmtC->execute([$idEnvio, $idUnidad]);
                $cRow = $stmtC->fetch(PDO::FETCH_ASSOC);
                if ($cRow && !empty($cRow['comentarios'])) {
                    $comentarioGeneral = $cRow['comentarios'];
                }
            }

            // Eliminar duplicados priorizando la descripción detallada ('Foto...') sobre la genérica ('Evidencia tipo...')
            $dedup = [];
            foreach ($rawRes as $row) {
                $ruta = $row['ruta_archivo'];
                $row['comentarios'] = $comentarioGeneral;
                if (!isset($dedup[$ruta])) {
                    $dedup[$ruta] = $row;
                } else {
                    if (strpos($row['observaciones'], 'Foto') !== false) {
                        $dedup[$ruta] = $row;
                    }
                }
            }
            $res = array_values($dedup);

            // Fallback: si no se encontraron por id_unidad, buscar por id_envio
            if (empty($res)) {
                $sqlFallback = "SELECT id_evidencia, tipo AS tipo_evidencia, 'general' AS tipo_foto, nombre_archivo AS ruta_archivo, 
                                       ? AS comentarios, '' AS vin_escaneado,
                                       CONCAT('Evidencia tipo ', tipo) AS observaciones, created_at 
                                FROM lgs_evidencias 
                                WHERE id_envio = ? 
                                ORDER BY created_at DESC";
                $stmtF = $this->getConexion()->prepare($sqlFallback);
                $stmtF->execute([$comentarioGeneral, $idEnvio]);
                $res = $stmtF->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            return $res;
        } catch (Throwable $e) {
            error_log('getEvidenciasPorUnidad error: ' . $e->getMessage());
            return [];
        }
    }

    public function revertirConfirmacionVin(PDO $db, int $idEnvio, int $idUnidad): bool
    {
        // 1. Regresar el vin a 'EN_PATIO'
        $sql = "UPDATE lgs_envios_vins 
                SET estado_unidad_fisico = 'EN_PATIO' 
                WHERE id_envio = ? AND id_unidad = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$idEnvio, $idUnidad]);

        // 2. Revertir la solicitud de entrega si existe
        try {
            $sql2 = "UPDATE lgs_solicitudes_entrega 
                     SET confirmado = 0, fecha_confirmacion = NULL, confirmado_by = NULL 
                     WHERE id_envio = ? AND id_unidad = ?";
            $stmt2 = $db->prepare($sql2);
            $stmt2->execute([$idEnvio, $idUnidad]);
        } catch (Throwable $e) {}

        // 3. Eliminar los registros unificados de evidencias
        try {
            $sql3 = "DELETE FROM lgs_evidencias WHERE id_envio = ? AND id_vin = ?";
            $stmt3 = $db->prepare($sql3);
            $stmt3->execute([$idEnvio, $idUnidad]);
        } catch (Throwable $e) {}

        // 4. Eliminar las fotos específicas del checklist (usando subquery)
        try {
            $sql4 = "DELETE FROM lgs_checklist_evidencias 
                     WHERE id_checklist IN (
                         SELECT id_checklist FROM lgs_trasladistas_checklist 
                         WHERE id_envio = ? AND id_unidad = ?
                     )";
            $stmt4 = $db->prepare($sql4);
            $stmt4->execute([$idEnvio, $idUnidad]);
        } catch (Throwable $e) {}

        // 5. Eliminar el registro base del checklist (que contiene los comentarios)
        try {
            $sql5 = "DELETE FROM lgs_trasladistas_checklist WHERE id_envio = ? AND id_unidad = ?";
            $stmt5 = $db->prepare($sql5);
            $stmt5->execute([$idEnvio, $idUnidad]);
        } catch (Throwable $e) {}

        return $result;
    }


    /**
     * Valida si un texto QR escaneado coincide con el destino, folio o cliente de un envío.
     */
    public function validarQrContraDestinoEnvio(int $idEnvio, string $textoQr): array
    {
        $textoQr = trim($textoQr);
        if (empty($textoQr)) {
            return ['valido' => false, 'mensaje' => 'Código QR vacío.'];
        }

        // 1. Verificar coincidencia directa con el folio del envío
        $sqlFolio = "SELECT folio FROM lgs_envios WHERE id_envio = :id AND (LOWER(folio) = LOWER(:qr) OR :qr LIKE CONCAT('%', folio, '%'))";
        $stmtF = $this->getConexion()->prepare($sqlFolio);
        $stmtF->execute(['id' => $idEnvio, 'qr' => $textoQr]);
        if ($stmtF->fetch()) {
            return ['valido' => true, 'mensaje' => 'Validado por Folio de Envío'];
        }

        // 2. Verificar coincidencia con los destinos del envío (catálogo o nombre libre)
        $sqlDest = "SELECT DISTINCT d.nombre, ep.destino_nombre_libre, e.destino_nombre_libre AS dest_envio
                    FROM lgs_envios e
                    LEFT JOIN lgs_envios_vins ev ON e.id_envio = ev.id_envio
                    LEFT JOIN lgs_cat_destinos d ON (ev.id_destino = d.id_destino OR e.id_destino = d.id_destino)
                    LEFT JOIN lgs_envios_paradas ep ON ep.id_envio = e.id_envio
                    WHERE e.id_envio = :id";
        $stmtD = $this->getConexion()->prepare($sqlDest);
        $stmtD->execute(['id' => $idEnvio]);
        $destinos = $stmtD->fetchAll(PDO::FETCH_ASSOC);

        $qrLower = mb_strtolower($textoQr, 'UTF-8');
        foreach ($destinos as $row) {
            $candidatos = array_filter([$row['nombre'] ?? '', $row['destino_nombre_libre'] ?? '', $row['dest_envio'] ?? '']);
            foreach ($candidatos as $cand) {
                $candLower = mb_strtolower(trim($cand), 'UTF-8');
                if (!empty($candLower) && (strpos($qrLower, $candLower) !== false || strpos($candLower, $qrLower) !== false || levenshtein($candLower, $qrLower) <= 3)) {
                    return ['valido' => true, 'mensaje' => 'Destino validado: ' . $cand];
                }
            }
        }

        // 3. Si el QR trae un token, ID de cliente o texto estructurado (ej. JSON o KEY:VAL)
        if (json_decode($textoQr, true)) {
            $json = json_decode($textoQr, true);
            if (isset($json['id_envio']) && intval($json['id_envio']) === $idEnvio) {
                return ['valido' => true, 'mensaje' => 'Token de Envío validado'];
            }
            if (isset($json['folio']) && !empty($json['folio'])) {
                $stmtF->execute(['id' => $idEnvio, 'qr' => $json['folio']]);
                if ($stmtF->fetch()) return ['valido' => true, 'mensaje' => 'Folio validado desde QR'];
            }
        }

        // 4. Si el QR contiene algún VIN del envío
        $sqlVin = "SELECT u.vin, ut.clave AS vin_alt 
                   FROM lgs_envios_vins ev
                   LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                   LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                   WHERE ev.id_envio = :id";
        $stmtV = $this->getConexion()->prepare($sqlVin);
        $stmtV->execute(['id' => $idEnvio]);
        $vins = $stmtV->fetchAll(PDO::FETCH_ASSOC);
        foreach ($vins as $v) {
            $vCandidate = trim($v['vin'] ?? $v['vin_alt'] ?? '');
            if (!empty($vCandidate) && (stripos($textoQr, $vCandidate) !== false)) {
                return ['valido' => true, 'mensaje' => 'VIN de la unidad validado: ' . $vCandidate];
            }
        }

        return ['valido' => false, 'mensaje' => 'El código QR escaneado no corresponde al destino, folio o unidades de este envío.'];
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
