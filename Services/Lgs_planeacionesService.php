<?php

class Lgs_planeacionesService {

    private Lgs_planeacionesModel $model;

    public function __construct() {
        $this->model = new Lgs_planeacionesModel();
    }

    public function getAllPlaneaciones(): array {
        return $this->model->getPlaneacionesDataTable();
    }

    public function getEnviosDisponibles(int $idPlaneacion = 0): array {
        return $this->model->getEnviosDisponiblesPlan($idPlaneacion);
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

    /**
     * Actualiza una planeación existente (en estado 1 - Borrador)
     */
    public function updatePlaneacion(int $idPlaneacion, array $data, array $enviosIds, int $userId): void {
        if (empty($enviosIds)) {
            throw new Exception("Debe seleccionar al menos un envío para la planeación.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $data['updated_at'] = date('Y-m-d H:i:s');
            
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

            // 1. Actualizar cabecera de la Planeación
            $this->model->updatePlaneacion($db, $idPlaneacion, $data);
            
            // 2. Liberar los envíos anteriores (volver a estado 8)
            $oldEnvios = $this->model->deletePlanEnvios($db, $idPlaneacion);
            if (!empty($oldEnvios)) {
                $inOldIds = implode(',', array_fill(0, count($oldEnvios), '?'));
                $stmtRelease = $db->prepare("UPDATE lgs_envios SET id_estado = 8 WHERE id_envio IN ($inOldIds)");
                $stmtRelease->execute($oldEnvios);
            }
            
            // 3. Vincular los nuevos envíos y cambiarles el estado
            // Nota: Si se guarda un borrador, el estado de los envíos debería ser 1, pero según reabrirPlaneacion, el estado es 1 (editable).
            // Sin embargo, si al crear se envían a aprobación, id_estado es 2.
            // Para mantener coherencia, si el estado de la planeación se mantiene en 1 (Borrador), los envíos deberían ser 1.
            $stmtUpdateEnvio = $db->prepare("UPDATE lgs_envios SET id_estado = 1 WHERE id_envio = ?");
            
            foreach ($enviosIds as $idEnvio) {
                $this->model->insertPlanEnvio($db, $idPlaneacion, $idEnvio);
                $stmtUpdateEnvio->execute([$idEnvio]);
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function getDetalleCompletoPlan(int $idPlaneacion): array {
        return $this->model->getDetalleCompletoPlan($idPlaneacion);
    }

    /**
     * Reabre una planeación regresándola a borrador y desbloqueando sus envíos
     */
    public function reabrirPlaneacion(int $idPlaneacion, int $userId): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $this->model->reabrirPlaneacion($db, $idPlaneacion);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Envía una planeación existente en borrador a aprobación
     */
    public function enviarAprobacion(int $idPlaneacion, int $userId): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $this->model->enviarAprobacion($db, $idPlaneacion);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
