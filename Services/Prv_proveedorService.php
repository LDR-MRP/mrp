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

            if ($validated['idproveedor']) {
                $this->model->updateData($validated);
                $this->model->logAudit($validated['idproveedor'], 'ACTUALIZACIÓN', "Se actualizó el proveedor con RFC: {$data['rfc']}", $_SESSION['idUser']);
                $db->commit();
                return ServiceResponse::success(data: ['id' => $validated['idproveedor']], message: "Proveedor actualizado con éxito.");
            }

            $id = $this->model->save($validated);
            if (!$id) throw new \Exception("No se pudo registrar el proveedor.");
            $this->model->logAudit($id, 'CREACIÓN', "Se registró el proveedor con RFC: {$data['rfc']}", $_SESSION['idUser']);
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

    public function getKpi()
    {
        return ServiceResponse::success($this->model->getKpi());
    }
}