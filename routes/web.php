<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FamilyMemberController;


Route::get('/', function () {
    return view('home');
})->name('home');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Students CRUD & Exports
    Route::get('students/export/excel', [StudentController::class, 'exportExcel'])->name('students.export.excel');
    Route::get('students/export/pdf', [StudentController::class, 'exportPdf'])->name('students.export.pdf');
    Route::patch('students/{student}/restore', [StudentController::class, 'restore'])->name('students.restore');
    Route::delete('students/{student}/force', [StudentController::class, 'forceDestroy'])->name('students.forceDestroy');
    Route::resource('students', StudentController::class);

    Route::middleware(['rol:admin,profesor'])->group(function () {
        Route::get('/family-members', [FamilyMemberController::class, 'index'])->name('family-members.index');
        Route::post('/family-members', [FamilyMemberController::class, 'store'])->name('family-members.store');
        Route::put('/family-members/{familyMember}', [FamilyMemberController::class, 'update'])->name('family-members.update');
        Route::delete('/family-members/{familyMember}', [FamilyMemberController::class, 'destroy'])->name('family-members.destroy');
    });

    // Admin Specific Routes
    Route::middleware(['rol:admin'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
        Route::patch('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::delete('/users/{user}/force', [UserController::class, 'forceDestroy'])->name('users.forceDestroy');
    });
});
