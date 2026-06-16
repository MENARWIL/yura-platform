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
                'total_profesores' => User::where('rol', 'profesor')->count(),
                'total_responsables' => User::whereIn('rol', ['padre', 'madre', 'tutor'])->count(),
            ];
            $data['recientes'] = Student::with('padre')->latest()->take(5)->get();
        } elseif ($user->isProfesor()) {
            $data['metrics'] = [
                'mis_estudiantes_count' => Student::where('profesor_id', $user->id)->count(),
            ];
            $data['ranking'] = Student::where('profesor_id', $user->id)
                ->orderBy('puntaje', 'desc')
                ->take(10)
                ->get();

            $data['distribucion'] = [
                'basico' => Student::where('profesor_id', $user->id)->where('nivel', 'básico')->count(),
                'intermedio' => Student::where('profesor_id', $user->id)->where('nivel', 'intermedio')->count(),
                'avanzado' => Student::where('profesor_id', $user->id)->where('nivel', 'avanzado')->count(),
            ];
        } elseif ($user->isEstudiante()) {
            $student = $user->estudiantePerfil()->with(['profesor', 'padre'])->first();
            $data['metrics'] = [
                'mi_estudiante' => $student?->nombre,
                'mi_promedio' => $student?->promedio ?? 0,
                'mi_asistencia' => $student?->asistencia ?? 0,
            ];
        } else {
            $data['mis_hijos'] = Student::where('usuario_id', $user->id)
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
