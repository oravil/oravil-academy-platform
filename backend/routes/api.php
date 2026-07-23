<?php

use App\Http\Controllers\Assignment\AssignmentSubmissionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\LearningPath\LessonController;
use App\Http\Controllers\LearningPath\ModuleOverviewController;
use App\Http\Controllers\Progress\LearnerProgressController;
use App\Http\Controllers\Progress\ModuleCompletionController;
use App\Http\Controllers\Survey\SurveyController;
use App\Http\Controllers\Survey\SurveyResponseController;
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
    Route::get('/lessons/{lesson_id}', LessonController::class);
    Route::post('/assignments/{assignment_id}/submissions', AssignmentSubmissionController::class);
    Route::get('/modules/{module_id}/completion', ModuleCompletionController::class);
    Route::get('/modules/{module_id}/survey', SurveyController::class);
    Route::post('/surveys/{survey_id}/responses', SurveyResponseController::class);
});
