<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::withCount('parallels')
            ->with(['parallels' => function ($query) {
                $query->withCount('students')->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        return view('courses.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name',
            'nivel_educativo' => 'required|string|in:' . implode(',', array_keys(Course::NIVELES)),
        ]);

        Course::create($data);

        return redirect()->route('courses.index')->with('success', 'Curso creado correctamente.');
    }

    public function edit(Course $course)
    {
        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name,' . $course->id,
            'nivel_educativo' => 'required|string|in:' . implode(',', array_keys(Course::NIVELES)),
        ]);

        $course->update($data);

        return redirect()->route('courses.index')->with('success', 'Curso actualizado correctamente.');
    }

    public function destroy(Course $course)
    {
        if ($course->parallels()->exists()) {
            return redirect()->route('courses.index')->with('error', 'No se puede eliminar el curso: tiene paralelos asociados. Elimine primero los paralelos.');
        }

        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Curso eliminado correctamente.');
    }
}
