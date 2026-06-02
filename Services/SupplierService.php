<?php

declare(strict_types=1);

use Requests\Supplier\StoreBankAccountRequest;
use Requests\Supplier\StoreSupplierRequest;

class SupplierService
{
    use \Loggable, \Notifiable;

    private readonly \Prv_proveedorModel $proveedorModel;
    private readonly \Prv_detExpedienteModel $expedienteModel;
    private readonly \Prv_detCuentaBancariaModel $bankModel;
    private readonly \UsuariosModel $usuarioModel;
    private object $db;

    public function __construct() {
        $this->proveedorModel = new \Prv_proveedorModel();
        $this->expedienteModel = new \Prv_detExpedienteModel();
        $this->bankModel = new \Prv_detCuentaBancariaModel();
        $this->usuarioModel = new \UsuariosModel();
        $this->db = $this->proveedorModel->getConexion();
    }

    public function findByCriteria(array $filters = []): ServiceResponse
    {
        $suppliers = $this->proveedorModel->findByCriteria($filters);
        return ServiceResponse::success($suppliers);
    }

    /**
     * Orquestador Stateless para la persistencia del proveedor.
     * 
     * @param array $userContext Identidad del usuario (JWT).
     */
    public function store(array $userContext): ServiceResponse
    {
        try {
            $this->db->beginTransaction();

            // 1. VALIDACIÓN DE FORMA Y NEGOCIO
            $storeRequest = new \Requests\Supplier\StoreSupplierRequest();
            $storeRequest->validate();
            $validated = $storeRequest->all();
            
            $userId = (int)$userContext['id'];
            $supplierId = !empty($validated['id']) ? (int)$validated['id'] : null;

            if ($supplierId) {
                // --- RUTA A: ACTUALIZACIÓN INTELIGENTE ---
                $response = $this->processUpdate($supplierId, $validated, $storeRequest, $userId);
            } else {
                // --- RUTA B: CREACIÓN ATÓMICA ---
                $response = $this->processCreation($validated, $userId);
            }

            $this->db->commit();
            return $response;

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return \ServiceResponse::validation(errors: $i->getMessage());
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            // Manejo de duplicados (RFC / Razón Social)
            if ($e->getCode() == 23000) {
                return ServiceResponse::validation(['db' => "El RFC o Razón Social ya están registrados."]);
            }
            return ServiceResponse::error("Error de integridad en base de datos.");
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage(), (int)$e->getCode() ?: 500);
        }
    }

    /**
     * Lógica de creación: Maestro + 4 tablas satélites.
     */
    private function processCreation(array $data, int $userId): ServiceResponse
    {
        $id = $this->proveedorModel->insertSupplier($data, $userId);
        if (!$id) throw new \Exception("No se pudo crear el maestro.");

        $data['limite_credito'] = (float)$data['limite_credito'] ?? null;
        $this->proveedorModel->insertAddress($data, $id, $userId);
        $this->proveedorModel->insertFinancialConfig($data, $id, $userId);
        $this->proveedorModel->insertContact($data, $id, $userId);
        $this->proveedorModel->insertOnboarding($id, $userId);

        $this->proveedorModel->logAudit($id, AuditAction::CREATED, "Alta de proveedor RFC: {$data['rfc']}", $userId);

        return ServiceResponse::success(['id' => $id], "Proveedor registrado exitosamente.", 201);
    }

    /**
     * Lógica de actualización: Dirty Checking sobre el esquema.
     * @param int $id
     * @param array $data
     * @param StoreSupplierRequest $request
     * @param int $userId
     */
    private function processUpdate(int $id, array $data, StoreSupplierRequest $request, int $userId): ServiceResponse
    {
        $current = $request->getCurrentSupplier();
        $dirtyData = [];

        // Identificamos solo los campos que realmente cambiaron
        foreach ($data as $column => $newValue) {
            if (array_key_exists($column, $current) && $current[$column] != $newValue) {
                $dirtyData[$column] = $newValue;
            }
        }

        if (empty($dirtyData)) {
            return ServiceResponse::success(['id' => $id], "No se detectaron cambios.");
        }

        // Repartimos los cambios en las tablas correspondientes según el SCHEMA del modelo
        foreach ($this->proveedorModel::SCHEMA as $table => $allowedColumns) {
            $tableData = array_intersect_key($dirtyData, array_flip($allowedColumns));
            if (!empty($tableData)) {
                $this->proveedorModel->updateDynamic($table, $tableData, (int)$id);
            }
        }

        $this->proveedorModel->logAudit($id, AuditAction::UPDATED, "Actualización de datos maestros.", $userId);

        return ServiceResponse::success(['id' => $id], "Información actualizada correctamente.");
    }

    /**
     * Procesa la subida física de documentos y actualiza el expediente.
     */
    public function uploadDocument(array $userContext): ServiceResponse
    {
        $request = new \Requests\Supplier\UploadDocumentRequest();
        
        try {
            $request->validate();
            $validated = $request->all();
            $file = $request->files()['archivo'];
            
            // --- INICIO DE CIRUGÍA: Prevención de IDOR ---
            $isVendor = ($userContext['role'] ?? '') === 'VENDOR' || !empty($userContext['vendor_id']);
            
            if ($isVendor) {
                // Si es proveedor, forzamos que sea su propio ID (Seguridad SRM)
                $supplierId = (int)$userContext['vendor_id'];
            } else {
                // Si es administrador interno, tomamos el del Request validado
                $supplierId = (int)$validated['id_proveedor'];
            }
            // --- FIN DE CIRUGÍA ---
            
            $docType    = $validated['tipo_documento'];
            $userId     = (int)$userContext['id'];

            // --- LÓGICA DE REEMPLAZO ---
            // 1. Verificar si ya existe un documento previo para obtener la ruta y borrarlo
            $existingDoc = $this->expedienteModel->getDocumentByType($supplierId, $docType);
            $oldFilePath = $existingDoc ? $existingDoc['url_archivo'] : null;

            // 2. Preparar nueva ruta
            // Usamos una ruta fuera del alcance público directo por seguridad
            $uploadPath = $this->expedienteModel::SUPPLIER_RECORD_PATH . "prov_{$supplierId}/";
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fileName = "{$docType}_" . bin2hex(random_bytes(4)) . ".pdf";
            $fullPath = $uploadPath . $fileName;

            $this->db->beginTransaction();

            // 3. Mover archivo al almacenamiento
            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new Exception("Error técnico al mover el archivo al servidor.", 500);
            }

            // 4. Registro en Base de Datos (Upsert)
            $dbData = [
                'id_proveedor'    => $supplierId,
                'tipo_documento'  => $docType,
                'url_archivo'     => $fullPath,
                'created_by'      => $userId
            ];
            
            $this->expedienteModel->upsertDocument($dbData);

            // 5. Auditoría
            $this->proveedorModel->logAudit($supplierId, AuditAction::UPLOAD_FILE, "Subida de documento: {$docType}", $userId);

            $this->db->commit();

            // 6. LIMPIEZA FÍSICA: Si todo salió bien en la DB, borramos el archivo viejo del disco
            if ($oldFilePath && file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }

            // 7. Recalcular Progreso de Onboarding
            $newProgress = $this->calculateOnboardingProgress($supplierId);

            // --- INICIO DE INTEGRACIÓN DE NOTIFICACIÓN ---
            /**
             * Lógica de Disparo: Solo notificamos si el expediente llegó al 100%.
             * Usamos el motor dinámico de distribución para no hardcodear correos.
             */
            if ($newProgress === 100) {
                $supplier = $this->proveedorModel->getById($supplierId);

                // Resolvemos quiénes deben recibir el correo
                $recipients = $this->usuarioModel->resolveRecipients('supplier_ready_for_approval', $supplier['id_planta'] ?? 0);
                
                if (empty($recipients)) {
                    $recipients = [MAIL_WEBMASTER];
                }
                
                // Preparamos los datos para el template "LDR Premium" que diseñamos
                $emailData = [
                    'razon_social'    => $supplier['razon_social'],
                    'rfc'             => $supplier['rfc'],
                    'origen'          => $supplier['origen'],
                    'tipo'            => $supplier['tipo'],
                    'created_at'      => date('d/m/Y', strtotime($supplier['created_at'])),
                    'link_expediente' => base_url() . "/prv_proveedor/edit?id=" . $supplierId
                ];

                // El método sendNotification buscará en 'sys_notification_distribution' 
                // a quién avisarle según el evento y la planta del proveedor.
                $this->sendNotification(
                    'supplier_ready_for_approval', 
                    $emailData, 
                    $recipients
                );
            }
            // --- FIN DE INTEGRACIÓN ---

            return ServiceResponse::success(
                ['progress' => $newProgress], 
                "Documento '{$docType}' guardado correctamente."
            );

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return \ServiceResponse::validation(errors: $i->getMessage());
        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'auditDocument',
                'id_user' => $userContext['id']
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage());
        }
    }

    /**
     * Calcula qué porcentaje de los documentos obligatorios ya fueron subidos.
     */
    private function calculateOnboardingProgress(int $supplierId): int
    {
        $requiredCount = 6; // Asumido segun tu UI
        $uploadedCount = $this->expedienteModel->countDocumentsBySupplier($supplierId);
        
        $progress = (int)(($uploadedCount / $requiredCount) * 100);
        return $progress > 100 ? 100 : $progress;
    }

    /**
     * 
     */
    public function documents(int $supplierId): ServiceResponse
    {
        // 1. Obtener el perfil del proveedor para saber qué exigirle
        $supplier = $this->proveedorModel->getById($supplierId);
        if (!$supplier) throw new Exception("Proveedor inexistente.");

        // 2. Obtener lista dinámica basada en el Enum
        $requiredDocs = DocumentTypeEnum::getRequiredList($supplier['id_tipo_persona'], $supplier['origen']);
        
        // 3. Obtener lo que ya subió
        $uploaded = $this->expedienteModel->uploadedDocuments($supplierId);        
        $uploadedIndexed = array_column($uploaded, null, 'tipo_documento');

        // 4. Cruzar información
        $finalList = [];
        $approvedCount = 0;

        foreach ($requiredDocs as $key => $config) {
            $fileInfo = $uploadedIndexed[$key] ?? null;
            $finalList[$key] = array_merge($config, [
                'uploaded'  => (bool)$fileInfo,
                'file_data' => $fileInfo,
            ]);

            if (isset($fileInfo['estatus_validacion']) && (int)$fileInfo['estatus_validacion'] === 1) {
                $approvedCount++;
            }
        }

        $progress = round(($approvedCount / count($requiredDocs)) * 100);
        
        return ServiceResponse::success([
            'documents' => $finalList,
            'progress' => $progress
        ]);
    }

    /**
     * Procesa el dictamen de validación de un documento (Aprobación o Rechazo).
     * Si todos los documentos obligatorios son aprobados, el proveedor cambia a estatus 'Aprobado'.
     *
     * @param array $userContext Contexto del usuario autenticado (JWT).
     * @return ServiceResponse
     */
    public function auditDocument(array $userContext): ServiceResponse
    {
        $request = new \Requests\Supplier\AuditDocumentRequest();

        try {
            $request->validate(); // Lanza Exception 422 si las reglas fallan
            $data = $request->all();

            $this->db->beginTransaction();

            $idDoc = (int)$data['id_documento'];
            $status = (int)$data['estatus_validacion'];
            $motivo = $data['motivo_rechazo'] ?? null;
            $supplierId = (int)$data['id_proveedor'];
            $userId = (int)$userContext['id'];

            $supplierData  = $this->proveedorModel->getById($supplierId);
            if (!$supplierData) throw new \Exception("No se encontró el proveedor.", 404);

            // 1. Actualizar el estatus del documento
            $updated = $this->expedienteModel->updateDocumentStatus($idDoc, $status, $motivo, $userId);
            if (!$updated) throw new \Exception("No se encontró el documento para auditar.", 404);

            // 2. Determinar acción de auditoría para el log
            $auditAction = ($status === 1) ? AuditAction::APPROVE_L1 : AuditAction::REJECTED;
            $logMsg = ($status === 1) ? "Documento verificado correctamente." : "Documento rechazado: " . $motivo;

            $this->proveedorModel->logAudit($supplierId, $auditAction, $logMsg, $userId);

            // 3. LÓGICA PRO: Verificar si el Onboarding se completó con esta aprobación
            $onboardingComplete = false;
            if ($status === 1) {
                $onboardingComplete = $this->checkAndActivateSupplier($supplierId, $userId);

                if ($onboardingComplete) {
                    // 1. Resolvemos quiénes deben recibir el correo
                    $recipients = $this->usuarioModel->resolveRecipients('supplier_onboarding_complete', $supplierData['id_planta']);
                    
                    if (empty($recipients)) {
                       ['erick.pulido@ldrsolutions.com.mx'];
                    }
                    // NOTIFICACIÓN: Documentos Listos
                    $this->sendNotification(
                        'supplier_onboarding_complete',
                        [
                            'razon_social'    => $supplierData['razon_social'],
                            'rfc'             => $supplierData['rfc'],
                            'origen'          => $supplierData['origen'],
                            'tipo'            => $supplierData['tipo'],
                            'created_at'      => date('d/m/Y', strtotime($supplierData['created_at'])),
                            'link_expediente' => base_url() . "/prv_proveedor/edit?id=" . $supplierId
                        ],
                        $recipients
                    );
                }
            }

            $this->db->commit();

            return ServiceResponse::success(
                ['proveedor_activado' => $onboardingComplete], 
                "Dictamen registrado exitosamente."
            );

        } catch (\InvalidArgumentException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::validation(errors: $e->getMessage());

        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'auditDocument',
                'id_user' => $userContext['id']
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage(), (int)$e->getCode() ?: 500);
        }
    }

    /**
     * Verifica si el proveedor ya cuenta con todos sus documentos aprobados 
     * basándose en su perfil dinámico (Física/Moral/Origen).
     * Si el expediente está completo, realiza la transición a estatus 'Aprobado'.
     * 
     * @param int $supplierId ID del proveedor a validar.
     * @param int $adminId    ID del administrador que realiza la acción.
     * @return bool True si el proveedor fue activado en este paso.
     */
    private function checkAndActivateSupplier(int $supplierId, int $adminId): bool
    {
        // 1. Obtener los datos maestros para conocer el perfil fiscal (tipo persona y origen)
        $supplier = $this->proveedorModel->getById($supplierId);
        if (!$supplier) return false;

        // 2. Consultar al Enum cuántos documentos se requieren para este perfil exacto
        $requiredDocsList = DocumentTypeEnum::getRequiredList(
            $supplier['id_tipo_persona'], 
            $supplier['origen']
        );
        $requiredCount = count($requiredDocsList);

        // 3. Contamos cuántos documentos tienen estatus_validacion = 1 (Aprobado) en la BD
        $approvedCount = $this->expedienteModel->countApprovedDocuments($supplierId);

        // 4. Lógica de activación dinámica
        // Validamos que el conteo de aprobados sea igual o mayor al requerido para este perfil
        if ($requiredCount > 0 && $approvedCount >= $requiredCount) {
            
            // El proveedor ha cumplido con su onboarding específico
            $this->proveedorModel->updateOnboardingStatus($supplierId, 'Aprobado', $adminId);
            
            // Auditoría con detalle de la métrica de cumplimiento
            $this->proveedorModel->logAudit(
                $supplierId, 
                AuditAction::FINALIZED, 
                "Onboarding completado con éxito. Cumplimiento de expediente: {$approvedCount}/{$requiredCount} documentos aprobados.", 
                $adminId
            );

            return true;
        }

        return false;
    }

    /**
     * Obtiene el estado detallado de cada hito del onboarding.
     * HU: Visualización de Progreso en Timeline.
     */
    public function getOnboardingTimeline(int $supplierId): ServiceResponse
    {
        $supplier = $this->proveedorModel->getById($supplierId);
        if (!$supplier) return ServiceResponse::error("Proveedor no encontrado", 404);

        // 1. Cálculos de Documentación (Uso del Enum dinámico)
        $requiredDocs = DocumentTypeEnum::getRequiredList($supplier['id_tipo_persona'], $supplier['origen']);
        $totalRequired = count($requiredDocs);
        $uploadedCount = $this->expedienteModel->countDocumentsBySupplier($supplierId);
        $approvedCount = $this->expedienteModel->countApprovedDocuments($supplierId);

        // 2. Definición de la lógica de cada paso
        $steps = [
            'step1' => [ // Registro Inicial
                'status' => 'completed', 
                'date' => date('d M, Y', strtotime($supplier['created_at'])),
                'badge' => 'Completado'
            ],
            'step2' => [ // Expediente Digital
                'status' => ($uploadedCount >= $totalRequired) ? 'completed' : 'active',
                'date' => ($uploadedCount > 0) ? 'En proceso' : 'Pendiente',
                'badge' => "{$uploadedCount} de {$totalRequired} subidos"
            ],
            'step3' => [ // Validación (Mesa de Control)
                'status' => ($approvedCount >= $totalRequired) ? 'completed' : (($uploadedCount >= $totalRequired) ? 'active' : 'pending'),
                'badge' => ($approvedCount >= $totalRequired) ? 'Verificado' : 'Esperando Aprobación'
            ],
            'step4' => [ // Alta en ERP
                'status' => ($supplier['estatus_onboarding'] === 'Aprobado') ? 'completed' : 'pending',
                'badge' => ($supplier['estatus_onboarding'] === 'Aprobado') ? 'Activo' : 'Esperando Alta'
            ]
        ];

        return ServiceResponse::success([
            'current_status_text' => $supplier['estatus_onboarding'],
            'steps' => $steps
        ]);
    }

    /**
     * Registro de Cuenta Bancaria con soporte internacional y anti-fraude.
     * HU: Gestión Bancaria Premium.
     */
    public function storeBank(array $userContext): ServiceResponse
    {
        // 1. Instanciar tu validador con los datos recibidos
        $request = new StoreBankAccountRequest();

        try {
            $request->validate(); // Ejecuta Fase 1 y Fase 2 de FormRequest
            $validated = $request->all();
            
            $userId = (int)$userContext['id'];
            $supplierId = (int)$validated['id_proveedor']; // Validado en el request

            // Obtenemos el archivo ya validado de forma segura del request
            $file = $request->files()['caratula_pdf'];

            // 2. Mover archivo al almacenamiento físico (Hostinger)
            $relativeDir = "Assets/uploads/expedientes/prov_{$supplierId}/";
            $uploadPath  = __DIR__ . '/../' . $relativeDir; // Ajustar según estructura de carpetas de tu WSL

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fileName = "caratula_bancaria_" . bin2hex(random_bytes(4)) . ".pdf";
            $fullPath = $uploadPath . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new \Exception("Error técnico al mover el archivo de carátula al servidor.", 500);
            }

            $urlPdf = $relativeDir . $fileName;

            $this->db->beginTransaction();

            // 2. Lógica de Cuenta Principal
            // En tu request se llama 'banco_es_principal'
            $esPrincipal = (int)($validated['banco_es_principal'] ?? 0);
            if ($esPrincipal === 1) {
                $this->bankModel->resetPrincipalAccounts($supplierId);
            }

            // 3. Preparar data para el Modelo (Mapeo de UI a DB)
            // Función de utilidad rápida para convertir '' en NULL
            $nullify = fn($value) => (trim((string)$value) === '') ? null : trim((string)$value);

            $bankData = [
                'id_proveedor'       => $supplierId,
                'id_banco'           => $validated['id_banco'],
                'id_moneda'          => $validated['id_moneda_banco'],
                'cuenta'             => $nullify($validated['cuenta'] ?? null),
                'clabe'              => $nullify($validated['clabe'] ?? null), // <--- AQUÍ ESTÁ EL FIX
                'swift_bic'          => $nullify($validated['swift_bic'] ?? null),
                'iban'               => $nullify($validated['iban'] ?? null),
                'url_pdf'            => $urlPdf,
                'es_principal'       => (int)($validated['banco_es_principal'] ?? 0),
                'estatus_aprobacion' => 'PENDIENTE',
                'created_by'         => $userId
            ];

            $idCuenta = $this->bankModel->save($bankData);

            // 4. Auditoría
            $this->proveedorModel->logAudit(
                $supplierId, 
                AuditAction::CREATED, 
                "Nueva cuenta bancaria registrada ({$validated['id_moneda_banco']}). Pendiente de validación L2.", 
                $userId
            );

            $this->db->commit();

            return ServiceResponse::success(
                ['id' => $idCuenta], 
                "Datos bancarios registrados correctamente.", 
                201
            );

        } catch (\InvalidArgumentException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::validation(errors: $e->getMessage());

        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'storeBank',
                'id_user' => $userContext['id']
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage(), (int)$e->getCode() ?: 500);
        }
    }

    /**
     * Procesa la aprobación o rechazo de una cuenta bancaria (Compliance L2).
     * 
     * @param array $data { id_cuenta_bancaria, estatus_aprobacion }
     */
    public function auditBankAccount(array $data, array $userContext): ServiceResponse
    {
        $request = new \Requests\Supplier\AuditBankAccountRequest();

        try {
            $request->validate(); // Lanza Exception 422 si falla
            $data = $request->all();

            // 1. Solo ciertos roles pueden aprobar bancos (Seguridad RBAC)
            $role = RoleEnum::tryFrom((int)$userContext['rolid']);
            if ($role !== RoleEnum::ADMINISTRADOR && $role !== RoleEnum::GERENTE) {
                return ServiceResponse::error("Solo el área de Finanzas/Tesoreria puede autorizar cuentas bancarias.", 403);
            }

            $idCuenta = (int)$data['id_cuenta_bancaria'];
            $nuevoEstatus = $data['estatus_aprobacion']; // 'APROBADO' o 'RECHAZADO'

            $this->db->beginTransaction();

            // 2. Actualizar estatus en la tabla prv_det_cuentas_bancarias
            $success = $this->bankModel->updateApprovalStatus($idCuenta, $nuevoEstatus, $userContext['id']);
            if (!$success) throw new \Exception("No se pudo actualizar la cuenta.");

            // 3. Auditoría (Crítico para Antifraude)
            $auditAction = ($nuevoEstatus === 'APROBADO') ? AuditAction::APPROVE_L2 : AuditAction::REJECTED;
            $this->proveedorModel->logAudit($idCuenta, $auditAction, "Dictamen bancario: {$nuevoEstatus}", $userContext['id']);

            $this->db->commit();
            return ServiceResponse::success(null, "Cuenta bancaria marcada como {$nuevoEstatus}.");

        } catch (\InvalidArgumentException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::validation(errors: $e->getMessage());

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage());
        }
    }

    /**
     * Realiza el borrado lógico de una cuenta bancaria.
     * 
     * @param int $id ID de la cuenta a eliminar.
     * @param array $userContext Contexto del usuario autenticado.
     * @return ServiceResponse
     */
    public function deleteBank(int $id, array $userContext): ServiceResponse
    {
        try {
            $this->db->beginTransaction();

            // 1. Borrado Lógico (Soft Delete)
            $success = $this->bankModel->softDelete($id, $userContext['id']);
            if (!$success) throw new \Exception("No se pudo eliminar la cuenta seleccionada.");

            // 2. Auditoría
            $this->proveedorModel->logAudit($id, AuditAction::DELETED, "Baja de cuenta bancaria.", $userContext['id']);

            $this->db->commit();
            return ServiceResponse::success(null, "Cuenta bancaria eliminada con éxito.");

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error($e->getMessage());
        }
    }

    /**
     * 
     */
    public function banks(int $supplierId): ServiceResponse
    {
        $data = $this->bankModel->findBySupplierId($supplierId);
        return ServiceResponse::success($data);
    }

    /**
     * 
     */
    public function getKpis(array $userContext): ServiceResponse
    {
        $filters = [];
        $role = RoleEnum::tryFrom((int)$userContext['rolid']);
        $scope = $role?->getScope() ?? 'propio';

        // APLICACIÓN DE LA MATRIZ DE VISIBILIDAD
        $scopeFilters = match($scope) {
            'propio' => ['usuarioid' => (int)$userContext['id']],
            'planta'  => ['plantaid' => (int)$userContext['plantaid']],
            'total'  => true,
            default  => false
        };

        if(!empty($scopeFilters) && is_array($scopeFilters)) {
            $filters = $scopeFilters;
        }
        
        return ServiceResponse::success(
            $this->proveedorModel->getKpi($filters),
            'Datos obtenidos correctamente.',
            200
        );
    }
}