<?php

class Lgs_enviosService {

    private Lgs_enviosModel $model;

    public function __construct() {
        $this->model = new Lgs_enviosModel();
    }

    /**
     * Obtiene todos los envíos para la vista principal
     */
    public function getAllEnvios(): array {
        return $this->model->getEnviosDataTable();
    }

    /**
     * Crea la cabecera de un envío nuevo (Transaction con bloqueo)
     */
    public function createEnvio(array $data, int $userId): int {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            
            // 1. Bloquea la tabla y genera el folio (EN-000001)
            $folio = $this->model->generarFolioTransaccional($db);
            $data['folio'] = $folio;
            $data['created_by'] = $userId;
            
            // 2. Inserta la cabecera
            $idEnvio = $this->model->insertEnvio($db, $data);
            
            $db->commit();
            return $idEnvio;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Asigna un VIN a un envío con soporte para múltiples paradas
     */
    public function asignarVin(int $idEnvio, int $idUnidad, array $params, int $userId): bool {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            
            $params['id_envio'] = $idEnvio;
            $params['id_unidad'] = $idUnidad;
            $params['created_by'] = $userId; // Opcional, si aplicara

            // 1. Insertar el VIN en la pivot
            $this->model->insertVin($db, $params);
            
            // 2. Aquí iría la llamada a recalcularCostoTotal($idEnvio)
            // $this->recalcularCostoTotal($idEnvio, $db);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Motor de cálculo: Recalcula costos basado en Madrina vs Chofer (Rodando)
     * Este es el placeholder para la Subtarea 2.8
     */
    public function recalcularCostoTotal(int $idEnvio, PDO $db = null): float {
        // ... (Lógica definida en 2_analisis_tecnico_dev.md)
        return 0.0;
    }
}
