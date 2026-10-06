<?php

class Prv_plataformasService {

    private Prv_plataformasModel $model;

    public function __construct() {
        $this->model = new Prv_plataformasModel();
    }

    public function getAll(): array {
        return $this->model->getPlataformas();
    }

    public function getById(int $id): ?array {
        return $this->model->getPlataforma($id);
    }

    public function create(array $data, int $userId): int {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $id = $this->model->insertPlataforma($data, $userId);
            if ($id <= 0) {
                throw new Exception("Error al guardar la plataforma.");
            }
            $db->commit();
            return $id;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data, int $userId): bool {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $res = $this->model->updatePlataforma($id, $data, $userId);
            $db->commit();
            return $res;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id, int $userId): bool {
        return $this->model->deletePlataforma($id, $userId);
    }

    public function getHistorialChoferes(int $idPlataforma): array {
        return $this->model->getHistorialChoferes($idPlataforma);
    }

    public function asignarChofer(int $idPlataforma, int $idChofer, ?string $observaciones, int $userId): bool {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $res = $this->model->asignarChofer($idPlataforma, $idChofer, $observaciones, $userId);
            $db->commit();
            return $res;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            throw $e;
        }
    }
}
