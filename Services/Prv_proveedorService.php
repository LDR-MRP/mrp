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
            $file = $proveedorStoreRequest->files()['logo'];

            if(!empty($file) && !empty($file['tmp_name'])) {
                $validated['logo'] = 'data:'.$file['type'].';base64,'.base64_encode(file_get_contents($file['tmp_name']));
            } else {
                $validated['logo'] = current($this->model->findByCriteria(['idproveedor' => $validated['idproveedor']]))['logo'];
            }

            if ($validated['idproveedor']) {
                $this->model->updateData($validated);
                $this->model->logAudit($validated['idproveedor'], 'ACTUALIZACIÓN', "Se actualizó el proveedor con RFC: {$data['rfc']}", $_SESSION['idUser']);
                $db->commit();
                return ServiceResponse::success(data: ['id' => $validated['idproveedor']], message: "Proveedor actualizado con éxito.");
            }

            $id = $this->model->save($validated);
            if (!$id) throw new \Exception("No se pudo registrar el proveedor.");
            $this->model->logAudit($id, 'CREACIÓN', "Se registró/actualizó el proveedor con RFC: {$data['rfc']}", $_SESSION['idUser']);
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

    public function delete(array $data): ServiceResponse
    {
        $db = $this->model->getConexion();
        $db->beginTransaction();

        try {
            $this->model->destroy($data['idproveedor']);
            $this->model->logAudit($data['idproveedor'], 'ELIMINACIÓN', "Se elimino el proveedor con ID: {$data['rfc']}", $_SESSION['idUser']);
            $db->commit();
            return ServiceResponse::success(data: ['rfc' => $data['rfc']], message: "Proveedor eliminado con éxito.");
        } catch (\Exception $e) {
            $db->rollBack();
            return ServiceResponse::error(message: $e->getMessage());
        }
        
    }

    public function suppliers()
    {
        return ServiceResponse::success($this->model->findByCriteria());
    }
}