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
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeachingAssignmentController;


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
    Route::resource('students', StudentController::class)
        ->middleware('rol:admin,academic,profesor,estudiante,padre,madre');

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

    Route::middleware(['rol:admin,academic,profesor'])->group(function () {
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
    });

    Route::middleware(['rol:profesor'])->group(function () {
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

    Route::get('/grades/{student}/history', [GradeController::class, 'historyForStudent'])
        ->middleware(['web', 'auth', 'rol:admin,academic,profesor'])
        ->name('grades.history');

    Route::middleware(['rol:admin,academic'])->group(function () {
        Route::get('/tutors/create', [TutorController::class, 'create'])->name('tutors.create');
        Route::post('/tutors', [TutorController::class, 'store'])->name('tutors.store');
        Route::get('/tutors/{tutor}/edit', [TutorController::class, 'edit'])->name('tutors.edit');
        Route::put('/tutors/{tutor}', [TutorController::class, 'update'])->name('tutors.update');
        Route::patch('/tutors/{tutor}/status', [TutorController::class, 'toggleStatus'])->name('tutors.status');
    });

    Route::middleware(['web', 'auth', 'rol:admin,academic'])->group(function () {
        Route::resource('courses', CourseController::class)->except(['show']);
        Route::resource('subjects', SubjectController::class)->except(['show', 'destroy']);
        Route::resource('teaching-assignments', TeachingAssignmentController::class)->only(['index', 'create', 'store', 'update', 'destroy']);

        Route::get('/teachers', [UserController::class, 'teacherIndex'])->name('teachers.index');
        Route::get('/teachers/create', [UserController::class, 'createTeacher'])->name('teachers.create');
        Route::post('/teachers', [UserController::class, 'storeTeacher'])->name('teachers.store');
        Route::get('/teachers/{user}/edit', [UserController::class, 'edit'])->name('teachers.edit');
        Route::put('/teachers/{user}', [UserController::class, 'update'])->name('teachers.update');

        Route::get('/parallels', [ParallelController::class, 'index'])->name('parallels.index');
        Route::get('/parallels/create', [ParallelController::class, 'create'])->name('parallels.create');
        Route::post('/parallels', [ParallelController::class, 'store'])->name('parallels.store');
        Route::get('/parallels/{parallel}/edit', [ParallelController::class, 'edit'])->name('parallels.edit');
        Route::put('/parallels/{parallel}', [ParallelController::class, 'update'])->name('parallels.update');
        Route::delete('/parallels/{parallel}', [ParallelController::class, 'destroy'])->name('parallels.destroy');
    });

    Route::get('/tutor/dashboard', function () {
        return redirect()->route('dashboard');
    })->middleware(['rol:tutor'])->name('tutor.dashboard');

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
    ->name('grades.grade-history');
