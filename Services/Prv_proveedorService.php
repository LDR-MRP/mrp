<?php

class Prv_proveedorService
{
    private $prvProveedorModel;

    private $prvDetExpedienteModel;

    protected $userId;

    public function __construct() {
        $this->prvProveedorModel = new Prv_proveedorModel();
        $this->prvDetExpedienteModel = new Prv_detExpedienteModel();
        $this->userId = $_SESSION['idUser'] ?? 1;
    }

    public function findByCriteria(array $filters = []): ServiceResponse
    {
        return ServiceResponse::success($this->prvProveedorModel->findByCriteria($filters));
    }

    public function getKpi(): ServiceResponse
    {
        return ServiceResponse::success($this->prvProveedorModel->getKpi());
    }

    public function delete(array $data): ServiceResponse
    {
        $db = $this->prvProveedorModel->getConexion();
        $db->beginTransaction();

        try {
            $this->prvProveedorModel->destroy($data['idproveedor']);
            $this->prvProveedorModel->logAudit($data['idproveedor'], 'ELIMINACIÓN', "Se elimino el proveedor con ID: {$data['rfc']}", $_SESSION['idUser']);
            $db->commit();
            return ServiceResponse::success(data: ['rfc' => $data['rfc']], message: "Proveedor eliminado con éxito.");
        } catch (\Exception $e) {
            $db->rollBack();
            return ServiceResponse::error(message: $e->getMessage());
        }
        
    }
    public function suppliers(): ServiceResponse
    {
        return ServiceResponse::success($this->prvProveedorModel->findByCriteria());
    }

    public function storeSupplier(mixed $data): ServiceResponse
    {
        $db = $this->prvProveedorModel->getConexion();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->beginTransaction();

        try {
            $action = null;
            $data = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('El cuerpo de la petición no es un JSON válido.');
            }

            $proveedorStoreRequest = new Prv_proveedorStoreRequest($data);
            $proveedorStoreRequest->validate();
            $validated = $proveedorStoreRequest->all();

            if(!empty($supplierId = $validated['id'])):
                // RUTA A: ACTUALIZACIÓN INTELIGENTE (Solo campos modificados)
                $currentSupplier = $proveedorStoreRequest->getCurrentSupplier();

                $dirtyData = [];

                foreach ($validated as $column => $newValue) {
                    if (array_key_exists($column, $currentSupplier) && $currentSupplier[$column] != $newValue) {
                        $dirtyData[$column] = $newValue;
                    }
                }

                if (!empty($dirtyData)) {
                    $buckets = [];

                    foreach ($this->prvProveedorModel::SCHEMA as $table => $allowedColumns) {
                        $tableData = array_intersect_key($dirtyData, array_flip($allowedColumns));
                        
                        if (!empty($tableData)) {
                            $buckets[$table] = $tableData;
                        }
                    }

                    foreach ($buckets as $table => $dataToUpdate) {
                        if (empty($dataToUpdate)) continue;
                        $cols = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($dataToUpdate)));
                        $values = array_merge(array_values($dataToUpdate), [(int) $supplierId]);
                        $this->prvProveedorModel->updateDynamic($table, $cols, $values);
                    }

                    $message = "Se actualizó el proveedor con RFC: {$currentSupplier['rfc']}";
                    $action = 'actualización';
                } else {
                    $message = 'No se detectaron cambios en la información del proveedor.';
                }

                $code = 200;                
            else:
                // RUTA B: CREACIÓN DEL REGISTRO
                $supplierId = $this->prvProveedorModel->insertSupplier($validated, $this->userId);
                if ($supplierId <= 0) throw new \Exception("Error al crear el maestro del proveedor.");

                $resDir = $this->prvProveedorModel->insertAddress($validated, $supplierId, $this->userId);
                if (!$resDir) throw new \Exception("Error al registrar la dirección fiscal.");

                $resFin = $this->prvProveedorModel->insertFinancialConfig($validated, $supplierId, $this->userId);
                if (!$resFin) throw new \Exception("Error al registrar la configuración financiera.");

                $resCont = $this->prvProveedorModel->insertContact($validated, $supplierId, $this->userId);
                if (!$resCont) throw new \Exception("Error al registrar el contacto.");

                $resOnb = $this->prvProveedorModel->insertOnboarding($supplierId, $this->userId);
                if (!$resOnb) throw new \Exception("Error al iniciar flujo de onboarding.");

                $message = "Se creó el proveedor con RFC: {$validated['rfc']}";
                $code = 201;
                $action = 'creación';
            endif;
            
            $this->prvProveedorModel->logAudit($supplierId, $action, $message, $this->userId);
            $db->commit();

            return ServiceResponse::success(
                data: ['id' => $supplierId],
                message: $message,
                code: $code
            );

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
        $db = $this->prvProveedorModel->getConexion();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        try {
            $uploadDocumentRequest = new Prv_uploadExpedienteRequest(array_merge($data, $files));
            $uploadDocumentRequest->validate();
            $validated = $uploadDocumentRequest->all();

            $supplierId = intval($validated['id_proveedor']);
            $docType = $validated['tipo_documento'];
            $file = $validated['archivo'];

            $existingDoc = current($this->prvDetExpedienteModel->findByCriteria($validated));
            $oldFilePath = $existingDoc['url_archivo'] ?? null;

            $docConfig = $this->prvDetExpedienteModel::REQUIRED_DOCUMENTS[$docType];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            $dirPath = $this->prvDetExpedienteModel::SUPPLIER_RECORD_PATH . "prov_{$supplierId}/";
            if (!is_dir($dirPath)) mkdir($dirPath, 0777, true);

            $fileName = "{$docType}_{$supplierId}_" . date('Ymd_His') . ".{$extension}";
            $fullPath = $dirPath . $fileName;

            $db->beginTransaction();

            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new \Exception("Error al mover el archivo al servidor.");
            }

            $dbData = [
                'id_proveedor'    => $supplierId,
                'tipo_documento'  => $docType,
                'url_archivo'    => $fullPath,
                'nombre_original' => $file['name'],
                'created_by'      => $this->userId,
            ];

            if (!$this->prvDetExpedienteModel->upsertDocument($dbData)) {
                throw new \Exception("Error al registrar en base de datos.");
            }

            $this->prvDetExpedienteModel->logAudit($supplierId, $existingDoc ? 'actualización' : 'creación', "{$docConfig['name']} procesado correctamente.", $this->userId);
            $db->commit();

            if ($oldFilePath && file_exists($oldFilePath)) {
                unlink($existingDoc['url_archivo']);
            }

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
        $config = $this->prvDetExpedienteModel::REQUIRED_DOCUMENTS;
        $uploaded = $this->prvDetExpedienteModel->uploadedDocuments($supplierId);        
        $uploadedIndexed = array_column($uploaded, null, 'tipo_documento');

        $approvedDocuments = array_filter($uploaded, function($doc) {
            return isset($doc['estatus_validacion']) && (int)$doc['estatus_validacion'] === 1;
        });

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

        $progressPercentage = round((count($approvedDocuments) / count($config)) * 100) ?? 0;
        
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

        if(!$this->prvDetExpedienteModel->auditDocument(values: $inputData, userId: $this->userId)){
            throw new \Exception("No se pudo procesar tu solicitud.");            
        }

        $this->prvDetExpedienteModel->logAudit($inputData['id_documento'], $inputData['motivo_rechazo'] ? 'rechazo' : 'aprobación', $inputData['motivo_rechazo'], $this->userId);

        return ServiceResponse::success($outputData);
    }

    public function getOnboardingStatus(int $supplierId): ServiceResponse
    {
        $supplier = $this->prvProveedorModel->findByCriteria(['id_proveedor' => $supplierId]);
        if (!$supplier) {
            return ServiceResponse::error("Proveedor no encontrado.", 404);
        }


            return ServiceResponse::success([]);
    }
}