<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Student;
use App\Observers\GradeObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Grade::observe(GradeObserver::class);

        // Las pantallas internas usan Bootstrap 4 (AdminLTE); Laravel genera Tailwind por defecto.
        Paginator::useBootstrapFour();

        // Cada estudiante tiene una matrícula en la gestión actual que sigue a su curso y paralelo.
        Student::saved(function (Student $student) {
            $yearId = AcademicYear::currentId();
            if (! $yearId || ! ($student->wasRecentlyCreated || $student->wasChanged(['course_id', 'parallel_id']))) {
                return;
            }
            Enrollment::updateOrCreate(
                ['student_id' => $student->id, 'academic_year_id' => $yearId],
                ['course_id' => $student->course_id, 'parallel_id' => $student->parallel_id]
            );
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Login de la API: por cuenta + IP, para no bloquear a toda una red escolar.
        RateLimiter::for('api-login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')) . '|' . $request->ip());
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
