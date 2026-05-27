<?php
namespace Controllers\Api\V1;

use Com_ordenCompraModel;
use AccountsPayableInvoiceModel;
use Services\InvoiceValidationService;
use Prv_proveedorModel;
use ThreeWayMatchService;

class SrmInvoiceController
{
    use \ApiResponser; // Tu trait estándar de respuestas

    private InvoiceValidationService $validationService;

    public array $request = [];

    public function __construct()
    {
        // 1. Instanciamos los modelos base
        $ordenCompraModel = new Com_ordenCompraModel();
        $proveedorModel   = new Prv_proveedorModel();
        $facturaModel     = new AccountsPayableInvoiceModel();

        // 2. Instanciamos el motor de 3-Way Match (Requiere facturaModel y ordenCompraModel)
        $threeWayMatchService = new ThreeWayMatchService(
            $facturaModel, 
            $ordenCompraModel
        );

        // 3. Inyectamos los 4 argumentos requeridos en el constructor de tu servicio
        $this->validationService = new InvoiceValidationService(
            $ordenCompraModel,
            $proveedorModel,
            $facturaModel,
            $threeWayMatchService
        );
    }

    /**
     * Obtiene el historial de facturas cargadas exclusivamente por el proveedor autenticado.
     * GET /api/v1/srm/invoices
     */
    public function index()
    {
        // 1. Obtener contexto inyectado por tu Middleware (JWT)
        $userContext = $this->request['auth_user'] ?? null;

        // 2. Defensa en profundidad: Validar rol del JWT
        if (!$userContext || $userContext['rol'] !== 'VENDOR') {
            return $this->errorResponse('Acceso denegado. Credenciales insuficientes.', 403);
        }

        // 3. DELEGACIÓN ESTRICTA: El controlador no sabe de BD, le pasa el control al servicio
        $response = $this->validationService->getHistory($userContext);

        return $this->apiResponse($response);
    }

    /**
     * POST /api/v1/srm/invoices/upload
     * Acceso: Exclusivo para proveedores (SRM)
     */
    public function uploadInvoice()
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext || $userContext['rol'] !== 'VENDOR') {
            return $this->errorResponse('Acceso denegado. Credenciales insuficientes.', 403);
        }

        // Ejecutar el servicio inyectando el $_POST y $_FILES
        $response = $this->validationService->validateAndUpload($userContext, $_POST, $_FILES);

        return $this->apiResponse($response);
    }
}