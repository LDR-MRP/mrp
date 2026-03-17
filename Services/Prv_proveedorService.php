<?php

class Prv_proveedorService
{
    private $model;

    private $prvDetExpedienteModel;

    public function __construct() {
        $this->model = new Prv_proveedorModel();
        $this->prvDetExpedienteModel = new Prv_detExpedienteModel();
    }

    public function findByCriteria(array $filters = []): ServiceResponse
    {
        return ServiceResponse::success($this->model->findByCriteria($filters));
    }

    public function getKpi(): ServiceResponse
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
    public function suppliers(): ServiceResponse
    {
        return ServiceResponse::success($this->model->findByCriteria());
    }

    public function registrarProveedor(mixed $data): ServiceResponse
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

    public function uploadDocument(array $data, array $files): ServiceResponse
    {
        $db = $this->model->getConexion();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->beginTransaction();

        try {
            if(!($userId = $_SESSION['idUser'])) {
                throw new \Exception("No hay una sesión de usuario activa.");
            }

            $uploadDocumentRequest = new Prv_uploadExpedienteRequest(array_merge($data, $files));
            $uploadDocumentRequest->validate();
            $validated = $uploadDocumentRequest->all();

            $idProveedor = intval($_POST['id_proveedor']);
            $tipoDoc     = $validated['tipo_documento'];
            $archivo     = $validated['archivo'];

            $docConfig = $this->prvDetExpedienteModel::DOCUMENTOS_REQUERIDOS[$tipoDoc];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

            // 1. Gestión de almacenamiento
            $dirPath = "Assets/uploads/expedientes/prov_{$idProveedor}/";
            if (!is_dir($dirPath)) mkdir($dirPath, 0777, true);

            $fileName = "{$tipoDoc}_{$idProveedor}_" . date('Ymd_His') . ".{$extension}";
            $fullPath = $dirPath . $fileName;

            if (!move_uploaded_file($archivo['tmp_name'], $fullPath)) {
                throw new \Exception("Error al mover el archivo al servidor.");
            }

            // 2. Persistencia
            $dbData = [
                'id_proveedor'    => $idProveedor,
                'tipo_documento'  => $tipoDoc,
                'url_archivo'    => $fullPath,
                'nombre_original' => $archivo['name'],
                'created_by'      => $_SESSION['idUser'] ?? 1
            ];

            if (!$this->prvDetExpedienteModel->saveDocument($dbData)) {
                if (file_exists($fullPath)) unlink($fullPath);
                throw new \Exception("Error al registrar en base de datos.");
            }

            $db->commit();

            return ServiceResponse::success(
                message: "{$docConfig['name']} actualizado correctamente.",
                data: ['ruta' => $fullPath]
            );
        } catch (\PDOException $e) {
            $db->rollBack();
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
        } catch (\InvalidArgumentException $e) {
            $db->rollBack();
            return ServiceResponse::validation(errors: $e->getMessage());
        } catch (\Exception $e) {
            $db->rollBack();
            return ServiceResponse::error(message: $e->getMessage());
        }
    }

    public function documents(int $supplierId): ServiceResponse
    {
        $config = $this->prvDetExpedienteModel::DOCUMENTOS_REQUERIDOS;
        $uploaded = $this->prvDetExpedienteModel->uploadedDocuments($supplierId);        
        $uploadedIndexed = array_column($uploaded, null, 'tipo_documento');

        $validated = array_combine(
            array_keys($config),
            array_map(function($key, $value) use ($uploadedIndexed) {
                $fileInfo = $uploadedIndexed[$key] ?? null;

                return array_merge($value, [
                    'uploaded'  => (bool)$fileInfo,
                    'file_data' => $fileInfo,
                ]);
            }, array_keys($config), $config)
        );

        $progressPercentage = round((count($uploaded) / count($config)) * 100) ?? 0;
        
        return ServiceResponse::success(
            [
                'documents' => $validated,
                'progress' => $progressPercentage
            ]
        );
    }

    public function auditDocument(array $inputData): ServiceResponse
    {
        $outputData = [];

        if(!$this->prvDetExpedienteModel->auditDocument(values: $inputData)){
            throw new \Exception("No se pudo procesar tu solicitud.");            
        }

        return ServiceResponse::success($outputData);
    }
}