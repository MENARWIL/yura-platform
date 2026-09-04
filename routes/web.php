<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\ParallelController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\RobotActivityController;
use App\Http\Controllers\TutorDashboardController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\CourseController;


Route::get('/', function () {
    return view('home');
})->name('home');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/lang/{locale}', [LocaleController::class, 'set'])->name('lang.set');

// Protected Routes
Route::middleware(['auth', 'set.locale'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Students CRUD & Exports (global)
    Route::get('/students/export-pdf-all', [StudentController::class, 'exportPdfAll'])->name('students.export.pdf.all');
    Route::get('/students/export-excel-all', [StudentController::class, 'exportExcelAll'])->name('students.export.excel.all');
    // Individual student exports
    Route::get('/students/{student}/export-pdf', [StudentController::class, 'exportPdf'])->name('students.export.pdf');
    Route::get('/students/{student}/export-excel', [StudentController::class, 'exportExcel'])->name('students.export.excel');
    Route::patch('students/{student}/restore', [StudentController::class, 'restore'])->name('students.restore');
    Route::delete('students/{student}/force', [StudentController::class, 'forceDestroy'])->name('students.forceDestroy');
    Route::resource('students', StudentController::class);

    // Family Members - Read Access (admin, academic, profesor, tutor)
    Route::middleware(['rol:admin,academic,profesor,padre,madre,tutor'])->group(function () {
        Route::get('/family-members', [FamilyMemberController::class, 'index'])->name('family-members.index');
    });

    // Family Members - Write Access (admin, academic only)
    Route::middleware(['rol:admin,academic'])->group(function () {
        Route::post('/family-members', [FamilyMemberController::class, 'store'])->name('family-members.store');
        Route::put('/family-members/{familyMember}', [FamilyMemberController::class, 'update'])->name('family-members.update');
        Route::delete('/family-members/{familyMember}', [FamilyMemberController::class, 'destroy'])->name('family-members.destroy');
    });

    Route::middleware(['rol:admin,profesor'])->group(function () {
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::post('/grades/save', [GradeController::class, 'save'])->name('grades.save');
        Route::post('/grades/update-score', [GradeController::class, 'updateScore'])->name('grades.updateScore');
        Route::post('/attendance/save', [App\Http\Controllers\GradeController::class, 'saveAttendance'])->name('attendance.save');
        Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
        Route::post('/grades/batch', [GradeController::class, 'batchUpdate'])->name('grades.batchUpdate');
        Route::get('/robot-activities/create', [GradeController::class, 'createRobotActivity'])->name('robot-activities.create');
        Route::post('/robot-activities', [GradeController::class, 'storeRobotActivity'])->name('robot-activities.store');
    });

    Route::get('/grades/{student}', [GradeController::class, 'show'])
        ->middleware(['web', 'auth', 'rol:admin,academic,profesor'])
        ->name('grades.show');

    Route::middleware(['rol:admin,academic'])->group(function () {
        Route::get('/tutors/create', [TutorController::class, 'create'])->name('tutors.create');
        Route::post('/tutors', [TutorController::class, 'store'])->name('tutors.store');
    });

    Route::middleware(['web', 'auth', 'rol:admin,academic'])->group(function () {
        Route::resource('courses', CourseController::class)->except(['show']);

        Route::get('/teachers/create', [UserController::class, 'create'])->name('teachers.create');
        Route::post('/teachers', [UserController::class, 'store'])->name('teachers.store');
        Route::get('/teachers/{user}/edit', [UserController::class, 'edit'])->name('teachers.edit');
        Route::put('/teachers/{user}', [UserController::class, 'update'])->name('teachers.update');

        Route::get('/parallels', [ParallelController::class, 'index'])->name('parallels.index');
        Route::get('/parallels/create', [ParallelController::class, 'create'])->name('parallels.create');
        Route::post('/parallels', [ParallelController::class, 'store'])->name('parallels.store');
        Route::get('/parallels/{parallel}/edit', [ParallelController::class, 'edit'])->name('parallels.edit');
        Route::put('/parallels/{parallel}', [ParallelController::class, 'update'])->name('parallels.update');
    });

    Route::middleware(['rol:tutor'])->group(function () {
        Route::get('/tutor/dashboard', [TutorDashboardController::class, 'index'])->name('tutor.dashboard');
    });

    // Admin Specific Routes
    Route::middleware(['rol:admin'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
        Route::patch('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::delete('/users/{user}/force', [UserController::class, 'forceDestroy'])->name('users.forceDestroy');
    });
});

Route::get('/grades/{grade}/history', [GradeController::class, 'history'])
    ->middleware(['web', 'auth'])
    ->name('grades.history');
