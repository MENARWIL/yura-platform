<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RobotActivityController;
use App\Http\Controllers\TutorController;

Route::post('/login', [AuthController::class, 'loginApi']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/tutor/my-children', [TutorController::class, 'myChildren']);
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
});
