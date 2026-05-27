<?php
namespace Controllers\Api\V1;

use Services\AccountsPayableInvoiceService;
use AccountsPayableInvoiceModel;

class AccountsPayableInvoiceController
{
    use \ApiResponser;

    private AccountsPayableInvoiceService $invoiceService;

    public array $request = [];

    public function __construct()
    {
        $this->invoiceService = new AccountsPayableInvoiceService(new AccountsPayableInvoiceModel());
    }

    /**
     * GET /api/v1/cxp/invoices
     */
    public function index()
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext) {
            return $this->errorResponse('Acceso no autorizado.', 401);
        }

        // Filtros Query Params
        $filters = [
            'proveedorid' => $_GET['proveedorid'] ?? null,
            'estatus'     => $_GET['estatus'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];

        $response = $this->invoiceService->getInvoices($filters, $userContext);
        return $this->apiResponse($response);
    }

    /**
     * GET /api/v1/cxp/invoices/kpis
     */
    public function getKpis()
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext) {
            return $this->errorResponse('Acceso no autorizado.', 401);
        }

        $response = $this->invoiceService->getKpiSummary($userContext);
        return $this->apiResponse($response);
    }

    /**
     * POST /api/v1/cxp/invoices/override
     */
    public function override()
    {
        $userContext = $this->request['auth_user'] ?? null;

        if (!$userContext) {
            return $this->errorResponse('Acceso no autorizado.', 401);
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $invoiceId = (int) ($payload['id_factura'] ?? 0);
        $comentario = trim($payload['comentarios'] ?? '');

        if ($invoiceId <= 0 || empty($comentario)) {
            return $this->errorResponse('ID de factura y comentarios de justificación obligatorios.', 422);
        }

        $response = $this->invoiceService->forceApproval($invoiceId, $comentario, $userContext);
        return $this->apiResponse($response);
    }
}