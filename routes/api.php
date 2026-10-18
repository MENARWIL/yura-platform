<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RobotActivityController;
use App\Http\Controllers\Api\RobotActivityQueueController;
use App\Http\Controllers\TutorController;

Route::post('/login', [AuthController::class, 'loginApi'])->middleware('throttle:api-login');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'active.user']);

Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/tutor/my-children', [TutorController::class, 'myChildren']);

    // API de la app móvil (contrato: docs/api-app-v1.md)
    Route::prefix('v1/app')->group(function () {
        Route::get('/me', [AppController::class, 'me']);
        Route::get('/children', [AppController::class, 'children']);
        Route::get('/children/{student}/summary', [AppController::class, 'summary'])->whereNumber('student');
    });
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/students/{id}', [StudentController::class, 'show']);
    Route::post('/students/{id}/update-score', [StudentController::class, 'updatePuntaje']);

    // User Management (Admin)
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus']);
    Route::post('/v1/robot/activity', [RobotActivityController::class, 'store']);
    Route::get('/v1/robot/activities/next', [RobotActivityQueueController::class, 'next']);
    Route::get('/v1/robot/activities/{activityId}/attempts/{attemptId}/status', [RobotActivityQueueController::class, 'status'])
        ->whereUuid('activityId')
        ->whereUuid('attemptId');
    Route::post('/v1/robot/activities/{activityId}/results', [RobotActivityQueueController::class, 'results'])
        ->whereUuid('activityId');
});
