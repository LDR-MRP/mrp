<?php

declare(strict_types=1);

namespace Services;

use AccountsPayableInvoiceModel;
use ServiceResponse;

readonly class AccountsPayableInvoiceService
{
    public function __construct(
        private AccountsPayableInvoiceModel $facturaModel
    ) {}

    /**
     * Obtiene el listado de facturas para la bandeja administrativa del ERP.
     */
    public function getInvoices(array $filters, array $userContext): ServiceResponse
    {
        try {
            if (($userContext['rol'] ?? '') === 'VENDOR') {
                return ServiceResponse::error("Acceso denegado. Rol insuficiente.", 403);
            }

            $invoices = $this->facturaModel->getInvoicesList($filters);

            foreach ($invoices as &$inv) {
                $inv['id'] = (int) $inv['id'];
                $inv['id_compra'] = (int) $inv['id_compra'];
                $inv['codigo_oc'] = (int) $inv['codigo_oc'];
                $inv['monto_total'] = (float) $inv['monto_total'];
                $inv['estatus_validacion'] = (int) $inv['estatus_validacion'];
            }

            return ServiceResponse::success($invoices, "Listado de facturas recuperado.");

        } catch (\Exception $e) {
            return ServiceResponse::error("Error al obtener facturas: " . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene los contadores de los KPIs para el dashboard de Cuentas por Pagar.
     */
    public function getKpiSummary(array $userContext): ServiceResponse
    {
        try {
            if (($userContext['rol'] ?? '') === 'VENDOR') {
                return ServiceResponse::error("Acceso denegado.", 403);
            }

            $kpis = $this->facturaModel->getDashboardKpis();

            $kpis = [
                'congeladas' => (int) $kpis['congeladas'],
                'aprobadas'  => (int) $kpis['aprobadas'],
                'rechazadas' => (int) $kpis['rechazadas'],
                'vencidas'   => (int) $kpis['vencidas'],
            ];

            return ServiceResponse::success($kpis, "KPIs de CxP calculados.");

        } catch (\Exception $e) {
            return ServiceResponse::error("Error al calcular KPIs: " . $e->getMessage(), 500);
        }
    }

    /**
     * Permite a un administrador forzar la aprobación manual (Override) de una factura congelada.
     */
    public function forceApproval(int $invoiceId, string $comentario, array $userContext): ServiceResponse
    {
        try {
            if (($userContext['rol'] ?? '') === 'VENDOR') {
                return ServiceResponse::error("Acceso denegado.", 403);
            }

            $factura = $this->facturaModel->getById($invoiceId);
            if (!$factura) {
                return ServiceResponse::error("La factura no existe.", 404);
            }

            $auditMsg = "Liberación manual autorizada por {$userContext['nombre']}. Comentarios: {$comentario}";
            
            $this->facturaModel->updateValidationStatus($invoiceId, 1, $auditMsg);

            return ServiceResponse::success(null, "La factura ha sido liberada para pago manualmente.");

        } catch (\Exception $e) {
            return ServiceResponse::error("Error al autorizar factura: " . $e->getMessage(), 500);
        }
    }
}