<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Parallel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParallelController extends Controller
{
    public function index()
    {
        // Cursos y paralelos se gestionan juntos en una sola pantalla.
        return redirect()->route('courses.index');
    }

    public function create()
    {
        $courses = Course::all();

        return view('parallels.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('parallels')->where('course_id', $request->input('course_id')),
            ],
            'course_id' => 'required|exists:courses,id',
            'max_students' => 'required|integer|min:1',
        ], [
            'name.unique' => 'Ya existe un paralelo con ese nombre en el curso seleccionado.',
        ]);

        Parallel::create($request->only(['name', 'course_id', 'max_students']));

        return redirect()->route('courses.index')->with('success', 'Paralelo creado correctamente.');
    }

    public function edit(Parallel $parallel)
    {
        $courses = Course::all();

        return view('parallels.edit', compact('parallel', 'courses'));
    }

    public function update(Request $request, Parallel $parallel)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('parallels')->where('course_id', $request->input('course_id'))->ignore($parallel->id),
            ],
            'course_id' => 'required|exists:courses,id',
            'max_students' => 'required|integer|min:1',
        ], [
            'name.unique' => 'Ya existe un paralelo con ese nombre en el curso seleccionado.',
        ]);

        $parallel->update($request->only(['name', 'course_id', 'max_students']));

        return redirect()->route('courses.index')->with('success', 'Paralelo actualizado correctamente.');
    }

    public function destroy(Parallel $parallel)
    {
        if ($parallel->students()->exists()) {
            return redirect()->route('courses.index')->with('error', 'No se puede eliminar el paralelo: tiene estudiantes asignados. Reasigne o elimine primero a los estudiantes.');
        }

        $parallel->delete();

        return redirect()->route('courses.index')->with('success', 'Paralelo eliminado correctamente.');
    }
}
