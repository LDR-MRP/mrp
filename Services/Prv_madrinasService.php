<?php

class Prv_madrinasService {

    private Prv_madrinasModel $model;

    public function __construct() {
        $this->model = new Prv_madrinasModel();
    }

    public function getAll(): array {
        return $this->model->getMadrinas();
    }

    public function getById(int $id): ?array {
        return $this->model->getMadrina($id);
    }

    public function create(array $data, int $userId): int {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $id = $this->model->insertMadrina($data, $userId);
            if ($id <= 0) {
                throw new Exception("Error al guardar la madrina.");
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
            $res = $this->model->updateMadrina($id, $data, $userId);
            $db->commit();
            return $res;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id, int $userId): bool {
        return $this->model->deleteMadrina($id, $userId);
    }
}
