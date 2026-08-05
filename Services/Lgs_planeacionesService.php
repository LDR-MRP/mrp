<?php

class Lgs_planeacionesService {

    private Lgs_planeacionesModel $model;

    public function __construct() {
        $this->model = new Lgs_planeacionesModel();
    }

    public function getAllPlaneaciones(): array {
        return $this->model->getPlaneacionesDataTable();
    }

    public function getEnviosDisponibles(): array {
        return $this->model->getEnviosDisponiblesPlan();
    }

    /**
     * Crea una planeación agrupando varios envíos y cambiándolos a estado 2 (En Revisión)
     */
    public function createPlaneacion(array $data, array $enviosIds, int $userId): int {
        if (empty($enviosIds)) {
            throw new Exception("Debe seleccionar al menos un envío para la planeación.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            
            // 1. Folio y datos base
            $folio = $this->model->generarFolioPlan($db);
            $data['folio'] = $folio;
            $data['created_by'] = $userId;
            $data['id_estado'] = 2; // Enviada a Aprobación (Estado 2 de Planeación = Enviada)
            
            // Recalcular el gran total de la planeación sumando los envíos
            $costoAcumulado = 0.0;
            $kmAcumulados = 0.0;

            // Bloqueamos los envíos para lectura segura
            $inIds = implode(',', array_fill(0, count($enviosIds), '?'));
            $stmt = $db->prepare("SELECT id_envio, costo_total, km_total FROM lgs_envios WHERE id_envio IN ($inIds) FOR UPDATE");
            $stmt->execute($enviosIds);
            $enviosData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($enviosData as $e) {
                $costoAcumulado += (float)$e['costo_total'];
                $kmAcumulados += (float)$e['km_total'];
            }

            $data['costo_total'] = $costoAcumulado;
            $data['km_total'] = $kmAcumulados;

            // 2. Insertar cabecera de la Planeación
            $idPlaneacion = $this->model->insertPlaneacion($db, $data);
            
            // 3. Vincular los envíos y cambiarles el estado
            $stmtUpdateEnvio = $db->prepare("UPDATE lgs_envios SET id_estado = 2 WHERE id_envio = ?");
            
            foreach ($enviosIds as $idEnvio) {
                $this->model->insertPlanEnvio($db, $idPlaneacion, $idEnvio);
                $stmtUpdateEnvio->execute([$idEnvio]);
            }
            
            $db->commit();
            return $idPlaneacion;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
