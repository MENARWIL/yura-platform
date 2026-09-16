<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
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
        } elseif ($user->isTutor()) {
            $parentForeignKey = (new Student())->getParentUserForeignKey();

            $children = Student::where(function ($query) use ($parentForeignKey, $user) {
                $query->where($parentForeignKey, $user->id)
                    ->orWhereHas('familiares', function ($family) use ($user) {
                        $family->where('user_id', $user->id)
                            ->where('student_family_members.active', true);
                    });
            })
                ->with(['course', 'parallel', 'grades.subject', 'attendances'])
                ->get();

            $data['mis_hijos'] = $children->map(function ($student) {
                $academicGrades = $student->grades
                    ->where('status', 'completed')
                    ->where('is_robot_activity', false)
                    ->filter(fn ($grade) => $grade->score !== null);
                $activityGrades = $academicGrades->where('type', 'activity');
                $examGrades = $academicGrades->where('type', 'exam');
                $robotGrades = $student->grades
                    ->where('status', 'completed')
                    ->where('is_robot_activity', true)
                    ->filter(fn ($grade) => $grade->score !== null);
                $attendanceTotal = $student->attendances->count();
                $attendancePresent = $student->attendances->filter(
                    fn ($attendance) => strtolower(trim((string) $attendance->status)) === 'presente'
                )->count();

                $student->dashboard_report = [
                    'average' => $academicGrades->count() ? round($academicGrades->avg('score'), 2) : null,
                    'activity_average' => $activityGrades->count() ? round($activityGrades->avg('score'), 2) : null,
                    'exam_average' => $examGrades->count() ? round($examGrades->avg('score'), 2) : null,
                    'robot_average' => $robotGrades->count() ? round($robotGrades->avg('score'), 2) : null,
                    'activity_count' => $activityGrades->count(),
                    'exam_count' => $examGrades->count(),
                    'robot_count' => $robotGrades->count(),
                    'quarter_averages' => collect([1, 2, 3])->mapWithKeys(function ($quarter) use ($academicGrades) {
                        $grades = $academicGrades->where('quarter', $quarter);

                        return [$quarter => $grades->count() ? round($grades->avg('score'), 2) : null];
                    }),
                    'subjects' => $academicGrades->groupBy(fn ($grade) => $grade->subject?->name ?? $grade->subject_display_name)
                        ->map(fn ($grades) => [
                            'name' => $grades->first()->subject?->name ?? $grades->first()->subject_display_name,
                            'average' => round($grades->avg('score'), 2),
                            'count' => $grades->count(),
                        ])->values(),
                    'attendance_percent' => $attendanceTotal ? round(($attendancePresent / $attendanceTotal) * 100, 2) : null,
                    'attendance_present' => $attendancePresent,
                    'attendance_total' => $attendanceTotal,
                    'robot_total' => $student->grades->where('is_robot_activity', true)->count(),
                    'robot_completed' => $student->grades
                        ->where('is_robot_activity', true)
                        ->where('status', 'completed')
                        ->count(),
                ];

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
                'attendance_percent' => $allAttendance->count()
                    ? round(($allAttendance->filter(fn ($attendance) => strtolower(trim((string) $attendance->status)) === 'presente')->count() / $allAttendance->count()) * 100, 2)
                    : null,
                'robot_total' => $data['mis_hijos']->sum(fn ($student) => $student->dashboard_report['robot_total']),
            ];
        } elseif ($user->isProfesor()) {
            $teacherForeignKey = (new Student())->getTeacherUserForeignKey();
            $levelColumn = 'level';
            $studentsQuery = Student::where($teacherForeignKey, $user->id)
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

            $todayAttendance = $students->flatMap(fn ($student) => $student->attendances->where('date', $today));
            $presentToday = $todayAttendance->filter(fn ($attendance) => strtolower(trim((string) $attendance->status)) === 'presente')->count();
            $attendancePercent = $todayAttendance->count() ? round(($presentToday / $todayAttendance->count()) * 100, 2) : 0;

            $data['mis_estudiantes_count'] = $students->count();
            $data['mis_estudiantes'] = $students;
            $data['promedio_general'] = $studentAverages->count() ? round($studentAverages->avg('promedio_academico'), 2) : 0;
            $data['asistencia_hoy'] = $attendancePercent;
            $data['bajo_rendimiento'] = $studentAverages->filter(fn ($student) => $student->promedio_academico < 60)->count();
            $data['ranking'] = $studentAverages->sortByDesc('promedio_academico')->take(5)->values();
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
            $data['mi_estudiante'] = $user->studentProfile()->with(['profesor', 'padre'])->first();
            $data['mi_promedio'] = $data['mi_estudiante']?->promedio ?? 0;
            $data['mi_asistencia'] = $data['mi_estudiante']?->attendance ?? 0;
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
                    $student->promedio = $student->promedio ?? 0;

                    return $student;
                });
            $data['hijos_count'] = $data['mis_hijos']->count();
        }

        $data['total_students'] = $selectedCourseId ? Student::where('course_id', $selectedCourseId)->count() : $totalStudents;
        $data['total_courses'] = $totalCourses;
        // Mock metrics for dashboard cards
        $data['today_attendance'] = '94%';
        $data['robot_challenges'] = 12;
        $data['role'] = $user->rol;
        $data['rol'] = $user->rol;

        return view('dashboard', compact('data', 'courses', 'selectedCourseId'));
    }
}

