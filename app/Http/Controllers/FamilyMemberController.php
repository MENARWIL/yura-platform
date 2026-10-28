<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentFamilyMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FamilyMemberController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'student_name' => ['nullable', 'string', 'max:255'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'parallel_id' => ['nullable', 'integer', 'exists:parallels,id'],
            'per_page' => ['nullable', 'integer', Rule::in([5, 10, 15, 20])],
        ]);

        $authUser = Auth::user();
        $search = preg_replace('/\s+/', ' ', trim((string) ($filters['student_name'] ?? ''))) ?: null;

        $familyMembers = StudentFamilyMember::with(['student.course', 'student.parallel', 'user'])
            ->where('active', true)
            ->where('is_primary', true)
            ->when($authUser->isResponsibleRole(), function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id);
            })
            // Busca por nombre o apellidos del estudiante, o por el nombre del tutor.
            ->when($search, function ($query, $search) {
                $like = '%' . $search . '%';
                $query->where(function ($q) use ($like) {
                    $q->whereHas('student', fn ($s) => $s->where('name', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('second_last_name', 'like', $like))
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)
                            ->orWhere('last_name', 'like', $like));
                });
            })
            ->whereHas('student', function ($query) use ($filters) {
                $query->when($filters['course_id'] ?? null, function ($studentQuery, $courseId) {
                    $studentQuery->where('course_id', $courseId);
                })->when($filters['parallel_id'] ?? null, function ($studentQuery, $parallelId) {
                    $studentQuery->where('parallel_id', $parallelId);
                });
            })
            ->latest()
            ->get()
            ->sortBy(function ($member) {
                $lastName = $member->user->last_name ?? '';

                return $lastName !== ''
                    ? '0_' . mb_strtolower($lastName)
                    : '1_' . mb_strtolower($member->user->name ?? '');
            })
            ->values();

        // De 5 en 5 (o lo que elija el usuario), sobre la lista ya ordenada por apellido del tutor.
        $perPage = (int) ($filters['per_page'] ?? 5);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $familyMembers = (new LengthAwarePaginator(
            $familyMembers->forPage($page, $perPage)->values(),
            $familyMembers->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        ))->withQueryString();

        $courses = \App\Models\Course::orderBy('name')->get();
        $parallels = \App\Models\Parallel::with('course')->orderBy('name')->get();
        $responsables = User::whereIn('role', ['padre', 'madre', 'tutor'])
            ->whereIn('status', ['active', 'activo'])
            ->orderBy('name')
            ->get();

        return view('family.index', compact('familyMembers', 'responsables', 'courses', 'parallels'));
    }

    public function store(Request $request)
    {
        // Only admin and academic roles can create family relationships
        if (! Auth::user()->isAdmin() && ! Auth::user()->isAcademic()) {
            abort(403, 'No tienes permiso para crear relaciones familiares.');
        }

        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
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
        // Only admin and academic roles can update family relationships
        if (! Auth::user()->isAdmin() && ! Auth::user()->isAcademic()) {
            abort(403, 'No tienes permiso para editar relaciones familiares.');
        }

        if ($request->filled('usuario_id') && ! $request->filled('user_id')) {
            $request->merge(['user_id' => $request->input('usuario_id')]);
        }

        if (! $request->filled('user_id')) {
            $request->merge(['user_id' => $familyMember->user_id]);
        }

        $data = $request->validate([
            'relation_type' => ['required', 'in:padre,madre,tutor'],
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['padre', 'madre', 'tutor'])
                        ->whereIn('status', ['active', 'activo']);
                }),
            ],
            'is_primary' => ['nullable', 'boolean'],
            'can_view' => ['nullable', 'boolean'],
            'can_edit' => ['nullable', 'boolean'],
            'can_receive_reports' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        StudentFamilyMember::where('student_id', $familyMember->student_id)
            ->update(['is_primary' => false]);

        StudentFamilyMember::updateOrCreate(
            [
                'student_id' => $familyMember->student_id,
                'user_id' => $data['user_id'],
            ],
            [
                'relation_type' => $data['relation_type'],
                'is_primary' => $request->boolean('is_primary', true),
                'can_view' => $request->boolean('can_view', true),
                'can_edit' => $request->boolean('can_edit', false),
                'can_receive_reports' => $request->boolean('can_receive_reports', true),
                'active' => $request->boolean('active', true),
            ]
        );

        return redirect()->route('family-members.index')->with('success', 'Familiar actualizado correctamente.');
    }

    public function destroy(StudentFamilyMember $familyMember)
    {
        // Only admin and academic roles can delete family relationships
        if (! Auth::user()->isAdmin() && ! Auth::user()->isAcademic()) {
            abort(403, 'No tienes permiso para eliminar relaciones familiares.');
        }

        $familyMember->delete();

        return redirect()->route('family-members.index')->with('success', 'Familiar eliminado correctamente.');
    }
}
