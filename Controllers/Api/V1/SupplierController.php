<?php
namespace Controllers\Api\V1;

class SupplierController
{
    use \ApiResponser;

    private readonly \SupplierService $supplierService;

    public array $request = [];

    public function __construct()
    {
        $this->supplierService = new \SupplierService;
    }

    public function index()
    {
        return $this->apiResponse($this->supplierService->findByCriteria());
    }

    public function show(string $id)
    {
        return $this->apiResponse($this->supplierService->findByCriteria(['id_proveedor' => $id]));
    }

    /**
     * Procesa el registro o actualización integral del proveedor.
     * POST /api/v1/suppliers
     */
    public function store(): string
    {
        // Enviamos el payload y el contexto del usuario (Stateless)
        $response = $this->supplierService->store($this->request['auth_user']);
        
        return $this->apiResponse($response);
    }

    /**
     * Endpoint para subida física de documentos.
     * POST /api/v1/suppliers/documents
     */
    public function uploadDocument(): string
    {
       
        $response = $this->supplierService->uploadDocument($this->request['auth_user']);
        
        return $this->apiResponse($response);
    }

    /**
     * Endpoint para obtener el estatus del expediente.
     * GET /api/v1/suppliers/{id}/documents
     */
    public function getDocuments(int $id): string
    {
        $response = $this->supplierService->documents($id);
        return $this->apiResponse($response);
    }

    /**
     * Procesa la aprobación o rechazo de un documento del expediente.
     * POST /api/v1/suppliers/audit-document
     *
     * @return string Respuesta JSON estandarizada.
     */
    public function auditDocument(): string
    {
        // El Service ya tiene la lógica de activar al proveedor automáticamente
        $response = $this->supplierService->auditDocument($this->request['auth_user']);        
        return $this->apiResponse($response);
    }

    /**
     * Procesa la autorización o rechazo de una cuenta bancaria.
     * Acción restringida a perfiles de Tesorería/Gerencia (Compliance L2).
     * 
     * POST /api/v1/suppliers/audit-bank
     *
     * @return string Respuesta JSON estandarizada.
     */
    public function auditBankAccount(): string
    {
        // El AuthMiddleware ya validó el JWT y cargó al usuario en $this->request
        $response = $this->supplierService->auditBankAccount(
            $this->request, 
            $this->request['auth_user']
        );
        
        return $this->apiResponse($response);
    }

    /**
     * Obtiene el listado de cuentas bancarias asociadas a un proveedor.
     * GET /api/v1/suppliers/{id}/banks
     */
    public function getBanks(int $id): string
    {
        $response = $this->supplierService->banks($id);
        return $this->apiResponse($response);
    }

    /**
     * Registra una nueva cuenta bancaria en estado 'PENDIENTE'.
     * POST /api/v1/suppliers/store-bank
     */
    public function storeBank(): string
    {
        // El Service se encarga de la validación y el registro
        $response = $this->supplierService->storeBank($this->request['auth_user']);
        return $this->apiResponse($response);
    }

    /**
     * Realiza el borrado lógico de una cuenta bancaria.
     * DELETE /api/v1/suppliers/banks/{id}
     */
    public function deleteBank(int $id): string
    {
        // Pasamos el ID del recurso y quién lo elimina para la auditoría
        $response = $this->supplierService->deleteBank(
            $id, 
            $this->request['auth_user']
        );
        return $this->apiResponse($response);
    }

    public function getOnboardingTimeline(int $id): string
    {
        return $this->apiResponse($this->supplierService->getOnboardingTimeline($id));
    }

    // GET /api/v1/suppliers/kpis
    public function kpis()
    {
        return $this->apiResponse($this->supplierService->getKpis($this->request['auth_user']));
    }

    /**
     * Obtiene el reporte analítico del progreso de onboarding para el CEO.
     * GET /api/v1/suppliers/reports/onboarding
     */
    public function getOnboardingReport()
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext) {
            return $this->errorResponse('Acceso no autorizado.', 401);
        }

        // Capturamos el filtro opcional de la URL
        $filters = [
            'plantaid' => $_GET['plantaid'] ?? null
        ];

        $response = $this->supplierService->getOnboardingReport($filters, $userContext);
        return $this->apiResponse($response);
    }
}