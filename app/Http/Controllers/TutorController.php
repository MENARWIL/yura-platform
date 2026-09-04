<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TutorController extends Controller
{
    public function create()
    {
        $user = Auth::user();

        if (! $user->isAdmin() && ! $user->isAcademic()) {
            abort(403, __('messages.unauthorized'));
        }

        return view('tutors.create');
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
        $validated = $request->validate([
            'ci' => ['required', 'string', 'max:50', Rule::unique('users', 'ci')],
            'name' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'ci.required' => 'The identification number is required.',
            'ci.unique' => 'The identification number is already registered.',
            'name.required' => 'The first name is required.',
            'lastname.required' => 'The last name is required.',
            'phone.required' => 'The phone number is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'The email address must be valid.',
            'email.unique' => 'The email address is already registered.',
            'password.required' => 'The password is required.',
            'password.min' => 'The password must contain at least 8 characters.',
        ]);

        User::create([
            'ci' => $validated['ci'],
            'name' => trim($validated['name'] . ' ' . $validated['lastname']),
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'tutor',
            'status' => 'active',
        ]);

        return redirect()
            ->route('students.create')
            ->with('success', 'Tutor registered successfully.');
    }
}
