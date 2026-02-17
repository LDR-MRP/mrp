<?php

class Prv_proveedorService
{
    private $model;

    public function __construct() {
        $this->model = new Prv_proveedorModel();
    }

    public function findByCriteria(array $filters = [])
    {
        return ServiceResponse::success($this->model->findByCriteria($filters));
    }

    public function store(array $data): ServiceResponse
    {
        $db = $this->model->getConexion();
        $db->beginTransaction();

        try {

            $proveedorStoreRequest = new Prv_proveedorStoreRequest($data);
            $proveedorStoreRequest->validate();
            $validated = $proveedorStoreRequest->all();

            $id = $this->model->save($validated);
            if (!$id) throw new \Exception("No se pudo registrar el proveedor.");

            // Auditoría automática
            // $this->model->logAudit($id, 'CREACIÓN', "Se registró el proveedor con RFC: {$data['rfc']}");

            $db->commit();
            return ServiceResponse::success(data: ['id' => $id], message: "Proveedor creado con éxito.");
        } catch (\InvalidArgumentException $e) {
            $db->rollBack();
            return ServiceResponse::validation(errors: $e->getMessage());
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