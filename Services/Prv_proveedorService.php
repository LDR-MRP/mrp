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

    public function registrarProveedor(mixed $data)
    {
        $db = $this->model->getConexion();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->beginTransaction();

        try {
            if(!($userId = $_SESSION['idUser'])) {
                throw new \Exception("No hay una sesión de usuario activa.");
            }

            $data = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('El cuerpo de la petición no es un JSON válido.');
            }

            $proveedorStoreRequest = new Prv_proveedorStoreRequest($data);
            $proveedorStoreRequest->validate();
            $validated = $proveedorStoreRequest->all();

            $idProveedor = $this->model->insertProveedor($validated);
            if ($idProveedor <= 0) throw new \Exception("Error al crear el maestro del proveedor.");

            $resDir = $this->model->insertDireccion($validated, $idProveedor);
            if (!$resDir) throw new \Exception("Error al registrar la dirección fiscal.");

            $resFin = $this->model->insertConfigFinanciera($validated, $idProveedor);
            if (!$resFin) throw new \Exception("Error al registrar la configuración financiera.");

            $resCont = $this->model->insertContacto($validated, $idProveedor);
            if (!$resCont) throw new \Exception("Error al registrar el contacto.");

            $resOnb = $this->model->insertOnboarding($idProveedor, $idProveedor);
            if (!$resOnb) throw new \Exception("Error al iniciar flujo de onboarding.");

            $db->commit();
            return ServiceResponse::success(data: ['id' => $idProveedor], code: 201);
        } catch (\PDOException $e) {
            $db->rollBack();

            if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062')) {
                return ServiceResponse::validation(errors: [
                    'db' => "El RFC o la Razón Social ya se encuentran registrados en el sistema."
                ]);
            }

            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
        } catch (\InvalidArgumentException $e) {
            $db->rollBack();
            return ServiceResponse::validation(errors: $e->getMessage());
        } catch (\Exception $e) {
            $db->rollBack();
            return ServiceResponse::error(message: $e->getMessage());
        }
    }
}