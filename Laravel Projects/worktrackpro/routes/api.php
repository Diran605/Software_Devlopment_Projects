<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DailyPlanController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\LookupController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::patch('password', [AuthController::class, 'updatePassword']);
        });
    });

    Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckOrganisationActive::class])->group(function () {
        Route::get('dashboard/stats/weekly', [DashboardController::class, 'weeklyStats']);
        
        Route::apiResource('plans', DailyPlanController::class);
        Route::patch('plans/{plan}/complete', [DailyPlanController::class, 'complete']);
        
        Route::apiResource('logs', ActivityLogController::class);

        // Lookup endpoints for frontend dropdowns
        Route::get('work-types', [LookupController::class, 'workTypes']);
        Route::get('project-clients', [LookupController::class, 'projectClients']);

        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
            Route::patch('/{id}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead']);
        });

        // Team directory
        Route::get('team', [TeamController::class, 'index']);
        Route::get('team/departments', [TeamController::class, 'departments']);
        Route::patch('team/{user}/toggle-status', [TeamController::class, 'toggleStatus']);
    });
});
