<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\EarningController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\SupportController; // جديد
use App\Http\Controllers\Api\AdminController;   // جديد

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

    // =============================
    // CUSTOMER ROUTES
    // =============================
    Route::middleware('role:customer')->prefix('customer')->group(function () {
        Route::post('/orders', [OrderController::class, 'createAsCustomer']);
        Route::post('/orders/scheduled', [OrderController::class, 'createScheduled']); // جديد: إنشاء طلب مجدول

        Route::get('/orders', [OrderController::class, 'listForCustomer']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);

        // تقييم
        Route::post('/orders/{id}/rate', [RatingController::class, 'store']); // جديد
    });

    // =============================
    // DRIVER ROUTES
    // =============================
    Route::middleware('role:driver')->prefix('driver')->group(function () {
        Route::get('/orders', [OrderController::class, 'availableOrders']);
        Route::post('/orders/{id}/accept', [OrderController::class, 'acceptOrder']);
        Route::post('/update-status', [DriverController::class, 'updateStatus']);
        Route::get('/parcels', [DriverController::class, 'availableParcels']);
        Route::get('/requests', [DriverController::class, 'nearbyRequests']);
        Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus']);

        // جديد: تحديث الموقع
        Route::post('/location/update', [DriverController::class, 'updateLocation']);
        Route::get('/ratings', [RatingController::class, 'driverRatings']); // جديد: عرض التقييمات
        Route::get('/earnings/history', [EarningController::class, 'history']); // جديد: أرباح مفصلة
    });

    // =============================
    // STATION ROUTES
    // =============================
    Route::middleware('role:station')->prefix('station')->group(function () {
        Route::get('/parcels', [StationController::class, 'index']);
        Route::post('/confirm', [StationController::class, 'confirmReceive']);
        Route::post('/assign-driver', [StationController::class, 'assignDriver']);
    });

    // =============================
    // PAYMENTS ROUTES
    // =============================
    Route::prefix('payments')->group(function () {
        Route::post('/record', [PaymentController::class, 'record']);
        Route::post('/refund', [PaymentController::class, 'refund']); // جديد: استرجاع دفعة
        Route::get('/history', [PaymentController::class, 'history']); // جديد: سجل المدفوعات
    });

    // =============================
    // EARNINGS ROUTES
    // =============================
    Route::prefix('earnings')->group(function () {
        Route::get('/', [EarningController::class, 'index']);
        Route::get('/summary', [EarningController::class, 'summary']);
    });

    // =============================
    // SUPPORT ROUTES (جديد)
    // =============================
    Route::prefix('support')->group(function () {
        Route::post('/create', [SupportController::class, 'create']);   // فتح تذكرة
        Route::get('/my-tickets', [SupportController::class, 'myTickets']); // عرض تذاكري
        Route::get('/ticket/{id}', [SupportController::class, 'show']); // تفاصيل تذكرة
    });

    // =============================
    // ADMIN ROUTES (جديد)
    // =============================
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'listUsers']);
        Route::post('/users/{id}/ban', [AdminController::class, 'banUser']);
        Route::get('/orders', [AdminController::class, 'listOrders']);
        Route::get('/payments', [AdminController::class, 'listPayments']);
        Route::get('/reports', [AdminController::class, 'reports']);
    });

    // =============================
    // NOTIFICATIONS (جديد)
    // =============================
    Route::prefix('notifications')->group(function () {
        Route::get('/', [AuthController::class, 'notifications']); // عرض الإشعارات
        Route::post('/mark-read', [AuthController::class, 'markNotificationsRead']); // تعليم كمقروء
    });
});
