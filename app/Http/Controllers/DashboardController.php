<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // capture selected course filter
        $selectedCourseId = request()->input('course_id');
        $courses = Course::all();
        $data = [];

        $totalStudents = Student::count();
        $totalCourses = Course::count();

        if ($user->isAdmin()) {
            $data['total_estudiantes'] = Student::when($selectedCourseId, function ($q) use ($selectedCourseId) { return $q->where('course_id', $selectedCourseId); })->count();
            $data['total_profesores'] = User::ofRole('profesor')->count();
            $data['total_padres'] = User::ofRole(['padre', 'madre', 'tutor'])->count();
            $data['recientes'] = Student::when($selectedCourseId, function ($q) use ($selectedCourseId) { return $q->where('course_id', $selectedCourseId); })->with('padre')->latest()->take(5)->get();
        } elseif ($user->isAcademic()) {
            $data['total_estudiantes'] = Student::when($selectedCourseId, function ($q) use ($selectedCourseId) { return $q->where('course_id', $selectedCourseId); })->count();
            $data['total_profesores'] = User::ofRole('profesor')->count();
            $data['total_padres'] = User::ofRole(['padre', 'madre', 'tutor'])->count();
            $data['recientes'] = Student::when($selectedCourseId, function ($q) use ($selectedCourseId) { return $q->where('course_id', $selectedCourseId); })->with('padre')->latest()->take(5)->get();
        } elseif ($user->isResponsibleRole()) {
            $children = Student::visibleToFamily($user)
                ->with(['course', 'parallel', 'grades.subject', 'attendances'])
                ->get();

            $data['mis_hijos'] = $children->map(function ($student) {
                $student->dashboard_report = \App\Services\StudentReport::for($student);

                return $student;
            });

            $allGrades = $data['mis_hijos']->flatMap(fn ($student) => $student->grades)
                ->where('status', 'completed')
                ->where('is_robot_activity', false)
                ->filter(fn ($grade) => $grade->score !== null);
            $allAttendance = $data['mis_hijos']->flatMap(fn ($student) => $student->attendances);
            $data['hijos_count'] = $data['mis_hijos']->count();
            $data['tutor_report'] = [
                'average' => $allGrades->count() ? round($allGrades->avg('score'), 2) : null,
                'attendance_percent' => Attendance::presentPercent($allAttendance),
                'robot_total' => $data['mis_hijos']->sum(fn ($student) => $student->dashboard_report['robot_total']),
            ];
        } elseif ($user->isProfesor()) {
            $levelColumn = 'level';
            $studentsQuery = Student::taughtBy($user)
                ->when($selectedCourseId, function ($q) use ($selectedCourseId) {
                    return $q->where('course_id', $selectedCourseId);
                })
                ->with(['course', 'parallel', 'grades', 'attendances']);

            $students = $studentsQuery->get();
            $today = now()->toDateString();

            $studentAverages = $students->map(function ($student) {
                $completedGrades = $student->grades->where('status', 'completed')->filter(fn ($grade) => is_numeric($grade->score));
                $student->promedio_academico = $completedGrades->count() ? round($completedGrades->avg('score'), 2) : 0;

                return $student;
            });

            // 'date' es Carbon: se compara como Y-m-d (where('date', $today) nunca coincidía y daba 0%).
            $todayAttendance = $students->flatMap(fn ($student) => $student->attendances->filter(
                fn ($attendance) => $attendance->date?->toDateString() === $today
            ));
            $attendancePercent = Attendance::presentPercent($todayAttendance) ?? 0;

            $data['mis_estudiantes_count'] = $students->count();
            // Tabla «Estudiantes asignados»: de 5 en 5, en orden de lista (las métricas usan a todos).
            $listed = $students->sortBy(fn ($s) => [$s->last_name ? 0 : 1, mb_strtolower((string) $s->last_name), mb_strtolower((string) $s->second_last_name), mb_strtolower((string) $s->first_name), mb_strtolower((string) $s->name)])->values();
            $page = LengthAwarePaginator::resolveCurrentPage('estudiantes');
            $data['mis_estudiantes'] = (new LengthAwarePaginator(
                $listed->forPage($page, 5)->values(),
                $listed->count(),
                5,
                $page,
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'estudiantes']
            ))->withQueryString();
            // Solo cuentan los estudiantes con notas: sin calificaciones no hay "bajo rendimiento"
            // ni deben bajar el promedio general con un 0 que no es real.
            $graded = $studentAverages->filter(fn ($student) => $student->grades->where('status', 'completed')->contains(fn ($grade) => is_numeric($grade->score)));
            $data['promedio_general'] = $graded->count() ? round($graded->avg('promedio_academico'), 2) : 0;
            $data['asistencia_hoy'] = $attendancePercent;
            $data['bajo_rendimiento'] = $graded->filter(fn ($student) => $student->promedio_academico < 60)->count();
            $data['ranking'] = $graded->sortByDesc('promedio_academico')->take(5)->values();
            $data['notas_recientes'] = \App\Models\Grade::whereIn('student_id', $students->pluck('id'))
                ->with(['student', 'subject'])
                ->orderByDesc('created_at')
                ->take(6)
                ->get();
            $data['distribucion'] = [
                'basico' => $students->where($levelColumn, 'básico')->count(),
                'intermedio' => $students->where($levelColumn, 'intermedio')->count(),
                'avanzado' => $students->where($levelColumn, 'avanzado')->count(),
            ];
        } elseif ($user->isEstudiante()) {
            // Computed from real grades/attendance (Student::real*()) instead of the
            // legacy static columns, which are not kept in sync.
            $data['mi_estudiante'] = $user->studentProfile()->with(['profesor', 'padre'])->first();
            $data['mi_promedio'] = $data['mi_estudiante']?->realOverallAverage() ?? 0;
            $data['mi_asistencia'] = $data['mi_estudiante']?->realAttendancePercent() ?? 0;
            $data['mi_profesor'] = $data['mi_estudiante']?->profesor?->name;
            $data['mi_nivel'] = $data['mi_estudiante']?->level;
        } else {
            $parentForeignKey = (new Student())->getParentUserForeignKey();

            $data['mis_hijos'] = Student::where($parentForeignKey, $user->id)
                ->orWhereHas('familiares', function ($family) use ($user) {
                    $family->where('user_id', $user->id);
                })
                ->with('profesor')
                ->get()
                ->map(function ($student) {
                    // Computed from real grades instead of the legacy static column.
                    $student->promedio = $student->realOverallAverage() ?? 0;

                    return $student;
                });
            $data['hijos_count'] = $data['mis_hijos']->count();
        }

        $data['total_students'] = $selectedCourseId ? Student::where('course_id', $selectedCourseId)->count() : $totalStudents;
        $data['total_courses'] = $totalCourses;

        // Metrics for dashboard cards, calculated from real data.
        $todaysPercent = Attendance::presentPercent(Attendance::whereDate('date', today())->get(['status']), 0);
        // No hay registros de asistencia hoy: se muestra 0% en vez de un porcentaje inventado.
        $data['today_attendance'] = ($todaysPercent ?? 0) . '%';

        // Retos de robot lanzados este mes (no el histórico total), para reflejar la actividad reciente del aula.
        $data['robot_challenges'] = \App\Models\Grade::where('is_robot_activity', true)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $data['role'] = $user->role;
        $data['rol'] = $user->role;

        return view('dashboard', compact('data', 'courses', 'selectedCourseId'));
    }
}

