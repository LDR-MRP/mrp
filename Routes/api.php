<?php
// Routes/api.php

use Libraries\Core\Route;
use Controllers\Api\V1\AuthController;
use Controllers\Api\V1\SupplierController;
use Controllers\Api\V1\RequisitionController;
use Controllers\Api\V1\PurchaseOrderController;
use Middlewares\AuthMiddleware;

// Rutas Públicas
// Endpoint público para obtener el token
Route::post('api/v1/login', [AuthController::class, 'login']);
Route::get('api/v1/suppliers', [SupplierController::class, 'index']);
Route::get('api/v1/suppliers/{id}', [SupplierController::class, 'show']);

// Rutas Protegidas
// --- SUPPLIERS ---
Route::post('api/v1/supplier', [SupplierController::class, 'store'])
    //->middleware([AuthMiddleware::class])
    ;

// --- REQUISITIONS ---
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
Route::post('api/v1/requisitions/{id}/return-to-draft', [RequisitionController::class, 'returnToDraft'])->middleware([AuthMiddleware::class]);
// Ruta de eliminación
Route::delete('api/v1/requisitions/{id}', [RequisitionController::class, 'destroy'])->middleware([AuthMiddleware::class]);
// Ruta de PDF
Route::get('api/v1/requisitions/{id}/pdf', [RequisitionController::class, 'generatePdf'])->middleware([AuthMiddleware::class]);
// Obtener las partidas pendientes de compra de una requisición
Route::get('api/v1/requisitions/{id}/pending-items', [RequisitionController::class, 'getPendingItems'])->middleware([AuthMiddleware::class]);
// Crear Orden de Compra (a partir de una requisición)
Route::post('api/v1/purchase-orders', [PurchaseOrderController::class, 'store'])->middleware([AuthMiddleware::class]);
// Obtener detalle de una OC específica
Route::get('api/v1/purchase-orders/{id}', [PurchaseOrderController::class, 'show'])->middleware([AuthMiddleware::class]);
// Listado de Órdenes de Compra con filtros
Route::get('api/v1/purchase-orders', [PurchaseOrderController::class, 'index'])->middleware([AuthMiddleware::class]);
?>