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
        } elseif ($user->isProfesor()) {
            $teacherForeignKey = (new Student())->getTeacherUserForeignKey();
            $levelColumn = 'level';
            $studentsQuery = Student::where($teacherForeignKey, $user->id)->when($selectedCourseId, function ($q) use ($selectedCourseId) { return $q->where('course_id', $selectedCourseId); })->with(['course', 'parallel']);

            $data['mis_estudiantes_count'] = $studentsQuery->count();
            $data['mis_estudiantes'] = $studentsQuery->get();
            $data['ranking'] = $studentsQuery->clone()->orderBy('score', 'desc')->take(10)->get()->map(function ($student) {
                $student->promedio = $student->promedio ?? 0;

                return $student;
            });

            $data['distribucion'] = [
                'basico' => $studentsQuery->clone()->where($levelColumn, 'básico')->count(),
                'intermedio' => $studentsQuery->clone()->where($levelColumn, 'intermedio')->count(),
                'avanzado' => $studentsQuery->clone()->where($levelColumn, 'avanzado')->count(),
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

