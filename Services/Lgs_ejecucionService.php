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

    /**
     * Confirma la fecha de recolección programada por el administrativo
     */
    public function confirmarFechaRecoleccion(int $idEnvio, string $fechaRecoleccion): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $this->model->confirmarFechaRecoleccion($db, $idEnvio, $fechaRecoleccion);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Registra la salida física/despacho del envío y genera las solicitudes para planta
     */
    public function registrarDespacho(int $idEnvio, string $fechaSalida, ?string $evidenciasJson, int $userId): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            // 1. Guardar fecha de salida real y evidencias
            $this->model->registrarDespachoEnvio($db, $idEnvio, $fechaSalida, $evidenciasJson);

            // 2. Generar las solicitudes de entrega para el área de planta/almacén con el orden de acomodo
            $this->model->crearSolicitudesEntrega($db, $idEnvio);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Confirma la entrega física de un VIN individual a la madrina/chofer en planta
     */
    public function confirmarSalidaVin(int $idEnvio, int $idUnidad, int $userId): bool {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            // 1. Confirmar entrega del VIN
            $this->model->confirmarEntregaVinPlanta($db, $idEnvio, $idUnidad, $userId);

            // 2. Comprobar si ya se entregaron TODOS los VINs del envío
            $todosCompletos = $this->model->checkTodosVinsEntregados($db, $idEnvio);

            // 3. Si ya salieron todas las unidades, el envío pasa automáticamente a Estado 6 (En Tránsito)
            if ($todosCompletos) {
                $this->model->updateEstadoEnvio($db, $idEnvio, 6);
                $this->model->updateEstadoUnidadesFisico($db, $idEnvio, 'EN_RUTA');
            }

            $db->commit();
            return $todosCompletos;
        } catch (Exception $e) {
            $db->rollBack();
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
            $db->beginTransaction();

            // 1. Crear registro de checklist
            $idChecklist = $this->model->registrarChecklist($db, $idEnvio, $idUnidad, $tipoChecklist, $vin, $userId, $comentarios);

            // 2. Crear directorio de evidencias si no existe
            $uploadDir = 'Assets/images/uploads/evidencias/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // 3. Procesar y guardar cada archivo fotográfico
            foreach ($fotosFiles as $key => $file) {
                if (isset($file['tmp_name']) && !empty($file['tmp_name'])) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = 'ev_' . $idChecklist . '_' . $key . '_' . md5(uniqid()) . '.' . $ext;
                    $destFile = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $destFile)) {
                        $this->model->registrarChecklistEvidencia($db, $idChecklist, $key, $destFile);
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

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
