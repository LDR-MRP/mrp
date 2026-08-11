<?php

class Lgs_evidenciasService {

    private Lgs_evidenciasModel $model;

    public function __construct() {
        $this->model = new Lgs_evidenciasModel();
    }

    public function getEnviosEvidencias(): array {
        return $this->model->getEnviosParaEvidencias();
    }

    public function getEvidenciasEnvio(int $idEnvio): array {
        return $this->model->getEvidenciasPorEnvio($idEnvio);
    }

    public function guardarEvidencia(array $data, int $userId): int {
        if (empty($data['id_envio']) || empty($data['ruta_archivo']) || empty($data['tipo_evidencia'])) {
            throw new Exception("Datos incompletos para guardar la evidencia.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $data['created_by'] = $userId;
            $idEvidencia = $this->model->insertEvidencia($db, $data);
            $db->commit();
            return $idEvidencia;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function borrarEvidencia(int $idEvidencia): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $this->model->deleteEvidencia($db, $idEvidencia);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function confirmarLlegadaDestino(int $idEnvio, string $fechaLlegada, int $userId): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $this->model->registrarEntregaFinal($db, $idEnvio, $fechaLlegada, $userId);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
