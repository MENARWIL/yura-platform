<?php

namespace App\Http\Controllers;

use App\Models\Parallel;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeachingAssignmentController extends Controller
{
    public function index()
    {
        $assignments = TeachingAssignment::with(['teacher', 'subject', 'parallel.course'])
            ->orderByDesc('active')
            ->orderBy('parallel_id')
            ->get();

        return view('teaching-assignments.index', compact('assignments'));
    }

    public function create()
    {
        $teachers = User::whereIn('role', ['profesor', 'teacher'])
            ->whereIn('status', ['active', 'activo'])
            ->orderBy('name')
            ->get();
        $subjects = Subject::where('active', true)->orderBy('sort_order')->orderBy('name')->get();
        $parallels = Parallel::with('course')->orderBy('course_id')->orderBy('name')->get();

        return view('teaching-assignments.create', compact('teachers', 'subjects', 'parallels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['profesor', 'teacher'])->whereIn('status', ['active', 'activo']))],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'parallel_id' => ['required', 'integer', 'exists:parallels,id'],
        ]);

        $alreadyAssigned = TeachingAssignment::where('subject_id', $data['subject_id'])
            ->where('parallel_id', $data['parallel_id'])
            ->exists();

        if ($alreadyAssigned) {
            return back()->withErrors(['subject_id' => 'Este paralelo ya tiene una asignación para esa asignatura.'])->withInput();
        }

        TeachingAssignment::create($data + ['active' => true]);

        return redirect()->route('teaching-assignments.index')->with('success', 'Asignación docente creada correctamente.');
    }

    public function update(Request $request, TeachingAssignment $teachingAssignment)
    {
        $teachingAssignment->update([
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('teaching-assignments.index')->with('success', 'Estado de la asignación actualizado.');
    }

    public function destroy(TeachingAssignment $teachingAssignment)
    {
        $teachingAssignment->delete();

        return redirect()->route('teaching-assignments.index')->with('success', 'Asignación docente eliminada.');
    }
}