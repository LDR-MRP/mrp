<?php

require_once "Models/Lgs_ejecucionModel.php";
require_once "Services/Lgs_notificacionesHelper.php";

class Lgs_ejecucionService {

    private Lgs_ejecucionModel $model;

    public function __construct() {
        $this->model = new Lgs_ejecucionModel();
    }

    public function getEnviosDespacho(?int $plantaId = null): array {
        return $this->model->getEnviosParaEjecucion($plantaId);
    }

    public function getDetalleDespacho(int $idEnvio): array {
        return $this->model->getVinsAcomodoConSolicitud($idEnvio);
    }

    public function resetEnvioParaPrueba(int $idEnvio = 16): void {
        $this->model->resetEnvioParaPrueba($idEnvio);
    }

    /**
     * Confirma la fecha de recolección programada por el administrativo
     */
    public function confirmarFechaRecoleccion(int $idEnvio, string $fechaRecoleccion): void {
        $db = $this->model->getConexion();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }
            $this->model->confirmarFechaRecoleccion($db, $idEnvio, $fechaRecoleccion);
            if ($db->inTransaction()) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Registra la salida física/despacho del envío y genera las solicitudes para planta
     */
    public function registrarDespacho(int $idEnvio, string $fechaSalida, ?string $evidenciasJson, int $userId): void {
        $this->model->asegurarColumnasDestino();
        $db = $this->model->getConexion();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // Validar confirmación previa de cliente/distribuidor (Bloqueo estricto para evitar viajes fallidos)
            $stmtConf = $db->prepare("SELECT confirmado_destino, folio, id_proveedor FROM lgs_envios WHERE id_envio = ?");
            $stmtConf->execute([$idEnvio]);
            $envioConf = $stmtConf->fetch(PDO::FETCH_ASSOC);
            if ($envioConf && (int)$envioConf['confirmado_destino'] !== 1) {
                throw new Exception("Bloqueo de Salida: El envío {$envioConf['folio']} no cuenta con cita/confirmación de recepción del cliente o distribuidor. Confirme la cita antes de despachar.");
            }

            // Validar que el envío cuente con trasladista/proveedor asignado
            if (empty($envioConf['id_proveedor'])) {
                throw new Exception("Bloqueo de Salida: El envío no cuenta con empresa trasladista o chofer asignado.");
            }

            // Validar que el envío contenga al menos una unidad (VIN)
            $stmtVins = $db->prepare("SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = ?");
            $stmtVins->execute([$idEnvio]);
            if ((int)$stmtVins->fetchColumn() === 0) {
                throw new Exception("Bloqueo de Salida: El envío no contiene unidades (VINs) asignadas.");
            }

            // 1. Guardar fecha de salida real y evidencias
            $this->model->registrarDespachoEnvio($db, $idEnvio, $fechaSalida, $evidenciasJson);

            // 2. Generar las solicitudes de entrega para el área de planta/almacén con el orden de acomodo
            $this->model->crearSolicitudesEntrega($db, $idEnvio);

            if ($db->inTransaction()) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Registra la confirmación de cita / recepción por parte del cliente o distribuidor
     */
    public function confirmarCitaDestino(int $idEnvio, string $contacto, string $fechaCita, string $notas, int $userId): void
    {
        $this->model->asegurarColumnasDestino();
        $db = $this->model->getConexion();
        $stmt = $db->prepare("UPDATE lgs_envios SET 
            confirmado_destino = 1,
            confirmado_destino_contacto = :contacto,
            confirmado_destino_fecha_cita = :cita,
            confirmado_destino_notas = :notas,
            confirmado_destino_by = :by_usr,
            confirmado_destino_at = NOW()
            WHERE id_envio = :id");
        $stmt->execute([
            'contacto' => $contacto,
            'cita'     => $fechaCita,
            'notas'    => $notas,
            'by_usr'   => $userId,
            'id'       => $idEnvio
        ]);
    }

    /**
     * Confirma la entrega física de un VIN individual a la madrina/chofer en planta
     */
    public function confirmarSalidaVin(int $idEnvio, int $idUnidad, int $userId): bool {
        $db = $this->model->getConexion();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // 1. Confirmar entrega del VIN
            $this->model->confirmarEntregaVinPlanta($db, $idEnvio, $idUnidad, $userId);

            // 2. Comprobar si ya se entregaron TODOS los VINs del envío
            $todosCompletos = $this->model->checkTodosVinsEntregados($db, $idEnvio);

            // 3. Si ya salieron todas las unidades, el envío pasa automáticamente a Estado 6 (En Tránsito)
            if ($todosCompletos) {
                $this->model->updateEstadoEnvio($db, $idEnvio, 6);
                $this->model->updateEstadoUnidadesFisico($db, $idEnvio, 'EN_RUTA');

                // Notificar a logística que se terminó de cargar la última unidad en la madrina
                $this->notificarCargaCompletaLogistica($db, $idEnvio, $userId);
            }

            if ($db->inTransaction()) {
                $db->commit();
            }
            return $todosCompletos;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene envíos asignados al chofer trasladista
     */
    public function getEnviosChofer(int $idChofer): array {
        return $this->model->getEnviosPorChofer($idChofer);
    }

    /**
     * Registra checklist digital con evidencias fotográficas
     */
    public function registrarChecklistTrasladista(int $idEnvio, int $idUnidad, string $tipoChecklist, string $vin, int $userId, ?string $comentarios, array $fotosFiles, ?array $zipFile = null): void {
        $db = $this->model->getConexion();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // 1. Crear registro de checklist
            $idChecklist = $this->model->registrarChecklist($db, $idEnvio, $idUnidad, $tipoChecklist, $vin, $userId, $comentarios);

            // 2. Crear directorio de evidencias si no existe y asegurar permisos de escritura
            $uploadDir = 'Assets/images/uploads/evidencias/';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("No se pudo crear el directorio de evidencias: " . $uploadDir);
                }
            }
            if (file_exists($uploadDir) && !is_writable($uploadDir)) {
                @chmod($uploadDir, 0777);
            }

            // 3. Procesar y guardar cada archivo fotográfico (en checklist y sincronizado en lgs_evidencias)
            $tipoEvidenciaNum = ($tipoChecklist === 'entrega_destino') ? 2 : 1; // 1=Salida/Patio, 2=Llegada/Destino

            // Soporte de extracción masiva vía ZIP
            if ($zipFile !== null && isset($zipFile['tmp_name']) && file_exists($zipFile['tmp_name'])) {
                require_once 'Services/Lgs_zipEvidenciasHelper.php';
                $zipHelper = new Lgs_zipEvidenciasHelper();
                $prefix = 'ev_' . $idChecklist . '_';
                $context = ($tipoChecklist === 'entrega_destino') ? 'entrega_destino' : 'inspeccion';
                $fotosExtraidas = $zipHelper->procesarZip($zipFile, $uploadDir, $prefix, $context);

                foreach ($fotosExtraidas as $f) {
                    $this->model->registrarChecklistEvidencia($db, $idChecklist, $f['key'], $f['destFile']);
                    $obsEvidencia = "Foto " . ucfirst($f['key']) . " (" . $vin . ")" . ($comentarios ? " - " . $comentarios : "");
                    $this->model->insertarEvidenciaUnificada($db, $idEnvio, $idUnidad, $tipoEvidenciaNum, $f['destFile'], $obsEvidencia, $userId);
                }
            }

            if (empty($fotosFiles) && $zipFile === null) {
                error_log("Checklist sin fotos subidas en _FILES o con error (error > 0) para el VIN: $vin");
            }

            foreach ($fotosFiles as $key => $file) {
                if (isset($file['tmp_name']) && !empty($file['tmp_name'])) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = 'ev_' . $idChecklist . '_' . $key . '_' . md5(uniqid()) . '.' . $ext;
                    $destFile = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $destFile)) {
                        @chmod($destFile, 0666);
                        $this->model->registrarChecklistEvidencia($db, $idChecklist, $key, $destFile);
                        
                        // Guardar en tabla unificada de evidencias
                        $obsEvidencia = "Foto " . ucfirst($key) . " (" . $vin . ")" . ($comentarios ? " - " . $comentarios : "");
                        $this->model->insertarEvidenciaUnificada($db, $idEnvio, $idUnidad, $tipoEvidenciaNum, $destFile, $obsEvidencia, $userId);
                    } else {
                        error_log("Fallo move_uploaded_file para " . $file['tmp_name'] . " hacia " . $destFile);
                        throw new Exception("Error al guardar la fotografía '{$key}'. Verifique los permisos de escritura del servidor.");
                    }
                }
            }

            // 4. Actualizar estados físicos según el tipo de checklist
            // 4. Actualizar estados físicos y odómetros según el tipo de checklist
            $valorOdometro = (isset($_POST['valor_odometro']) && is_numeric($_POST['valor_odometro'])) ? intval($_POST['valor_odometro']) : null;

            if ($tipoChecklist === 'entrada_trasladista') {
                // Al hacer el trasladista su checklist de recolección individual
                $sqlUpVin = "UPDATE lgs_envios_vins SET estado_unidad_fisico = 'EN_ENTREGAS'" . ($valorOdometro !== null ? ", odometro_inicial = ?" : "") . " WHERE id_envio = ? AND id_unidad = ?";
                $stmt = $db->prepare($sqlUpVin);
                $paramsUp = ($valorOdometro !== null) ? [$valorOdometro, $idEnvio, $idUnidad] : [$idEnvio, $idUnidad];
                $stmt->execute($paramsUp);
            } elseif ($tipoChecklist === 'salida_planta' || $tipoChecklist === 'recibo_trasladista') {
                // Al confirmar salida de planta o recibo de trasladista
                $sqlUpVin = "UPDATE lgs_envios_vins SET estado_unidad_fisico = 'EN_RUTA'" . ($valorOdometro !== null ? ", odometro_inicial = ?" : "") . " WHERE id_envio = ? AND id_unidad = ?";
                $stmt = $db->prepare($sqlUpVin);
                $paramsUp = ($valorOdometro !== null) ? [$valorOdometro, $idEnvio, $idUnidad] : [$idEnvio, $idUnidad];
                $stmt->execute($paramsUp);
            }

            // Comprobar si se terminó de cargar la última unidad en la plataforma/madrina
            if ($tipoChecklist === 'salida_planta' || $tipoChecklist === 'entrada_trasladista' || $tipoChecklist === 'recibo_trasladista') {
                $sqlCheckCarga = "SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = ? AND (estado_unidad_fisico NOT IN ('EN_RUTA', 'EN_ENTREGAS', 'ENTREGADO') OR estado_unidad_fisico IS NULL)";
                $stmtCheckCarga = $db->prepare($sqlCheckCarga);
                $stmtCheckCarga->execute([$idEnvio]);
                $pendientesCarga = (int)$stmtCheckCarga->fetchColumn();

                if ($pendientesCarga === 0) {
                    // Pasar el viaje a EN TRÁNSITO (6) si aún no lo estaba
                    $this->model->updateEstadoEnvio($db, $idEnvio, 6);
                    // Disparar notificación a Logística
                    $this->notificarCargaCompletaLogistica($db, $idEnvio, $vin, $userId);
                }
            } elseif ($tipoChecklist === 'entrega_destino') {
                // Al confirmar entrega en destino
                $sqlUpVin = "UPDATE lgs_envios_vins SET estado_unidad_fisico = 'ENTREGADO', fecha_entrega_real = NOW()" . ($valorOdometro !== null ? ", odometro_final = ?" : "") . " WHERE id_envio = ? AND id_unidad = ?";
                $stmt = $db->prepare($sqlUpVin);
                $paramsUp = ($valorOdometro !== null) ? [$valorOdometro, $idEnvio, $idUnidad] : [$idEnvio, $idUnidad];
                $stmt->execute($paramsUp);

                // Sincronizar lgs_unidades (Bandeja de Unidades) a id_estado_proceso = 3 (Entregado)
                $stmtUpUnidad = $db->prepare("UPDATE lgs_unidades SET id_estado_proceso = 3, fecha_llegada = NOW(), updated_by = ?, updated_at = NOW() WHERE id_unidad = ?");
                $stmtUpUnidad->execute([$userId, $idUnidad]);

                // Actualizar estatus en lgs_unidades_envios
                $db->prepare("UPDATE lgs_unidades_envios SET estatus = 'entregado' WHERE id_unidad = ?")->execute([$idUnidad]);

                // Comprobar si ya se entregaron todos para finalizar el envío
                $sqlCheck = "SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = ? AND (estado_unidad_fisico != 'ENTREGADO' OR estado_unidad_fisico IS NULL)";
                $stmtCheck = $db->prepare($sqlCheck);
                $stmtCheck->execute([$idEnvio]);
                $restantes = (int)$stmtCheck->fetchColumn();

                if ($restantes === 0) {
                    $this->model->updateEstadoEnvio($db, $idEnvio, 7); // 7 = Entregado
                    $db->prepare("UPDATE lgs_envios SET fecha_llegada_real = NOW() WHERE id_envio = ?")->execute([$idEnvio]);
                }
            }

            if ($db->inTransaction()) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Valida el código QR escaneado en destino contra los datos reales del viaje.
     */
    public function validarQrCliente(int $idEnvio, string $textoQr): array {
        return $this->model->validarQrContraDestinoEnvio($idEnvio, $textoQr);
    }

    public function getEvidenciasPorUnidad(int $idEnvio, int $idUnidad): array {
        return $this->model->getEvidenciasPorUnidad($idEnvio, $idUnidad);
    }

    public function revertirConfirmacionVin(int $idEnvio, int $idUnidad): bool {
        $db = $this->model->getConexion();
        if (!$db->inTransaction()) {
            $db->beginTransaction();
        }
        try {
            // Eliminar archivos físicos antes de borrar los registros
            $evidencias = $this->model->getEvidenciasPorUnidad($idEnvio, $idUnidad);
            foreach ($evidencias as $ev) {
                if (!empty($ev['ruta_archivo']) && file_exists($ev['ruta_archivo'])) {
                    unlink($ev['ruta_archivo']);
                }
            }

            $res = $this->model->revertirConfirmacionVin($db, $idEnvio, $idUnidad);
            $db->commit();
            return $res;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Finaliza la entrega en destino (cierre del viaje, remisión, firma y paso a Estado 7)
     */
    public function finalizarEntregaDestino(int $idEnvio, string $nombreRecibe, string $comentarios, array $fotosFiles, int $userId): void {
        $nombreRecibe = trim($nombreRecibe);
        if (empty($nombreRecibe)) {
            throw new Exception("El nombre completo de la persona que recibe en destino es obligatorio para cerrar la entrega.");
        }

        $db = $this->model->getConexion();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // Validar que se adjunte al menos un documento o ya existan evidencias de recepción
            $stmtCheckEvid = $db->prepare("SELECT COUNT(*) FROM lgs_evidencias WHERE id_envio = ? AND tipo_evidencia = 2");
            $stmtCheckEvid->execute([$idEnvio]);
            $totalEvidenciasDestino = (int)$stmtCheckEvid->fetchColumn();

            $subioArchivo = false;
            foreach ($fotosFiles as $file) {
                if (isset($file['tmp_name']) && !empty($file['tmp_name'])) {
                    $subioArchivo = true;
                    break;
                }
            }

            if (!$subioArchivo && $totalEvidenciasDestino === 0) {
                throw new Exception("Bloqueo de Cierre: Es obligatorio adjuntar la fotografía de la remisión sellada o firma del receptor para finalizar la entrega.");
            }

            // 1. Guardar archivos de remisión y firma
            $uploadDir = 'Assets/images/uploads/evidencias/';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("No se pudo crear el directorio: " . $uploadDir);
                }
            }
            if (file_exists($uploadDir) && !is_writable($uploadDir)) {
                @chmod($uploadDir, 0777);
            }

            foreach ($fotosFiles as $key => $file) {
                if (isset($file['tmp_name']) && !empty($file['tmp_name'])) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                    $fileName = 'entrega_' . $idEnvio . '_' . $key . '_' . md5(uniqid()) . '.' . $ext;
                    $destFile = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $destFile)) {
                        @chmod($destFile, 0666);
                        $obs = ($key === 'evidencia_remision') 
                            ? "Remisión Sellada - Recibe: {$nombreRecibe}" 
                            : "Firma / Foto Receptor - {$nombreRecibe}";
                        if (!empty($comentarios)) {
                            $obs .= " | " . $comentarios;
                        }
                        $this->model->insertarEvidenciaUnificada($db, $idEnvio, 0, 2, $destFile, $obs, $userId);
                    } else {
                        error_log("Fallo move_uploaded_file para " . $file['tmp_name'] . " hacia " . $destFile);
                        throw new Exception("Error al guardar la fotografía de entrega '{$key}'. Verifique permisos en el servidor.");
                    }
                }
            }

            // 2. Marcar todos los VINs del envío como ENTREGADO
            $stmtVins = $db->prepare("
                UPDATE lgs_envios_vins 
                SET estado_unidad_fisico = 'ENTREGADO', 
                    fecha_entrega_real = COALESCE(fecha_entrega_real, NOW()),
                    recibe_nombre = ?
                WHERE id_envio = ?
            ");
            $stmtVins->execute([$nombreRecibe, $idEnvio]);

            // 3. Pasar envío a Estado 7 (Entregado / Finalizado) y fijar fecha_llegada_real
            $stmtEnvio = $db->prepare("
                UPDATE lgs_envios 
                SET id_estado = 7, 
                    fecha_llegada_real = NOW()
                WHERE id_envio = ?
            ");
            $stmtEnvio->execute([$idEnvio]);

            // 4. Liberar unidades en lgs_unidades (cambiar id_estado_proceso a 3 = Entregado)
            $stmtIds = $db->prepare("SELECT id_unidad FROM lgs_envios_vins WHERE id_envio = ?");
            $stmtIds->execute([$idEnvio]);
            $unitIds = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($unitIds)) {
                $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
                $stmtUnidades = $db->prepare("UPDATE lgs_unidades SET id_estado_proceso = 3, updated_at = NOW() WHERE id_unidad IN ({$placeholders})");
                $stmtUnidades->execute($unitIds);
            }

            if ($db->inTransaction()) {
                $db->commit();
            }

            // Notificación de confirmación de entrega a Comercial (Punto 2 de Minuta)
            $this->notificarEntregaDestinoAComercial($db, $idEnvio, $nombreRecibe, $comentarios, $userId);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Dispara notificación de confirmación de entrega al área Comercial (Punto 2 de la Minuta)
     * Regla Minuta: Mandar confirmación de entrega a comercial no a distribuidor... para saber a dónde van.
     */
    private function notificarEntregaDestinoAComercial(PDO $db, int $idEnvio, string $nombreRecibe, string $comentarios, int $userId): void
    {
        try {
            $sql = "SELECT 
                        e.folio,
                        COALESCE(d.nombre, ub.nombre, 'Destino Autorizado') AS destino,
                        COALESCE(dist.nombre, 'Distribuidor Comercial') AS distribuidor,
                        pr.razon_social AS trasladista,
                        CONCAT(COALESCE(ch.nombre, ''), ' ', COALESCE(ch.apellidos, '')) AS chofer,
                        COALESCE(m.numero_economico, m.placas, '-') AS madrina,
                        e.fecha_llegada_real
                    FROM lgs_envios e
                    LEFT JOIN lgs_cat_destinos d ON e.id_destino = d.id_destino
                    LEFT JOIN lgs_cat_ubicaciones ub ON e.id_destino = ub.id_ubicacion
                    LEFT JOIN lgs_cat_distribuidores dist ON e.id_distribuidor = dist.id_distribuidor
                    LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                    LEFT JOIN prv_det_choferes ch ON e.id_chofer = ch.id_chofer
                    LEFT JOIN prv_det_madrinas m ON e.id_madrina = m.id_madrina
                    WHERE e.id_envio = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idEnvio]);
            $envio = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$envio) return;

            // Consultar VINs entregados
            $stmtVins = $db->prepare("
                SELECT ev.vin, COALESCE(u.modelo, '-') AS modelo, COALESCE(u.color, '-') AS color, ev.recibe_nombre
                FROM lgs_envios_vins ev
                LEFT JOIN lgs_unidades u ON ev.id_unidad = u.id_unidad
                WHERE ev.id_envio = ?
            ");
            $stmtVins->execute([$idEnvio]);
            $vinsList = $stmtVins->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $totalVins = count($vinsList);

            $folio = $envio['folio'] ?? "EN-" . str_pad((string)$idEnvio, 6, '0', STR_PAD_LEFT);
            $destino = $envio['destino'] ?? '-';
            $distribuidor = $envio['distribuidor'] ?? '-';
            $trasladista = $envio['trasladista'] ?? '-';
            $chofer = trim($envio['chofer'] ?? '') ?: 'Chofer Asignado';
            $madrina = $envio['madrina'] ?? '-';
            $fechaEntrega = date('Y-m-d H:i');
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

            $destComercial = Lgs_notificacionesHelper::getCorreosComercial($db);
            if (empty($destComercial)) return;

            // Armar tabla de VINs entregados
            $filasVins = '';
            foreach ($vinsList as $v) {
                $filasVins .= "<tr style='border-bottom: 1px solid #E2E8F0;'>
                    <td style='padding: 6px 8px; font-family: monospace; font-weight: bold;'>{$v['vin']}</td>
                    <td style='padding: 6px 8px;'>{$v['modelo']}</td>
                    <td style='padding: 6px 8px;'>{$v['color']}</td>
                </tr>";
            }

            $asunto = "🏁 CONFIRMACIÓN DE ENTREGA: Envío {$folio} Recibido en {$destino} ({$totalVins} VINs)";
            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: #0284C7; color: #ffffff; padding: 22px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>Confirmación de Entrega en Destino</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio Envío: <strong>{$folio}</strong></p>
                </div>
                <div style='padding: 24px; background-color: #ffffff;'>
                    <p style='font-size: 14px; color: #334155;'>
                        Estimado equipo Comercial,<br><br>
                        Se confirma que el traslado de unidades correspondiente al envío <strong>{$folio}</strong> ha concluido satisfactoriamente con la entrega física en destino final.
                    </p>
                    <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Destino:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'><strong>{$destino}</strong> ({$distribuidor})</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Recibido por:</strong></td>
                            <td style='padding: 8px 0; color: #0284C7; font-weight: bold;'>{$nombreRecibe}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Fecha / Hora Entrega:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$fechaEntrega}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Trasladista / Chofer:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$trasladista} · {$chofer} (Placas: {$madrina})</td>
                        </tr>
                        " . (!empty($comentarios) ? "
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Comentarios de Entrega:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$comentarios}</td>
                        </tr>" : "") . "
                    </table>

                    <h4 style='margin: 20px 0 10px 0; font-size: 14px; color: #0F172A;'>Unidades Entregadas ({$totalVins} VINs):</h4>
                    <table style='width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 20px;'>
                        <thead>
                            <tr style='background-color: #F8FAFC; text-align: left; border-bottom: 2px solid #E2E8F0;'>
                                <th style='padding: 6px 8px; color: #475569;'>VIN</th>
                                <th style='padding: 6px 8px; color: #475569;'>Modelo</th>
                                <th style='padding: 6px 8px; color: #475569;'>Color</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$filasVins}
                        </tbody>
                    </table>

                    <div style='text-align: center; margin-top: 24px;'>
                        <a href='{$baseUrl}/Lgs_evidencias' style='background-color: #0284C7; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-block;'>
                            Consultar Evidencias y Remisiones en Sistema
                        </a>
                    </div>
                </div>
            </div>";

            Lgs_notificacionesHelper::enviar($destComercial, $asunto, $html);
        } catch (Throwable $e) {
            error_log("Error al notificar entrega a comercial: " . $e->getMessage());
        }
    }

    /**
     * Envía notificación a Logística en cuanto se termine de cargar la última unidad en la madrina/chofer
     */
    private function notificarCargaCompletaLogistica(PDO $db, int $idEnvio, int $userId): void
    {
        try {
            $sql = "SELECT 
                        e.folio,
                        COALESCE(u.nombre, o.nombre, 'Sede Origen') AS origen,
                        pr.razon_social AS trasladista,
                        CONCAT(COALESCE(ch.nombre, ''), ' ', COALESCE(ch.apellidos, '')) AS chofer,
                        COALESCE(m.numero_economico, m.placas, 'Madrina General') AS madrina,
                        (SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = e.id_envio) AS total_vins
                    FROM lgs_envios e
                    LEFT JOIN lgs_cat_ubicaciones u ON e.id_origen = u.id_ubicacion
                    LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
                    LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor
                    LEFT JOIN prv_det_choferes ch ON e.id_chofer = ch.id_chofer
                    LEFT JOIN prv_det_madrinas m ON e.id_madrina = m.id_madrina
                    WHERE e.id_envio = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idEnvio]);
            $envio = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$envio) return;

            $folio = $envio['folio'] ?? "EN-" . str_pad((string)$idEnvio, 6, '0', STR_PAD_LEFT);
            $origen = $envio['origen'] ?? '-';
            $trasladista = $envio['trasladista'] ?? '-';
            $chofer = trim($envio['chofer'] ?? '') ?: 'Chofer Asignado';
            $madrina = $envio['madrina'] ?? '-';
            $totalVins = $envio['total_vins'] ?? 0;
            $fechaSalida = date('Y-m-d H:i');

            // Consultar correos del equipo de Logística (rolid 1: Admin, 65: Planeador logistica, 67: Logística Planta, 70: Admin Logística)
            $stmtMails = $db->query("SELECT email_user FROM usuarios WHERE rolid IN (1, 65, 67, 70) AND status = 1 AND email_user IS NOT NULL");
            $correos = $stmtMails->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $destinatarios = array_filter(array_unique($correos), function($e) {
                return filter_var(trim($e), FILTER_VALIDATE_EMAIL);
            });

            if (defined('MAIL_WEBMASTER') && filter_var(MAIL_WEBMASTER, FILTER_VALIDATE_EMAIL)) {
                $destinatarios[] = MAIL_WEBMASTER;
            }

            if (empty($destinatarios)) return;

            $asunto = "🚛 CARGA COMPLETA: Envío {$folio} en Tránsito ({$totalVins} VINs)";
            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: #0284C7; color: #ffffff; padding: 22px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>Carga Finalizada - Envío en Ruta</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio: <strong>{$folio}</strong></p>
                </div>
                <div style='padding: 24px; background-color: #ffffff;'>
                    <p style='font-size: 14px; color: #334155;'>
                        Se ha confirmado la carga de la <strong>última unidad</strong> en la plataforma/madrina. El envío ha cambiado automáticamente a estado <strong>EN TRÁNSITO</strong> y ha iniciado su recorrido.
                    </p>
                    <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Folio Envío:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'><strong>{$folio}</strong></td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Sede Origen:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$origen}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Unidades Cargadas:</strong></td>
                            <td style='padding: 8px 0; color: #0284C7; font-weight: bold;'>{$totalVins} VINs en Madrina</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Trasladista:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$trasladista}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Chofer y Unidad:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$chofer} · Madrina/Placas: <strong>{$madrina}</strong></td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Fecha / Hora Salida:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$fechaSalida}</td>
                        </tr>
                    </table>
                    <div style='text-align: center; margin-top: 24px;'>
                        <a href='" . BASE_URL . "/Lgs_ejecucion' style='background-color: #0284C7; color: #ffffff; text-decoration: none; padding: 10px 24px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-block;'>
                            Monitorear en Mesa de Despacho
                        </a>
                    </div>
                </div>
            </div>";

            if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') && defined('MAIL_HOST') && !empty(MAIL_HOST)) {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USER;
                $mail->Password   = MAIL_PASS;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = MAIL_PORT;
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom(MAIL_USER, 'MRP Logística');
                foreach ($destinatarios as $correo) {
                    $mail->addAddress($correo);
                }
                $mail->isHTML(true);
                $mail->Subject = $asunto;
                $mail->Body    = $html;
                $mail->send();
            } else {
                $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: no-reply@ldrsolutions.com.mx\r\n";
                @mail(implode(',', $destinatarios), $asunto, $html, $headers);
            }
        } catch (Throwable $e) {
            error_log("Error al notificar carga completa a logística: " . $e->getMessage());
        }
    }

    /**
     * Resuelve el VIN de una unidad dada para evitar fallos si el formulario no lo envía explícito
     */
    public function obtenerVinUnidad(int $idUnidad): ?string
    {
        try {
            $db = $this->model->getConexion();
            $stmt = $db->prepare("
                SELECT COALESCE(u.vin, ut.clave, lu.vin, ev.vin) as vin
                FROM lgs_envios_vins ev
                LEFT JOIN lgs_unidades_envios u ON ev.id_unidad = u.id_unidad
                LEFT JOIN mrp_unidades_terminadas ut ON ev.id_unidad = ut.idunidad
                LEFT JOIN lgs_unidades lu ON ev.id_unidad = lu.id_unidad
                WHERE ev.id_unidad = ?
                LIMIT 1
            ");
            $stmt->execute([$idUnidad]);
            $vin = $stmt->fetchColumn();
            return $vin ? (string)$vin : null;
        } catch (Throwable $e) {
            error_log("Error al obtenerVinUnidad: " . $e->getMessage());
            return null;
        }
    }
}
