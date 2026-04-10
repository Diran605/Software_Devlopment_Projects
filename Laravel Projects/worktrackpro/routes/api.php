<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DailyPlanController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\TeamController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('dashboard/stats/weekly', [DashboardController::class, 'weeklyStats']);
        
        Route::apiResource('plans', DailyPlanController::class);
        Route::apiResource('logs', ActivityLogController::class);

        // Team directory
        Route::get('team', [TeamController::class, 'index']);
        Route::get('team/departments', [TeamController::class, 'departments']);
        Route::patch('team/{user}/toggle-status', [TeamController::class, 'toggleStatus']);
    });
});
