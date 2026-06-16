<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentFamilyMember;
use App\Models\User;
use Illuminate\Http\Request;

class FamilyMemberController extends Controller
{
    public function index()
    {
        $students = Student::with(['padre', 'profesor', 'familiares'])->get();
        $familyMembers = StudentFamilyMember::with(['student', 'user'])->latest()->get();
        $responsables = User::whereIn('rol', ['padre', 'madre', 'tutor'])
            ->where('estado', 'activo')
            ->orderBy('name')
            ->get();

        return view('family.index', compact('students', 'familyMembers', 'responsables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:estudiantes,id'],
            'user_id' => ['required', 'exists:users,id'],
            'relation_type' => ['required', 'in:padre,madre,tutor'],
            'is_primary' => ['nullable', 'boolean'],
            'can_view' => ['nullable', 'boolean'],
            'can_edit' => ['nullable', 'boolean'],
            'can_receive_reports' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        $student = Student::findOrFail($request->student_id);
        $familyUser = User::findOrFail($request->user_id);

        if (! $familyUser->isResponsibleRole()) {
            return back()->withErrors(['user_id' => 'El usuario seleccionado debe ser padre, madre o tutor.'])->withInput();
        }

        if (auth()->user()->isProfesor() && $student->profesor_id != auth()->id()) {
            abort(403, 'No tienes permiso para administrar este estudiante.');
        }

        if ($request->boolean('is_primary')) {
            StudentFamilyMember::where('student_id', $student->id)->update(['is_primary' => false]);
        }

        StudentFamilyMember::updateOrCreate(
            ['student_id' => $student->id, 'user_id' => $familyUser->id],
            [
                'relation_type' => $data['relation_type'],
                'is_primary' => $request->boolean('is_primary'),
                'can_view' => $request->boolean('can_view', true),
                'can_edit' => $request->boolean('can_edit', false),
                'can_receive_reports' => $request->boolean('can_receive_reports', true),
                'active' => $request->boolean('active', true),
            ]
        );

        return redirect()->route('family-members.index')->with('success', 'Familiar asignado correctamente.');
    }

    public function update(Request $request, StudentFamilyMember $familyMember)
    {
        $data = $request->validate([
            'relation_type' => ['required', 'in:padre,madre,tutor'],
            'is_primary' => ['nullable', 'boolean'],
            'can_view' => ['nullable', 'boolean'],
            'can_edit' => ['nullable', 'boolean'],
            'can_receive_reports' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        if (auth()->user()->isProfesor() && $familyMember->student->profesor_id != auth()->id()) {
            abort(403, 'No tienes permiso para administrar este estudiante.');
        }

        if ($request->boolean('is_primary')) {
            StudentFamilyMember::where('student_id', $familyMember->student_id)->update(['is_primary' => false]);
        }

        $familyMember->update([
            'relation_type' => $data['relation_type'],
            'is_primary' => $request->boolean('is_primary'),
            'can_view' => $request->boolean('can_view', true),
            'can_edit' => $request->boolean('can_edit', false),
            'can_receive_reports' => $request->boolean('can_receive_reports', true),
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('family-members.index')->with('success', 'Familiar actualizado correctamente.');
    }

    public function destroy(StudentFamilyMember $familyMember)
    {
        if (auth()->user()->isProfesor() && $familyMember->student->profesor_id != auth()->id()) {
            abort(403, 'No tienes permiso para administrar este estudiante.');
        }

        $familyMember->delete();

        return redirect()->route('family-members.index')->with('success', 'Familiar eliminado correctamente.');
    }
}
