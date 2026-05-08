<?php

declare(strict_types=1);

use App\Libraries\Core\ServiceResponse;
use App\Enums\AuditAction;
use Requests\AccountsPayable\StoreInvoiceRequest;

class AccountsPayableService
{
    use \Loggable;

    private object $db;
    private readonly CxpModel $cxpModel;
    private readonly Prv_proveedoresModel $proveedorModel;
    private readonly Com_orden_compraModel $ocModel;

    public function __construct()
    {
        $this->cxpModel = new CxpModel();
        $this->proveedorModel = new Prv_cat_proveedoresModel();
        $this->ocModel = new Com_orden_compraModel();
        $this->db = $this->cxpModel->getConexion();
    }

    /**
     * Registra una nueva factura y crea el pasivo en CxP.
     * HU #149: Registro y Validación de Facturas.
     */
    public function store(array $userContext): ServiceResponse
    {
        $request = new StoreInvoiceRequest();

        try {
            // 1. VALIDACIÓN DE FORMA (Inputs)
            $request->validate();
            $payload = $request->all();

            // 2. VALIDACIÓN DE NEGOCIO (RFC y UUID)
            $this->validateInvoiceBusinessRules($payload);

            $this->db->beginTransaction();

            $userId = (int)$userContext['id'];
            $plantaId = (int)$userContext['plantaid'];
            $proveedorId = (int)$payload['id_proveedor'];

            // 3. CÁLCULO DE VENCIMIENTO (Regla de Negocio)
            $fechaVencimiento = $this->calculateDueDate($proveedorId, $payload['fecha_emision']);

            // 4. INSERTAR CABECERA DE FACTURA (Pasivo)
            $idFactura = $this->cxpModel->insertInvoice([
                'id_proveedor'      => $proveedorId,
                'plantaid'          => $plantaId,
                'uuid'              => $payload['uuid'],
                'serie'             => $payload['serie'] ?? null,
                'folio'             => $payload['folio'] ?? null,
                'rfc_emisor'        => $payload['rfc_emisor'],
                'fecha_emision'     => $payload['fecha_emision'],
                'fecha_vencimiento' => $fechaVencimiento,
                'id_moneda'         => $payload['id_moneda'] ?? 'MXN',
                'tipo_cambio'       => (float)($payload['tipo_cambio'] ?? 1.0),
                'subtotal'          => (float)$payload['subtotal'],
                'iva'               => (float)$payload['iva'],
                'total'             => (float)$payload['total'],
                'created_by'        => $userId
            ]);

            // 5. VINCULACIÓN CON ÓRDENES DE COMPRA (Three-Way Match Prep)
            // El payload debe traer un array 'compras' con [{idcompra: 1, monto: 100}, ...]
            if (!empty($payload['compras']) && is_array($payload['compras'])) {
                foreach ($payload['compras'] as $oc) {
                    $this->cxpModel->linkInvoiceToOrder($idFactura, (int)$oc['idcompra'], (float)$oc['monto']);
                    
                    // Opcional: Registrar en el log de la OC que ya tiene factura
                    $this->ocModel->logAudit(
                        (int)$oc['idcompra'], 
                        AuditAction::UPDATED->value, 
                        "Factura vinculada: {$payload['uuid']}", 
                        $userId
                    );
                }
            }

            // 6. AUDITORÍA GLOBAL
            $this->cxpModel->logAudit($idFactura, AuditAction::CREATED->value, "Registro de pasivo por factura UUID: {$payload['uuid']}", $userId);

            $this->db->commit();

            return ServiceResponse::success(
                ['idfactura' => $idFactura, 'vencimiento' => $fechaVencimiento],
                "Factura registrada exitosamente. Vence el: " . date('d/m/Y', strtotime($fechaVencimiento)),
                201
            );

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logMessage($e, \LogLevel::ERROR, ['action' => 'storeInvoice', 'payload' => $payload ?? []]);
            $code = $e->getCode() !== 0 ? (int)$e->getCode() : 500;
            return ServiceResponse::error($e->getMessage(), $code);
        }
    }

    /**
     * Valida RFC contra catálogo y evita duplicidad de UUID.
     */
    private function validateInvoiceBusinessRules(array $data): void
    {
        $proveedor = $this->proveedorModel->getById((int)$data['id_proveedor']);
        if (!$proveedor) throw new Exception("Proveedor no encontrado.", 404);

        if (strtoupper(trim($data['rfc_emisor'])) !== strtoupper(trim($proveedor['rfc']))) {
            throw new Exception("El RFC emisor no coincide con los datos del proveedor seleccionado.", 422);
        }

        if ($this->cxpModel->checkUuidExists($data['uuid'])) {
            throw new Exception("Error: El UUID {$data['uuid']} ya fue registrado anteriormente.", 409);
        }
    }

    /**
     * Obtiene los días de crédito del catálogo financiero para calcular el vencimiento.
     */
    private function calculateDueDate(int $proveedorId, string $fechaEmision): string
    {
        $config = $this->proveedorModel->getFinancialConfig($proveedorId);
        // id_condicion_pago podría ser un ID que mapea a días (ej: 1 = 30 días, 2 = 60 días)
        // Por simplicidad, asumo que devuelve un valor numérico de días.
        $diasCredito = (int)($config['dias_credito'] ?? 0); 

        $date = new \DateTime($fechaEmision);
        $date->modify("+{$diasCredito} days");
        
        return $date->format('Y-m-d');
    }
}