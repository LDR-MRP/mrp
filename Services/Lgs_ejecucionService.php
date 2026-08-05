<?php

class Lgs_ejecucionService {

    private Lgs_ejecucionModel $model;

    public function __construct() {
        $this->model = new Lgs_ejecucionModel();
    }

    public function getEnviosDespacho(): array {
        return $this->model->getEnviosParaEjecucion();
    }

    public function getDetalleDespacho(int $idEnvio): array {
        return $this->model->getVinsAcomodoConSolicitud($idEnvio);
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

            // 3. Si ya salieron todos las unidades, el envío pasa automáticamente a Estado 6 (En Tránsito)
            if ($todosCompletos) {
                $this->model->updateEstadoEnvio($db, $idEnvio, 6);
            }

            $db->commit();
            return $todosCompletos;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
