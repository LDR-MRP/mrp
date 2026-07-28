<?php

class Prv_trasladistasService {

    private Prv_trasladistasModel $model;

    public function __construct() {
        $this->model = new Prv_trasladistasModel();
    }

    public function getAll(): array {
        return $this->model->getTrasladistas();
    }

    public function getById(int $id): ?array {
        return $this->model->getTrasladista($id);
    }

    public function create(array $data, int $userId): int {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $id = $this->model->insertTrasladista($data, $userId);
            if ($id <= 0) {
                throw new Exception("Error al insertar el proveedor trasladista.");
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
            $res = $this->model->updateTrasladista($id, $data, $userId);
            $db->commit();
            return $res;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id, int $userId): bool {
        return $this->model->deleteTrasladista($id, $userId);
    }
}
