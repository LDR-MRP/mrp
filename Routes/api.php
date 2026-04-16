<?php
// Routes/api.php

use Libraries\Core\Route;
use Controllers\Api\V1\SupplierController;
use Controllers\Api\V1\RequisitionController;
use Controllers\Api\V1\PurchaseOrderController;
use Middlewares\AuthMiddleware;

// Rutas Públicas
Route::get('api/v1/suppliers', [SupplierController::class, 'index']);
Route::get('api/v1/suppliers/{id}', [SupplierController::class, 'show']);

// Rutas Protegidas
// --- SUPPLIERS ---
Route::post('api/v1/supplier', [SupplierController::class, 'store'])
    //->middleware([AuthMiddleware::class])
    ;

// --- REQUISITIONS ---
Route::get('api/v1/requisitions', [RequisitionController::class, 'index']);
Route::get('api/v1/requisitions/kpis', [RequisitionController::class, 'kpis']);
Route::get('api/v1/requisitions/{id}', [RequisitionController::class, 'show']);
Route::post('api/v1/requisitions', [RequisitionController::class, 'store']);
Route::put('api/v1/requisitions/{id}', [RequisitionController::class, 'update']);
Route::post('api/v1/requisitions/{id}/items', [RequisitionController::class, 'createItem']);
Route::delete('api/v1/requisitions/{id}/items/{item_id}', [RequisitionController::class, 'deleteItem']);
Route::post('api/v1/requisitions/{id}/items/move', [RequisitionController::class, 'moveItems']);
Route::post('api/v1/requisitions/{id}/submit', [RequisitionController::class, 'submit']);
// Rutas de cambio de estado (Máquina de Estados)
Route::post('api/v1/requisitions/{id}/approve', [RequisitionController::class, 'approve']);
Route::post('api/v1/requisitions/{id}/reject', [RequisitionController::class, 'reject']);
Route::post('api/v1/requisitions/{id}/return-to-draft', [RequisitionController::class, 'returnToDraft']);
// Ruta de eliminación
Route::delete('api/v1/requisitions/{id}', [RequisitionController::class, 'destroy']);
// Ruta de PDF
Route::get('api/v1/requisitions/{id}/pdf', [RequisitionController::class, 'generatePdf']);
// Obtener las partidas pendientes de compra de una requisición
Route::get('api/v1/requisitions/{id}/pending-items', [RequisitionController::class, 'getPendingItems']);
// Crear Orden de Compra (a partir de una requisición)
Route::post('api/v1/purchase-orders', [PurchaseOrderController::class, 'store']);

?>