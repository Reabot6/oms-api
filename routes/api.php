<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Version 1
Route::prefix('v1')->group(function () {
    // Public routes — throttle: 10 register/min, 5 login/min per IP
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:10,1');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');
    });

    // Protected routes
    Route::middleware(['auth:api'])->group(function () {
        // Auth routes
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'me']);
        });

        // Product routes
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::get('/{id}', [ProductController::class, 'show']);

            Route::middleware(['role:admin,vendor'])->group(function () {
                Route::post('/', [ProductController::class, 'store']);
                Route::put('/{id}', [ProductController::class, 'update']);
                Route::delete('/{id}', [ProductController::class, 'destroy']);
                Route::post('/import', [ProductController::class, 'import']);
            });
        });

        // Order routes
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('/{id}', [OrderController::class, 'show']);
            Route::post('/', [OrderController::class, 'store'])->middleware('role:customer');
            Route::post('/{id}/cancel', [OrderController::class, 'cancel']);

            Route::middleware(['role:admin,vendor'])->group(function () {
                Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
            });
        });

        // Inventory routes
        Route::middleware(['role:admin,vendor'])->prefix('inventory')->group(function () {
            Route::get('/{id}', [InventoryController::class, 'show']);
            Route::put('/{id}', [InventoryController::class, 'update']);
            Route::get('/low-stock/list', [InventoryController::class, 'lowStock']);
        });
    });
});
