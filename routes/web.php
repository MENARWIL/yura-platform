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
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RobotActivityController;
use App\Http\Controllers\TutorDashboardController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\PublicPortalController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AuditController;

// Public Portal Routes
Route::get('/', [PublicPortalController::class, 'home'])->name('public.home');
Route::get('/home', [PublicPortalController::class, 'home'])->name('home');
Route::get('/services', [PublicPortalController::class, 'services'])->name('public.services');
Route::get('/documents', [PublicPortalController::class, 'documents'])->name('public.documents');
Route::get('/search', [PublicPortalController::class, 'search'])->middleware('throttle:30,1')->name('public.search');

// Página del proyecto YURA (ruta histórica /feria)
Route::get('/feria', function () {
    return view('home');
})->name('feria');

// Confianza y buscadores
Route::get('/privacidad', [\App\Http\Controllers\LegalController::class, 'privacy'])->name('public.privacy');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('sitemap');

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
    Route::get('/students/export-pdf-all', [StudentController::class, 'exportPdfAll'])->middleware('rol:admin,profesor')->name('students.export.pdf.all');
    Route::get('/students/export-excel-all', [StudentController::class, 'exportExcelAll'])->middleware('rol:admin,profesor')->name('students.export.excel.all');
    // Individual student exports
    Route::get('/students/{student}/export-pdf', [StudentController::class, 'exportPdf'])->name('students.export.pdf');
    Route::get('/students/{student}/export-excel', [StudentController::class, 'exportExcel'])->name('students.export.excel');
    Route::patch('/students/{student}/info', [StudentController::class, 'updateInfo'])->middleware('rol:admin,academic')->name('students.info');
    Route::post('/students/{student}/guardian', [StudentController::class, 'assignGuardian'])->middleware('rol:admin,academic')->name('students.guardian');
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
        Route::get('/grades/export-pdf', [GradeController::class, 'exportPdf'])->name('grades.export.pdf');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/export-pdf', [AttendanceController::class, 'exportPdf'])->name('attendance.export.pdf');
    });

    Route::middleware(['rol:profesor'])->group(function () {
        Route::get('/robot-activities', [RobotActivityController::class, 'index'])->name('robot-activities.index');
        Route::get('/robot-activities/create', [RobotActivityController::class, 'create'])->name('robot-activities.create');
        Route::get('/robot-activities/{activityId}', [RobotActivityController::class, 'show'])
            ->whereUuid('activityId')
            ->name('robot-activities.show');
        Route::post('/robot-activities/{activityId}/cancel', [RobotActivityController::class, 'cancel'])
            ->whereUuid('activityId')
            ->name('robot-activities.cancel');
        Route::post('/robot-activities/{activityId}/retry', [RobotActivityController::class, 'retry'])
            ->whereUuid('activityId')
            ->name('robot-activities.retry');
        Route::post('/robot-activities', [RobotActivityController::class, 'store'])->name('robot-activities.store');
        Route::post('/grades/save', [GradeController::class, 'save'])->name('grades.save');
        Route::post('/grades/update-score', [GradeController::class, 'updateScore'])->name('grades.updateScore');
        Route::post('/attendance/save', [AttendanceController::class, 'save'])->name('attendance.save');
        Route::post('/attendance/save-list', [AttendanceController::class, 'saveList'])->name('attendance.saveList');
        Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
        Route::post('/grades/batch', [GradeController::class, 'batchUpdate'])->name('grades.batchUpdate');
    });

    Route::get('/grades/{student}', [GradeController::class, 'show'])
        ->middleware(['web', 'auth', 'rol:admin,academic,profesor,padre,madre,tutor'])
        ->name('grades.show');

    Route::get('/grades/{student}/history', [GradeController::class, 'historyForStudent'])
        ->middleware(['web', 'auth', 'rol:admin,academic,profesor,padre,madre,tutor'])
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
    })->middleware(['rol:padre,madre,tutor'])->name('tutor.dashboard');

    // Admin Specific Routes
    Route::middleware(['rol:admin'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
        Route::patch('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::delete('/users/{user}/force', [UserController::class, 'forceDestroy'])->name('users.forceDestroy');

        Route::get('/admin/audit', [AuditController::class, 'index'])->name('admin.audit.index');

        Route::get('/admin/years', [AcademicYearController::class, 'index'])->name('admin.years.index');
        Route::post('/admin/years', [AcademicYearController::class, 'store'])->name('admin.years.store');
        Route::get('/admin/years/{year}', [AcademicYearController::class, 'show'])->whereNumber('year')->name('admin.years.show');
        Route::get('/admin/years/{year}/export', [AcademicYearController::class, 'export'])->whereNumber('year')->name('admin.years.export');
        Route::post('/admin/years/{year}/current', [AcademicYearController::class, 'current'])->name('admin.years.current');
        Route::post('/admin/years/{year}/close', [AcademicYearController::class, 'close'])->name('admin.years.close');
        Route::post('/admin/years/{year}/reopen', [AcademicYearController::class, 'reopen'])->name('admin.years.reopen');
    });
});
