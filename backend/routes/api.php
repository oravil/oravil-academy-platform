<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\LearningPath\ModuleOverviewController;
use App\Http\Controllers\Progress\LearnerProgressController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', LoginController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', MeController::class);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/modules/{module_id}/overview', ModuleOverviewController::class);
    Route::get('/learners/me/progress/{module_id}', LearnerProgressController::class);
});
