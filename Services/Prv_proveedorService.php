<?php

class Prv_proveedorService
{
    private $model;

    public function __construct() {
        $this->model = new Prv_proveedorModel();
    }

    public function store(array $data): ServiceResponse
    {
        $db = $this->model->getConexion();
        $db->beginTransaction();

        try {
            $id = $this->model->save($data);
            if (!$id) throw new \Exception("No se pudo registrar el proveedor.");

            // Auditoría automática
            $this->model->logAudit($id, 'CREACIÓN', "Se registró el proveedor con RFC: {$data['rfc']}");

            $db->commit();
            return ServiceResponse::success(data: ['id' => $id], message: "Proveedor creado con éxito.");
        } catch (\Exception $e) {
            $db->rollBack();
            return ServiceResponse::error(message: $e->getMessage());
        }
    }

    public function changeState(int $id, int $newState, string $reason): ServiceResponse
    {
        DB::beginTransaction();
        try {
            $actions = [0 => 'ELIMINACIÓN', 1 => 'DESACTIVACIÓN', 2 => 'ACTIVACIÓN'];
            $this->model->updateStatus($id, $newState);
            $this->model->logAudit($id, $actions[$newState], $reason);

            DB::commit();
            return ServiceResponse::success(message: "Estado actualizado.");
        } catch (\Exception $e) {
            DB::rollBack();
            return ServiceResponse::error(message: $e->getMessage());
        }
    }
}