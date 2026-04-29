<?php

use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\ObligationController;
use App\Http\Controllers\Api\V1\ReceiptController;
use App\Http\Controllers\Api\V1\ReminderController;
use App\Http\Controllers\Api\V1\RemittanceController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('debug-session', function () {
        $cookie = request()->cookie('smartcash_uid');
        return response()->json([
            'cookie_value' => $cookie,
            'cookie_type' => gettype($cookie),
            'is_numeric' => is_numeric($cookie),
            'session_user' => session('user'),
            'session_user_id' => session('user_id'),
            'header_user_id' => request()->header('X-User-Id'),
        ]);
    });

    Route::middleware(['web', 'throttle:60,1'])->group(function () {
        Route::apiResource('obligations', ObligationController::class);
        Route::apiResource('receipts', ReceiptController::class);
        Route::apiResource('reminders', ReminderController::class);
        Route::apiResource('remittances', RemittanceController::class);
    });

    Route::apiResource('users', AdminUserController::class)->except(['index', 'store']);
    Route::get('users', [AdminUserController::class, 'index']);
    Route::post('users', [AdminUserController::class, 'store']);
    Route::get('users/{id}', [AdminUserController::class, 'show']);
    Route::post('users/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
    Route::put('profile/{id}', [UserController::class, 'update']);
    Route::post('profile/password', [UserController::class, 'updatePassword']);

    Route::get('reports/monthly', [ReportController::class, 'monthly']);
    Route::get('reports/statement', [ReportController::class, 'statement']);
    Route::get('reports/outstanding', [ReportController::class, 'outstanding']);
    Route::get('reports/overdue', [ReportController::class, 'overdue']);
    Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('reports/export/excel', [ReportController::class, 'exportExcel']);
    Route::get('reports/export/pdf', [ReportController::class, 'exportPdf']);
    Route::post('import', [ReportController::class, 'import']);
    Route::get('audits', [AuditController::class, 'index']);
});
