<?php

class Lgs_aprobacionesService {

    private Lgs_aprobacionesModel $model;

    public function __construct() {
        $this->model = new Lgs_aprobacionesModel();
    }

    public function getAllParaAprobacion(): array {
        return $this->model->getPlaneacionesAprobacion();
    }

    public function getDetallePlan(int $idPlaneacion): array {
        return $this->model->getEnviosPorPlaneacion($idPlaneacion);
    }

    /**
     * Procesa la decisión del gerente: Aprobar o Rechazar
     */
    public function resolverPlaneacion(int $idPlaneacion, string $decision, string $observaciones, int $userId): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            
            if ($decision === 'aprobar') {
                $estadoPlan = 5; // Planeación Aprobada
                $estadoEnvio = 3; // Envío Aprobado (Listo para despachar)
            } else if ($decision === 'rechazar') {
                $estadoPlan = 3; // Planeación Regresada
                $estadoEnvio = 4; // Envío Regresado (El operador debe replanearlo)
            } else {
                throw new Exception("Decisión no válida.");
            }

            // 1. Actualizamos la cabecera de la Planeación
            $this->model->updateEstadoPlaneacion($db, $idPlaneacion, $estadoPlan, $observaciones, $userId);
            
            // 2. Liberamos o bloqueamos los envíos que estaban adentro
            $this->model->updateEstadoEnviosMasivo($db, $idPlaneacion, $estadoEnvio);
            
            // Opcional: Aquí se podría integrar el servicio de correos para notificar al operador.
            // NotificationService::notifyOperadorLogistica($idPlaneacion, $decision, $observaciones);
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
