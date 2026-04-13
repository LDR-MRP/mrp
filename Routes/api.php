<?php
// Routes/api.php

use Libraries\Core\Route;
use Controllers\Api\V1\SupplierController;
use Controllers\Api\V1\RequisitionController;
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
Route::get('api/v1/requisitions', [RequisitionController::class, 'index'])
    //->middleware([AuthMiddleware::class])
    ;

Route::get('api/v1/requisitions/{id}', [RequisitionController::class, 'show'])
    //->middleware([AuthMiddleware::class])
    ;

Route::post('api/v1/requisitions/{id}/items/move', [RequisitionController::class, 'moveItems'])
    //->middleware([AuthMiddleware::class])
    ;
?>