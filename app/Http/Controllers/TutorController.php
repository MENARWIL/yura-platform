<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TutorController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();

        if (! $user->isAdmin() && ! $user->isAcademic()) {
            abort(403, __('messages.unauthorized'));
        }

        return view('tutors.create', [
            'target' => $request->input('target', 'primary'),
        ]);
    }

    public function edit(User $tutor)
    {
        abort_unless($tutor->isResponsibleRole(), 404);

        return view('tutors.edit', compact('tutor'));
    }

    public function update(Request $request, User $tutor): RedirectResponse
    {
        abort_unless($tutor->isResponsibleRole(), 404);

        $request->merge([
            'name' => preg_replace('/[ \t]+/u', ' ', trim((string) $request->input('name'))),
            'phone' => trim((string) $request->input('phone')),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+() -]+$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($tutor->id)],
            'status' => ['required', 'in:active,activo,inactive,inactivo'],
            'password' => ['nullable', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'name.required' => __('messages.validation.required'),
            'name.regex' => __('messages.validation.name'),
            'phone.required' => __('messages.validation.required'),
            'phone.regex' => __('messages.validation.phone'),
            'email.required' => __('messages.validation.required'),
            'email.email' => __('messages.validation.email'),
            'email.unique' => __('messages.validation.unique'),
            'password.*' => __('messages.validation.password'),
        ]);

        $tutor->name = $data['name'];
        $tutor->phone = $data['phone'];
        $tutor->email = $data['email'];
        $tutor->status = in_array($data['status'], ['active', 'activo'], true) ? 'active' : 'inactive';

        if (!empty($data['password'])) {
            $tutor->password = Hash::make($data['password']);
        }

        $tutor->save();

        return redirect()->route('family-members.index')->with('success', __('messages.tutor_updated'));
    }

    public function toggleStatus(User $tutor): RedirectResponse
    {
        abort_unless($tutor->isResponsibleRole(), 404);

        $tutor->status = in_array($tutor->status, ['active', 'activo'], true) ? 'inactive' : 'active';
        $tutor->save();

        return redirect()->route('family-members.index')->with('success', __('messages.tutor_status_updated'));
    }

    public function myChildren(Request $request)
    {
        /** @var User|null $tutor */
        $tutor = $request->user();

        if (! $tutor || $tutor->role !== 'tutor' || $tutor->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $students = Student::with(['course', 'parallel', 'grades.subject'])
            ->where(function ($query) use ($tutor) {
                $query->where('parent_user_id', $tutor->id)
                    ->orWhereHas('familyMembers', function ($familyQuery) use ($tutor) {
                        $familyQuery->where('users.id', $tutor->id)
                            ->where('student_family_members.active', true);
                    });
            })
            ->get();

        $data = $students->map(function (Student $student) {
            $gradesBySubject = $student->grades
                ->groupBy('subject_id')
                ->map(function ($grades, $subjectId) {
                    return [
                        'subject_id' => (int) $subjectId,
                        'subject_name' => $grades->first()->subject_display_name,
                        'scores' => $grades->mapWithKeys(function ($grade) {
                            return [
                                $grade->type => [
                                    'id' => $grade->id,
                                    'score' => (float) $grade->score,
                                    'status' => $grade->status,
                                ],
                            ];
                        }),
                    ];
                })
                ->values();

            return [
                'id' => $student->id,
                'name' => $student->name,
                'course' => $student->course ? [
                    'id' => $student->course->id,
                    'name' => $student->course->name,
                ] : null,
                'parallel' => $student->parallel ? [
                    'id' => $student->parallel->id,
                    'name' => $student->parallel->name,
                ] : null,
                'grades' => $gradesBySubject,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'ci' => $request->input('ci') !== null ? trim($request->input('ci')) : null,
            'name' => $request->input('name') !== null ? preg_replace('/\s+/', ' ', trim($request->input('name'))) : null,
            'lastname' => $request->input('lastname') !== null ? preg_replace('/\s+/', ' ', trim($request->input('lastname'))) : null,
            'phone' => $request->input('phone') !== null ? preg_replace('/\s+/', ' ', trim($request->input('phone'))) : null,
            'email' => $request->input('email') !== null ? strtolower(trim($request->input('email'))) : null,
        ]);

        $validated = $request->validate([
            'ci' => ['required', 'string', 'max:50', 'regex:/^[0-9A-Za-z-]+$/', Rule::unique('users', 'ci')],
            'name' => ['required', 'string', 'max:100', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'],
            'lastname' => ['required', 'string', 'max:100', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+() -]+$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'ci.required' => __('messages.validation.required'),
            'ci.unique' => __('messages.validation.unique'),
            'name.required' => __('messages.validation.required'),
            'name.regex' => __('messages.validation.name'),
            'lastname.required' => __('messages.validation.required'),
            'lastname.regex' => __('messages.validation.name'),
            'phone.required' => __('messages.validation.required'),
            'phone.regex' => __('messages.validation.phone'),
            'email.required' => __('messages.validation.required'),
            'email.email' => __('messages.validation.email'),
            'email.unique' => __('messages.validation.unique'),
            'password.required' => __('messages.validation.required'),
            'password.min' => __('messages.validation.password'),
            'password.letters' => __('messages.validation.password'),
            'password.mixed' => __('messages.validation.password'),
            'password.numbers' => __('messages.validation.password'),
            'password.symbols' => __('messages.validation.password'),
        ]);

        $tutor = User::create([
            'ci' => $validated['ci'],
            'name' => trim($validated['name'] . ' ' . $validated['lastname']),
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'tutor',
            'status' => 'active',
        ]);

        return redirect()
            ->route('students.create', [
                'new_tutor_id' => $tutor->id,
                'target' => $request->input('target', 'primary'),
            ])
            ->with('success', __('messages.tutor_created'));
    }
}
