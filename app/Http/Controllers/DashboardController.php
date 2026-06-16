<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data = [];

        if ($user->isAdmin()) {
            $data['total_estudiantes'] = Student::count();
            $data['total_profesores'] = User::where('rol', 'profesor')->count();
            $data['total_padres'] = User::whereIn('rol', ['padre', 'madre', 'tutor'])->count();
            $data['recientes'] = Student::with('padre')->latest()->take(5)->get();
        } elseif ($user->isProfesor()) {
            $data['mis_estudiantes_count'] = Student::where('profesor_id', $user->id)->count();
            $data['ranking'] = Student::where('profesor_id', $user->id)
                ->orderBy('puntaje', 'desc')
                ->take(10)
                ->get()
                ->map(function ($student) {
                    $student->promedio = $student->promedio;

                    return $student;
                });

            $data['distribucion'] = [
                'basico' => Student::where('profesor_id', $user->id)->where('nivel', 'básico')->count(),
                'intermedio' => Student::where('profesor_id', $user->id)->where('nivel', 'intermedio')->count(),
                'avanzado' => Student::where('profesor_id', $user->id)->where('nivel', 'avanzado')->count(),
            ];
        } elseif ($user->isEstudiante()) {
            $data['mi_estudiante'] = $user->estudiantePerfil()->with(['profesor', 'padre'])->first();
            $data['mi_promedio'] = $data['mi_estudiante']?->promedio ?? 0;
            $data['mi_asistencia'] = $data['mi_estudiante']?->asistencia ?? 0;
            $data['mi_profesor'] = $data['mi_estudiante']?->profesor?->name;
            $data['mi_nivel'] = $data['mi_estudiante']?->nivel;
        } else {
            $data['mis_hijos'] = Student::where('usuario_id', $user->id)
                ->orWhereHas('familiares', function ($family) use ($user) {
                    $family->where('user_id', $user->id);
                })
                ->with('profesor')
                ->get()
                ->map(function ($student) {
                    $student->promedio = $student->promedio;

                    return $student;
                });
            $data['hijos_count'] = $data['mis_hijos']->count();
        }

        $data['role'] = $user->rol;
        $data['rol'] = $user->rol;

        return view('dashboard', compact('data'));
    }
}

