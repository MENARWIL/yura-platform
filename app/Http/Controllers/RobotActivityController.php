<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Course;
use App\Models\Parallel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class RobotActivityController extends Controller
{
    public function create()
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, 'No tienes permiso para crear actividades de YURA.');
        }

        $parallels = Parallel::with('course')->get();
        $categories = [
            'Alimentos' => 'robot_alimentos',
            'Colores' => 'robot_colores',
            'Casa' => 'robot_casa_items',
            'Naturaleza' => 'robot_naturaleza_items',
            'Familia' => 'robot_familia_items',
            'Herramientas' => 'robot_herramientas_items',
            'Lugares' => 'robot_lugares_items',
        ];

        return view('robot.create', compact('parallels', 'categories'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, 'No tienes permiso para crear actividades de YURA.');
        }

        $request->validate([
            'parallel_id' => 'required|exists:parallels,id',
            'course_id' => 'required|exists:courses,id',
            'category' => 'required|string|in:Alimentos,Colores,Casa,Naturaleza,Familia,Herramientas,Lugares',
            'description' => 'nullable|string|max:500',
        ]);

        $parallel = \App\Models\Parallel::findOrFail($request->parallel_id);

        $grade = Grade::create([
            'student_id' => $parallel->students()->first()?->id ?? null,
            'subject_id' => $request->course_id,
            'score' => 0,
            'type' => 'YURA',
            'observations' => $request->description,
            'status' => 'pending',
            'activity_category' => $request->category,
            'is_robot_activity' => true,
        ]);

        if (! $grade->student_id) {
            $grade->delete();

            return back()->with('error', 'No se pudo crear la actividad porque el paralelo no tiene estudiantes registrados.');
        }

        return redirect()->route('grades.index')->with('success', 'Actividad de YURA registrada en estado pendiente.');
    }
}
