<?php
declare(strict_types=1);

namespace Services;

use Com_ordenCompraModel;
use AccountsPayableInvoiceModel;
use Inv_inventarioModel;
use LogAuditModel;
use ServiceResponse;

class DashboardService
{
    public function __construct(
        private readonly Com_ordenCompraModel $poModel = new Com_ordenCompraModel(),
        private readonly AccountsPayableInvoiceModel $cxpModel = new AccountsPayableInvoiceModel(),
        private readonly LogAuditModel $auditModel = new LogAuditModel(),
        private readonly Inv_inventarioModel $inventoryModel = new Inv_inventarioModel()
    ) {}

    public function getAdminSummary(array $userContext): ServiceResponse
    {
        try {
            // 1. Pendientes de Aprobación (Requisiciones/OCs según rol)
            // Aquí usamos los filtros que ya programamos en tus modelos
            $pendingPOs = $this->poModel->getDashboardKpis(['estatus' => 'emitida']);
            $totalPendingPOs = array_sum(array_column($pendingPOs, 'cantidad'));

            // 2. Facturas en "Congeladas" o "Pendientes"
            $cxpMetrics = $this->cxpModel->getDashboardKpis();
            
            // 3. Actividad Global (Últimos 15 movimientos de todo el ERP)
            $globalActivity = $this->auditModel->list(['limit' => 15]);

            $data = [
                'user' => [
                    'nombre' => $userContext['nombres'],
                    'rol'    => $userContext['rol_nombre'],
                    'planta' => $userContext['planta_nombre']
                ],
                'stats' => [
                    'pos_pendientes'      => $totalPendingPOs,
                    'facturas_congeladas' => (int)$cxpMetrics['congeladas'],
                    'stock_critico'       => 12, // Dummy por ahora
                    'recepciones_hoy'     => 5   // Dummy por ahora
                ],
                'activity' => $globalActivity
            ];

            return ServiceResponse::success($data, "Dashboard administrativo cargado.");
        } catch (\Exception $e) {
            return ServiceResponse::error($e->getMessage());
        }
    }
}