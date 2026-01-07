<?php

use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Payment API Routes for mobile app & external integrations
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Payment API Routes
Route::prefix('payments')->name('api.payments.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    Route::get('/report', [PaymentController::class, 'report'])->name('report');
    Route::get('/overdue', [PaymentController::class, 'overduePayments'])->name('overdue');
});

// Tenant Payments API
Route::prefix('tenants')->name('api.tenants.')->group(function () {
    Route::get('/{tenant}/payments', [PaymentController::class, 'tenantUnpaidPayments'])->name('unpaid-payments');
});

// Room Summary API
Route::prefix('rooms')->name('api.rooms.')->group(function () {
    Route::get('/{room}/summary', [PaymentController::class, 'roomSummary'])->name('summary');
});

// Admin Notifications API
Route::middleware('auth:sanctum')->prefix('admin/notifications')->name('api.admin.notifications.')->group(function () {
    Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
    Route::get('/unread', [AdminNotificationController::class, 'unread'])->name('unread');
    Route::get('/unread-count', [AdminNotificationController::class, 'getUnreadCount'])->name('unread-count');
    Route::get('/{adminNotification}', [AdminNotificationController::class, 'show'])->name('show');
    Route::patch('/{adminNotification}/read', [AdminNotificationController::class, 'markAsRead'])->name('mark-as-read');
    Route::patch('/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::delete('/{adminNotification}', [AdminNotificationController::class, 'delete'])->name('delete');
});

