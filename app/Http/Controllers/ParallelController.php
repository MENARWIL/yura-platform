<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Parallel;
use Illuminate\Http\Request;

class ParallelController extends Controller
{
    public function index()
    {
        $parallels = Parallel::withCount('students')->with('course')->get();

        return view('parallels.index', compact('parallels'));
    }

    public function create()
    {
        $courses = Course::all();

        return view('parallels.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'course_id' => 'required|exists:courses,id',
            'max_students' => 'required|integer|min:1',
        ]);

        Parallel::create($request->only(['name', 'course_id', 'max_students']));

        return redirect()->route('parallels.index')->with('success', 'Paralelo creado correctamente.');
    }

    public function edit(Parallel $parallel)
    {
        $courses = Course::all();

        return view('parallels.edit', compact('parallel', 'courses'));
    }

    public function update(Request $request, Parallel $parallel)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'course_id' => 'required|exists:courses,id',
            'max_students' => 'required|integer|min:1',
        ]);

        $parallel->update($request->only(['name', 'course_id', 'max_students']));

        return redirect()->route('parallels.index')->with('success', 'Capacidad de paralelo actualizada correctamente.');
    }
}
