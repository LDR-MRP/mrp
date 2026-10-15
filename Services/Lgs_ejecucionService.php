<?php

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
        $db = $this->model->getConexion();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
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
    public function registrarChecklistTrasladista(int $idEnvio, int $idUnidad, string $tipoChecklist, string $vin, int $userId, ?string $comentarios, array $fotosFiles): void {
        $db = $this->model->getConexion();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // 1. Crear registro de checklist
            $idChecklist = $this->model->registrarChecklist($db, $idEnvio, $idUnidad, $tipoChecklist, $vin, $userId, $comentarios);

            // 2. Crear directorio de evidencias si no existe
            $uploadDir = 'Assets/images/uploads/evidencias/';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("No se pudo crear el directorio de evidencias: " . $uploadDir);
                }
            }

            // 3. Procesar y guardar cada archivo fotográfico (en checklist y sincronizado en lgs_evidencias)
            $tipoEvidenciaNum = ($tipoChecklist === 'entrega_destino') ? 2 : 1; // 1=Salida/Patio, 2=Llegada/Destino

            if (empty($fotosFiles)) {
                error_log("Checklist sin fotos subidas en _FILES o con error (error > 0) para el VIN: $vin");
            }

            foreach ($fotosFiles as $key => $file) {
                if (isset($file['tmp_name']) && !empty($file['tmp_name'])) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = 'ev_' . $idChecklist . '_' . $key . '_' . md5(uniqid()) . '.' . $ext;
                    $destFile = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $destFile)) {
                        $this->model->registrarChecklistEvidencia($db, $idChecklist, $key, $destFile);
                        
                        // Guardar en tabla unificada de evidencias
                        $obsEvidencia = "Foto " . ucfirst($key) . " (" . $vin . ")" . ($comentarios ? " - " . $comentarios : "");
                        $this->model->insertarEvidenciaUnificada($db, $idEnvio, $idUnidad, $tipoEvidenciaNum, $destFile, $obsEvidencia, $userId);
                    } else {
                        error_log("Fallo move_uploaded_file para " . $file['tmp_name'] . " hacia " . $destFile);
                    }
                }
            }

            // 4. Actualizar estados físicos según el tipo de checklist
            if ($tipoChecklist === 'entrada_trasladista') {
                // Al hacer el trasladista su checklist de recolección individual
                $sqlUpVin = "UPDATE lgs_envios_vins SET estado_unidad_fisico = 'EN_ENTREGAS' WHERE id_envio = ? AND id_unidad = ?";
                $stmt = $db->prepare($sqlUpVin);
                $stmt->execute([$idEnvio, $idUnidad]);
            } elseif ($tipoChecklist === 'salida_planta') {
                // Al confirmar salida de planta
                $sqlUpVin = "UPDATE lgs_envios_vins SET estado_unidad_fisico = 'EN_RUTA' WHERE id_envio = ? AND id_unidad = ?";
                $stmt = $db->prepare($sqlUpVin);
                $stmt->execute([$idEnvio, $idUnidad]);
            } elseif ($tipoChecklist === 'entrega_destino') {
                // Al confirmar entrega en destino
                $sqlUpVin = "UPDATE lgs_envios_vins SET estado_unidad_fisico = 'ENTREGADO', fecha_entrega_real = NOW() WHERE id_envio = ? AND id_unidad = ?";
                $stmt = $db->prepare($sqlUpVin);
                $stmt->execute([$idEnvio, $idUnidad]);

                // Comprobar si ya se entregaron todos para finalizar el envío
                $sqlCheck = "SELECT COUNT(*) FROM lgs_envios_vins WHERE id_envio = ? AND (estado_unidad_fisico != 'ENTREGADO' OR estado_unidad_fisico IS NULL)";
                $stmtCheck = $db->prepare($sqlCheck);
                $stmtCheck->execute([$idEnvio]);
                $restantes = (int)$stmtCheck->fetchColumn();

                if ($restantes === 0) {
                    $this->model->updateEstadoEnvio($db, $idEnvio, 7); // 7 = Entregado
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
        $db = $this->model->getConexion();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // 1. Guardar archivos de remisión y firma
            $uploadDir = 'Assets/images/uploads/evidencias/';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("No se pudo crear el directorio: " . $uploadDir);
                }
            }

            foreach ($fotosFiles as $key => $file) {
                if (isset($file['tmp_name']) && !empty($file['tmp_name'])) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                    $fileName = 'entrega_' . $idEnvio . '_' . $key . '_' . md5(uniqid()) . '.' . $ext;
                    $destFile = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $destFile)) {
                        $obs = ($key === 'evidencia_remision') 
                            ? "Remisión Sellada - Recibe: {$nombreRecibe}" 
                            : "Firma / Foto Receptor - {$nombreRecibe}";
                        if (!empty($comentarios)) {
                            $obs .= " | " . $comentarios;
                        }
                        $this->model->insertarEvidenciaUnificada($db, $idEnvio, 0, 2, $destFile, $obs, $userId);
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
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
