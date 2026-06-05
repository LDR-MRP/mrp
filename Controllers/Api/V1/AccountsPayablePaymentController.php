<?php

namespace Controllers\Api\V1;

use Services\PaymentDispersalService;
use AccountsPayableInvoiceModel;
use Prv_proveedorModel;

class AccountsPayablePaymentController
{
    // Inyectamos tu Trait global para estandarizar las respuestas JSON
    use \ApiResponser; 

    private PaymentDispersalService $paymentService;

    public array $request = [];

    public function __construct()
    {        
        // Inyección manual de dependencias de acuerdo a tu arquitectura
        // Usamos tu modelo de facturas unificado AccountsPayableInvoiceModel
        $this->paymentService = new PaymentDispersalService(
            new AccountsPayableInvoiceModel(),
            new Prv_proveedorModel()
        );
    }

    /**
     * Obtiene el listado de facturas autorizadas listas para pago (pendientes de dispersión).
     * GET /api/v1/accounts-payable/payments/pending
     */
    public function index()
    {
        // 1. Obtener contexto inyectado de forma Stateless por el AuthMiddleware
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext) {
            return $this->errorResponse('Acceso no autorizado.', 401);
        }

        // 2. Delegar de forma estricta al servicio
        $response = $this->paymentService->getPending($userContext);

        // 3. Despachar respuesta con el Trait
        return $this->apiResponse($response);
    }

    /**
     * Genera el archivo Layout .txt de BBVA para dispersión masiva de pagos (SPEI).
     * POST /api/v1/accounts-payable/payments/generate-layout
     */
    public function generateLayout()
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext) {
            return $this->errorResponse('Acceso no autorizado.', 401);
        }

        // Obtener payload JSON crudo enviado por la bandeja de pagos en JS
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $invoiceIds = $payload['invoice_ids'] ?? [];

        // Obtenemos el banco de origen del payload (Si no viene, usamos BBVA por defecto)
        $bankOrigin = trim($payload['bank_origin'] ?? 'BBVA'); 

        if (empty($invoiceIds) || !is_array($invoiceIds)) {
            return $this->errorResponse('Es obligatorio enviar un array de IDs de facturas ("invoice_ids").', 422);
        }

        // Delegar procesamiento del layout al servicio
        $response = $this->paymentService->generateLayout($invoiceIds, $bankOrigin, $userContext);

        return $this->apiResponse($response);
    }
}