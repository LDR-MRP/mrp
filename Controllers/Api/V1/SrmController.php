<?php

namespace Controllers\Api\V1;

use SupplierService;
class SrmController
{
    // Inyectamos el trait nativo para estandarizar respuestas
    use \ApiResponser; 

    private SupplierService $supplierService;
    public array $request = [];

    public function __construct()
    {
        $this->supplierService = new \SupplierService();
    }

    /**
     * GET /api/v1/srm/dashboard/summary
     */
    public function getSummary()
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext || $userContext['rol'] !== 'VENDOR') {
            return $this->errorResponse('Acceso denegado. Credenciales insuficientes.', 403);
        }

        $vendorId = (int) ($userContext['vendor_id'] ?? 0);
        if ($vendorId <= 0) {
            return $this->errorResponse('Perfil de proveedor no asociado.', 400);
        }

        // El servicio retorna un ServiceResponse...
        $response = $this->supplierService->getSummary($vendorId);

        // ...y el Trait se encarga de formatear, poner los headers, el código HTTP y hacer el exit;
        return $this->apiResponse($response); 
    }

    /**
     * Endpoint para obtener el estatus del expediente.
     * GET /api/v1/srm/dossier
     */
    public function getDossier(): string
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext || $userContext['rol'] !== 'VENDOR') {
            return $this->errorResponse('Acceso denegado. Credenciales insuficientes.', 403);
        }
        
        $vendorId = (int) ($userContext['vendor_id'] ?? 0);
        if ($vendorId <= 0) {
            return $this->errorResponse('Perfil de proveedor no asociado.', 400);
        }

        $response = $this->supplierService->documents($vendorId);

        return $this->apiResponse($response);
    }

    /**
     * Procesa la subida física del documento del expediente del proveedor.
     * POST /api/v1/srm/dossier/upload
     */
    public function uploadDocument()
    {
        // 1. Obtener contexto inyectado por tu JwtAuthMiddleware
        $userContext = $this->request['auth_user'] ?? null;

        // 2. Bloqueo estricto contra roles administrativos o nulos
        if (!$userContext || $userContext['rol'] !== 'VENDOR') {
            return $this->errorResponse('Acceso denegado. Requiere cuenta de proveedor activa.', 403);
        }

        // =================================================================
        // INYECCIÓN DEFENSIVA (Cerrar el circuito con el FormRequest)
        // =================================================================
        // Sobrescribimos o inyectamos el id_proveedor real del JWT en las 
        // variables globales antes de que el Request las lea y las valide.
        // Si un atacante intentó mandar un id_proveedor falso, aquí lo destruimos.
        $_POST['id_proveedor'] = (int)$userContext['vendor_id'];
        $_REQUEST['id_proveedor'] = (int)$userContext['vendor_id'];
        // =================================================================

        // 3. Ejecutar la lógica de negocio en el servicio (que internamente previene el IDOR)
        $response = $this->supplierService->uploadDocument($userContext);

        // 4. Responder usando el estándar de tu Trait
        return $this->apiResponse($response);
    }

    /**
     * Obtiene las cuentas bancarias registradas del proveedor autenticado.
     * GET /api/v1/srm/bank-accounts
     */
    public function getBankAccounts()
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext || $userContext['rol'] !== 'VENDOR') {
            return $this->errorResponse('Acceso denegado.', 403);
        }

        $response = $this->supplierService->banks($userContext['vendor_id']);
        return $this->apiResponse($response);
    }

    /**
     * Registra una nueva cuenta bancaria desde el SRM de forma Stateless.
     * POST /api/v1/srm/bank-accounts
     */
    public function storeBankAccount()
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext || $userContext['rol'] !== 'VENDOR') {
            return $this->errorResponse('Acceso denegado.', 403);
        }

        $_POST['id_proveedor'] = (int) $userContext['vendor_id'];
        $_REQUEST['id_proveedor'] = (int) $userContext['vendor_id'];

        $response = $this->supplierService->storeBank($userContext);
        
        return $this->apiResponse($response);
    }
}