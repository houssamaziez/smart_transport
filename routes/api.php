<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\Api\PaymentController;

// =============================
// AUTHENTICATION ROUTES
// =============================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });
});
// =============================
// PROTECTED ROUTES
// =============================
Route::middleware('auth:sanctum')->group(function () {

    // CUSTOMER ROUTES
    Route::middleware('role:customer')->prefix('customer')->group(function () {
        Route::post('/orders', [OrderController::class, 'createAsCustomer']);
        Route::get('/orders', [OrderController::class, 'listForCustomer']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    });

    // DRIVER ROUTES
    Route::middleware('role:driver')->prefix('driver')->group(function () {
        Route::get('/requests', [DriverController::class, 'nearbyRequests']);
        Route::post('/accept', [DriverController::class, 'acceptRequest']);
        Route::post('/update-status', [DriverController::class, 'updateStatus']);
        Route::get('/parcels', [DriverController::class, 'availableParcels']);
        Route::get('/orders', [DriverController::class, 'availableOrders']); // ✅ الطلبات المتاحة
        Route::post('/orders/{id}/accept', [DriverController::class, 'acceptOrder']); // ✅ قبول الطلب
    });

    Route::middleware('role:station')->prefix('station')->group(function () {
        Route::get('/parcels', [StationController::class, 'index']);
        Route::post('/confirm', [StationController::class, 'confirmReceive']);
        Route::post('/assign-driver', [StationController::class, 'assignDriver']);
    });

    // PAYMENTS ROUTES
    Route::prefix('payments')->group(function () {
        Route::post('/record', [PaymentController::class, 'record']);
    });
});
