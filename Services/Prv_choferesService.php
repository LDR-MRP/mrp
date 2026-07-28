<?php

class Prv_choferesService {

    private Prv_choferesModel $model;

    public function __construct() {
        $this->model = new Prv_choferesModel();
    }

    public function getAll(): array {
        return $this->model->getChoferes();
    }

    public function getById(int $id): ?array {
        return $this->model->getChofer($id);
    }

    public function create(array $data, int $userId): int {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $id = $this->model->insertChofer($data, $userId);
            if ($id <= 0) {
                throw new Exception("Error al guardar el chofer.");
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
            $res = $this->model->updateChofer($id, $data, $userId);
            $db->commit();
            return $res;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id, int $userId): bool {
        return $this->model->deleteChofer($id, $userId);
    }
}
