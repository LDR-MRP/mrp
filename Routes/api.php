<?php
// Routes/api.php

use Controllers\Api\V1\AccountsPayableInvoiceController;
use Controllers\Api\V1\AccountsPayablePaymentController;
use Libraries\Core\Route;
use Controllers\Api\V1\AuthController;
use Controllers\Api\V1\CurrencyController;
use Controllers\Api\V1\InventoryReceptionController;
use Controllers\Api\V1\SupplierController;
use Controllers\Api\V1\RequisitionController;
use Controllers\Api\V1\PurchaseOrderController;
use Controllers\Api\V1\SourcingController;
use Controllers\Api\V1\SrmController;
use Controllers\Api\V1\SrmInvoiceController;
use Controllers\Api\V1\SrmPurchaseOrderController;
use Controllers\Api\V1\WarehouseController;
use Middlewares\AuthMiddleware;

// Rutas Públicas
// Endpoint público para obtener el token
Route::post('api/v1/login', [AuthController::class, 'login']);

// Rutas Protegidas
/**
 * ==============================================================================
 * RUTAS DE SUPPLIERS
 * ==============================================================================
 */
// Registro y Actualización de Proveedor (Maestro + Satélites)
Route::post('api/v1/suppliers', [SupplierController::class, 'store'])->middleware([AuthMiddleware::class]);
// Listado y Detalle
Route::get('api/v1/suppliers', [SupplierController::class, 'index'])->middleware([AuthMiddleware::class]);
Route::get('api/v1/suppliers/kpis', [SupplierController::class, 'kpis'])->middleware([AuthMiddleware::class]);
Route::get('api/v1/suppliers/{id}', [SupplierController::class, 'show'])->middleware([AuthMiddleware::class]);

// Expediente Digital
Route::post('api/v1/suppliers/documents', [SupplierController::class, 'uploadDocument'])->middleware([AuthMiddleware::class]);
Route::get('api/v1/suppliers/{id}/documents', [SupplierController::class, 'getDocuments'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/suppliers/audit-document', [SupplierController::class, 'auditDocument'])->middleware([AuthMiddleware::class]);

// --- GESTIÓN BANCARIA DE PROVEEDORES ---
// Listar cuentas de un proveedor específico
Route::get('api/v1/suppliers/{id}/banks', [SupplierController::class, 'getBanks'])->middleware([AuthMiddleware::class]);
// Registrar nueva cuenta
Route::post('api/v1/suppliers/store-bank', [SupplierController::class, 'storeBank'])->middleware([AuthMiddleware::class]);
// Aprobar o Rechazar cuenta (Compliance L2)
Route::post('api/v1/suppliers/audit-bank', [SupplierController::class, 'auditBankAccount'])->middleware([AuthMiddleware::class]);
// Eliminar cuenta (Soft Delete)
Route::delete('api/v1/suppliers/banks/{id}', [SupplierController::class, 'deleteBank'])->middleware([AuthMiddleware::class]);
// Onboarding
Route::get('api/v1/suppliers/{id}/onboarding-timeline', [SupplierController::class, 'getOnboardingTimeline'])->middleware([AuthMiddleware::class]);
/**
 * Reporte Ejecutivo de Onboarding (CEO)
 * GET /api/v1/suppliers/reports/onboarding
 */
Route::get('api/v1/suppliers/reports/onboarding', [SupplierController::class, 'getOnboardingReport'])
    ->middleware([AuthMiddleware::class]);

/**
 * ==============================================================================
 * RUTAS DE DISPERSIÓN DE PAGOS (ACCOUNTS PAYABLE V1)
 * ==============================================================================
 */
Route::get('api/v1/accounts-payable/payments/pending', [AccountsPayablePaymentController::class, 'index'])->middleware([AuthMiddleware::class]);

Route::post('api/v1/accounts-payable/payments/generate-layout', [AccountsPayablePaymentController::class, 'generateLayout'])->middleware([AuthMiddleware::class]);

/**
 * ==============================================================================
 * RUTAS DE REQUISICIONES
 * ==============================================================================
 */
Route::get('api/v1/requisitions', [RequisitionController::class, 'index'])->middleware([AuthMiddleware::class]);
Route::get('api/v1/requisitions/kpis', [RequisitionController::class, 'kpis'])->middleware([AuthMiddleware::class]);
Route::get('api/v1/requisitions/{id}', [RequisitionController::class, 'show'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/requisitions', [RequisitionController::class, 'store'])->middleware([AuthMiddleware::class]);
Route::put('api/v1/requisitions/{id}', [RequisitionController::class, 'update'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/requisitions/{id}/items', [RequisitionController::class, 'createItem'])->middleware([AuthMiddleware::class]);
Route::delete('api/v1/requisitions/{id}/items/{item_id}', [RequisitionController::class, 'deleteItem'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/requisitions/{id}/items/move', [RequisitionController::class, 'moveItems'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/requisitions/{id}/submit', [RequisitionController::class, 'submit'])->middleware([AuthMiddleware::class]);
// Rutas de cambio de estado (Máquina de Estados)
Route::post('api/v1/requisitions/{id}/approve', [RequisitionController::class, 'approve'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/requisitions/{id}/reject', [RequisitionController::class, 'reject'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/requisitions/{id}/cancel', [RequisitionController::class, 'cancel'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/requisitions/{id}/return-to-draft', [RequisitionController::class, 'returnToDraft'])->middleware([AuthMiddleware::class]);
// Ruta de eliminación
Route::delete('api/v1/requisitions/{id}', [RequisitionController::class, 'destroy'])->middleware([AuthMiddleware::class]);
// Ruta de PDF
Route::get('api/v1/requisitions/{id}/pdf', [RequisitionController::class, 'generatePdf'])->middleware([AuthMiddleware::class]);
// Obtener las partidas pendientes de compra de una requisición
Route::get('api/v1/requisitions/{id}/pending-items', [RequisitionController::class, 'getPendingItems'])->middleware([AuthMiddleware::class]);

/**
 * ==============================================================================
 * RUTAS DE SOURCING
 * ==============================================================================
 */
// Guarda o actualiza la ficha técnica y precio objetivo de una partida que no existe en el catálogo maestro.
Route::post('api/v1/requisitions/special-specs', [RequisitionController::class, 'storeSpecialSpecs'])->middleware([AuthMiddleware::class]);
// Obtener tabla comparativa
Route::get('api/v1/sourcing/comparison/{id}', [SourcingController::class, 'getComparison'])->middleware([AuthMiddleware::class]);
// Guardar nueva cotización (Inyecta archivo PDF)
Route::post('api/v1/sourcing/quotations', [SourcingController::class, 'addQuotation'])->middleware([AuthMiddleware::class]);
/**
 * Marca una cotización específica como la ganadora para una partida de sourcing.
 * Actualiza automáticamente el precio negociado en la requisición original.
 */
Route::post('api/v1/sourcing/quotations/{id}/select-winner', [SourcingController::class, 'selectWinner'])->middleware([AuthMiddleware::class]);
/**
 * Realiza el borrado lógico de una cotización de sourcing.
 * DELETE /api/v1/sourcing/quotations/{id}
 */
Route::delete('api/v1/sourcing/quotations/{id}', [SourcingController::class, 'deleteQuotation'])->middleware([AuthMiddleware::class]);
/**
 * Convierte una partida de sourcing en un artículo oficial del catálogo maestro.
 * Requiere que previamente se haya seleccionado una cotización ganadora.
 */
Route::post('api/v1/sourcing/promote-to-catalog', [SourcingController::class, 'promoteToCatalog'])->middleware([AuthMiddleware::class]);

/**
 * ==============================================================================
 * RUTAS DE ORDENES DE COMPRA
 * ==============================================================================
 */
// Crear Orden de Compra (a partir de una requisición)
Route::post('api/v1/purchase-orders', [PurchaseOrderController::class, 'store'])->middleware([AuthMiddleware::class]);
// Obtiene KPIs
Route::get('api/v1/purchase-orders/kpis', [PurchaseOrderController::class, 'getKpis'])->middleware([AuthMiddleware::class]);
// Obtener detalle de una OC específica
Route::get('api/v1/purchase-orders/{id}', [PurchaseOrderController::class, 'show'])->middleware([AuthMiddleware::class]);
// Listado de Órdenes de Compra con filtros
Route::get('api/v1/purchase-orders', [PurchaseOrderController::class, 'index'])->middleware([AuthMiddleware::class]);
// Rutas de cambio de estado (Máquina de Estados)
Route::post('api/v1/purchase-orders/{id}/transit', [PurchaseOrderController::class, 'transit'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware([AuthMiddleware::class]);
// Ruta de PDF
Route::get('api/v1/purchase-orders/{id}/pdf', [PurchaseOrderController::class, 'generatePdf'])->middleware([AuthMiddleware::class]);

/**
 * ==============================================================================
 * RUTAS DE RECEPCIÓN DE INVENTARIO
 * ==============================================================================
 */
// Pendiente de recepción
Route::get('api/v1/purchase-orders/{id}/pending-reception', [InventoryReceptionController::class, 'getPendingItems'])->middleware([AuthMiddleware::class]);
// Recepción de mercancía
Route::post('api/v1/inventory-receptions', [InventoryReceptionController::class, 'store'])->middleware([AuthMiddleware::class]);

/**
 * ==============================================================================
 * RUTAS DE CUENTAS POR PAGAR
 * ==============================================================================
 */
// Listado de facturas
Route::get('api/v1/accounts-payable/invoices', [AccountsPayableInvoiceController::class, 'index'])->middleware([AuthMiddleware::class]);
// KPIs del Dashboard
Route::get('api/v1/accounts-payable/invoices/kpis', [AccountsPayableInvoiceController::class, 'getKpis'])->middleware([AuthMiddleware::class]);
// Liberación Manual de Factura
Route::get('api/v1/accounts-payable/invoices/override', [AccountsPayableInvoiceController::class, 'getKpis'])->middleware([AuthMiddleware::class]);

/**
 * ==============================================================================
 * RUTAS DE CATÁLOGOS
 * ==============================================================================
 */
// --- WAREHOUSE ---
// Almacenes
Route::get('api/v1/warehouses', [WarehouseController::class, 'index'])->middleware([AuthMiddleware::class]);

// --- CURRENCY ---
// Monedas
Route::get('api/v1/currencies', [CurrencyController::class, 'index'])->middleware([AuthMiddleware::class]);

// --- PRODUCT LINES ---
Route::get('api/v1/catalogs/product-lines', [Catalogo::class, 'productLines'])->middleware([AuthMiddleware::class]);

// -- PAYMENT METHODS --
Route::get('api/v1/catalogs/payment-methods', [Catalogo::class, 'paymentMethods'])->middleware([AuthMiddleware::class]);

// -- 
Route::get('api/v1/catalogs/plants', [Catalogo::class, 'plants'])->middleware([AuthMiddleware::class]);

/**
 * ==============================================================================
 * RUTAS DEL API RESTFUL SRM V1 (SERVICIOS JSON)
 * ==============================================================================
 */
// 1. RESUMEN / DASHBOARD
Route::get('api/v1/dashboard/dashboard/summary', [SrmController::class, 'getSummary'])->middleware([AuthMiddleware::class]);

// 2. EXPEDIENTE DIGITAL
Route::get('api/v1/srm/dossier', [SrmController::class, 'getDossier'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/srm/dossier/upload', [SrmController::class, 'uploadDocument'])->middleware([AuthMiddleware::class]);

// 3. ÓRDENES DE COMPRA
Route::get('api/v1/srm/purchase-orders', [SrmPurchaseOrderController::class, 'index'])->middleware([AuthMiddleware::class]);
Route::get('api/v1/srm/purchase-orders/{id_oc}', [SrmPurchaseOrderController::class, 'show'])->middleware([AuthMiddleware::class]);
Route::get('api/v1/srm/purchase-orders/{id}/pdf', [SrmPurchaseOrderController::class, 'generatePdf'])->middleware([AuthMiddleware::class]);

// 4. BUZÓN DE FACTURAS (XML/PDF)
Route::get('api/v1/srm/invoices', [SrmInvoiceController::class, 'index'])->middleware([AuthMiddleware::class]); // Historial de facturas subidas
Route::post('api/v1/srm/invoices/upload', [SrmInvoiceController::class, 'uploadInvoice'])->middleware([AuthMiddleware::class]); // Validación y carga de XML + PDF
// 5. GESTIÓN BANCARIA AUTOGESTIONABLE
Route::get('api/v1/srm/bank-accounts', [SrmController::class, 'getBankAccounts'])->middleware([AuthMiddleware::class]);
Route::post('api/v1/srm/bank-accounts', [SrmController::class, 'storeBankAccount'])->middleware([AuthMiddleware::class]);
?>