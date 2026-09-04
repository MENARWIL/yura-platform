<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $data = [];

        if ($user->isAdmin()) {
            $data['metrics'] = [
                'total_estudiantes' => Student::count(),
                'total_profesores' => User::ofRole('profesor')->count(),
                'total_responsables' => User::ofRole(['padre', 'madre', 'tutor'])->count(),
            ];
            $data['recientes'] = Student::with(['padre', 'profesor', 'parallel', 'course'])->latest()->take(5)->get();
        } elseif ($user->isProfesor()) {
            $teacherForeignKey = (new Student())->getTeacherUserForeignKey();
            $levelColumn = 'level';

            $data['metrics'] = [
                'mis_estudiantes_count' => Student::where($teacherForeignKey, $user->id)->count(),
            ];
            $data['ranking'] = Student::where($teacherForeignKey, $user->id)
                ->orderBy('score', 'desc')
                ->take(10)
                ->get();

            $data['distribucion'] = [
                'basico' => Student::where($teacherForeignKey, $user->id)->where($levelColumn, 'básico')->count(),
                'intermedio' => Student::where($teacherForeignKey, $user->id)->where($levelColumn, 'intermedio')->count(),
                'avanzado' => Student::where($teacherForeignKey, $user->id)->where($levelColumn, 'avanzado')->count(),
            ];
        } elseif ($user->isEstudiante()) {
            $student = $user->studentProfile()->with(['profesor', 'padre'])->first();
            $data['metrics'] = [
                'mi_estudiante' => $student?->nombre,
                'mi_promedio' => $student?->promedio ?? 0,
                'mi_asistencia' => $student?->asistencia ?? 0,
            ];
        } else {
            $parentForeignKey = (new Student())->getParentUserForeignKey();

            $data['mis_hijos'] = Student::where($parentForeignKey, $user->id)
                ->orWhereHas('familiares', function ($family) use ($user) {
                    $family->where('user_id', $user->id);
                })
                ->with('profesor')
                ->get();
            $data['metrics'] = [
                'hijos_count' => $data['mis_hijos']->count(),
            ];
        }

        return response()->json(array_merge([
            'success' => true,
            'role' => $user->rol,
            'rol' => $user->rol,
        ], $data));
    }
}
