<?php

declare(strict_types=1);

namespace Services;

use Prv_detCuentaBancariaModel;
use Prv_proveedorModel;
use ServiceResponse;

readonly class AccountsPayableBankService
{
    // Cuenta de retiro de LDR Solutions registrada en BBVA (10 dígitos obligatorios)
    private const CUENTA_RETIRO_BBVA = '0123456789';
    private const CUENTA_RETIRO_BANORTE = '9876543210';

    public function __construct(
        private Prv_detCuentaBancariaModel $bankModel,
        private Prv_proveedorModel $proveedorModel
    ) {}

    /**
     * Obtiene las cuentas bancarias de un proveedor específico para el Portal SRM.
     */
    public function getSupplierAccounts(array $userContext): ServiceResponse
    {
        try {
            $vendorId = (int) ($userContext['vendor_id'] ?? 0);
            if ($vendorId <= 0) {
                return ServiceResponse::error("Perfil de proveedor no asociado.", 400);
            }

            $accounts = $this->bankModel->findBySupplierId($vendorId);

            foreach ($accounts as &$acc) {
                $acc['id_cuenta_bancaria'] = (int)$acc['id_cuenta_bancaria'];
                $acc['id_proveedor'] = (int)$acc['id_proveedor'];
                $acc['es_principal'] = (int)$acc['es_principal'];
            }

            return ServiceResponse::success($accounts, "Cuentas bancarias recuperadas.");

        } catch (\Exception $e) {
            return ServiceResponse::error("Error al obtener cuentas: " . $e->getMessage(), 500);
        }
    }

    /**
     * Procesa la captura segura de datos bancarios y carga el PDF en Hostinger desde el SRM.
     */
    public function storeSupplierAccount(array $userContext, array $postData, ?array $file): ServiceResponse
    {
        try {
            $vendorId = (int) ($userContext['vendor_id'] ?? 0);
            if ($vendorId <= 0) {
                return ServiceResponse::error("Acceso denegado. Sin perfil de proveedor asociado.", 403);
            }

            $idBanco   = trim($postData['id_banco'] ?? '');
            $idMoneda  = trim($postData['id_moneda'] ?? 'MXN');
            $clabe     = trim($postData['clabe'] ?? '');
            $cuenta    = trim($postData['cuenta'] ?? '');
            $isPrimary = (int)($postData['es_principal'] ?? 0);

            if (empty($idBanco) || empty($clabe) || empty($cuenta)) {
                return ServiceResponse::validation("El banco, la cuenta y la CLABE son campos obligatorios.");
            }

            // A. DEVSECOPS (Antifraude): Validar formato estricto de CLABE (18 dígitos)
            if (!preg_match('/^[0-9]{18}$/', $clabe)) {
                return ServiceResponse::validation("La CLABE interbancaria es inválida (debe tener exactamente 18 dígitos).");
            }

            // B. DEVSECOPS (Antifraude): Validar que la CLABE no esté duplicada
            $existingClabe = $this->bankModel->findByClabe($clabe);
            if ($existingClabe) {
                return ServiceResponse::error("Esta CLABE interbancaria ya se encuentra registrada en el sistema por otro proveedor.", 409);
            }

            // C. Validar carátula bancaria PDF
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                return ServiceResponse::validation("Es obligatorio cargar el PDF de la carátula bancaria para validación.");
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            if ($finfo->file($file['tmp_name']) !== 'application/pdf') {
                return ServiceResponse::validation("Solo se permiten carátulas bancarias en formato PDF original.");
            }

            // D. Mover archivo físico al Storage
            $relativeDir = "Assets/uploads/expedientes/prov_{$vendorId}/";
            $physicPath  = __DIR__ . '/../../../' . $relativeDir; // Ajuste de nivel en tus directorios de WSL/Hostinger

            if (!is_dir($physicPath)) {
                mkdir($physicPath, 0755, true);
            }

            $fileName = "caratula_bancaria_" . bin2hex(random_bytes(4)) . ".pdf";
            
            if (!move_uploaded_file($file['tmp_name'], $physicPath . $fileName)) {
                throw new \Exception("Error al guardar la carátula bancaria en el servidor.");
            }

            // E. Transacción lógica: Resetear principal si aplica y Guardar
            $this->bankModel->getConexion()->beginTransaction();

            if ($isPrimary === 1) {
                $this->bankModel->resetPrincipalAccounts($vendorId);
            }

            $accountId = $this->bankModel->save([
                'id_proveedor'       => $vendorId,
                'id_banco'           => $idBanco,
                'id_moneda'          => $idMoneda,
                'cuenta'             => $cuenta,
                'clabe'              => $clabe,
                'swift_bic'          => $postData['swift_bic'] ?? null,
                'iban'               => $postData['iban'] ?? null,
                'es_principal'       => $isPrimary,
                'estatus_aprobacion' => 'PENDIENTE', // Nace estrictamente en revisión L2 [4]
                'created_by'         => (int)$userContext['id']
            ]);

            // Auditoría en bitácora
            $this->proveedorModel->logAudit($vendorId, 'UPLOAD_BANK_ACCOUNT', "Carga de cuenta bancaria. Id de Cuenta: {$accountId}", (int)$userContext['id']);

            $this->bankModel->getConexion()->commit();

            return ServiceResponse::success([
                'id_cuenta' => $accountId
            ], "Cuenta bancaria registrada con éxito. Queda pendiente de validación por Finanzas L2.");

        } catch (\Exception $e) {
            if ($this->bankModel->getConexion()->inTransaction()) {
                $this->bankModel->getConexion()->rollBack();
            }
            return ServiceResponse::error("Error al registrar cuenta bancaria: " . $e->getMessage(), 500);
        }
    }

    /**
     * Ejecuta el proceso de auditoría y validación para liberar/rechazar una cuenta bancaria (ERP).
     */
    public function evaluateAccount(int $accountId, int $estatus, ?string $comentarios, array $userContext): ServiceResponse
    {
        try {
            $adminId = (int)$userContext['id'];

            if (($userContext['role'] ?? '') === 'VENDOR') {
                return ServiceResponse::error("Acceso denegado. Privilegios insuficientes.", 403);
            }

            $account = $this->bankModel->getById($accountId);
            if (!$account) {
                return ServiceResponse::error("La cuenta bancaria no existe.", 404);
            }

            $clabe = trim($account['clabe'] ?? '');
            if (!preg_match('/^[0-9]{18}$/', $clabe)) {
                return ServiceResponse::error("La cuenta no puede ser autorizada: El formato de la CLABE es inválido.", 422);
            }

            if ($estatus === 2 && empty($comentarios)) {
                return ServiceResponse::validation("Para rechazar una cuenta bancaria es obligatorio ingresar el motivo de rechazo.");
            }

            // Traducimos los estatus numéricos al ENUM de tu base de datos: 'PENDIENTE', 'APROBADO', 'RECHAZADO'
            $dbStatus = match($estatus) {
                1 => 'APROBADO',
                2 => 'RECHAZADO',
                default => 'PENDIENTE'
            };

            $auditMsg = $dbStatus === 'APROBADO' 
                ? "Cuenta validada contra PDF por {$userContext['nombre']}." 
                : "Rechazado: " . trim($comentarios);

            $this->bankModel->auditAccount($accountId, $dbStatus, $adminId);

            $this->proveedorModel->logAudit(
                (int)$account['id_proveedor'], 
                'AUDIT_BANK_ACCOUNT', 
                "Auditoría bancaria realizada. Resultado: {$dbStatus}. Nota: {$auditMsg}", 
                $adminId
            );

            return ServiceResponse::success([
                'id_cuenta' => $accountId,
                'estatus_final' => $dbStatus
            ], "Auditoría completada exitosamente.");

        } catch (\Exception $e) {
            return ServiceResponse::error("Error en la auditoría antifraude: " . $e->getMessage(), 500);
        }
    }
}