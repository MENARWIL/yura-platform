<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\GradeHistory;
use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAcademic() && !$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $subjects = Subject::where('active', true)->orderBy('sort_order')->orderBy('name')->get();
        $parallels = Parallel::with('course')->orderBy('course_id')->orderBy('name')->get();
        $selectedSubjectId = $request->integer('subject_id');
        $selectedParallelId = $request->integer('parallel_id');
        $selectedQuarter = $request->input('quarter', 1);

        $query = Student::with(['parent', 'teacher', 'parallel']);

        if ($user->isProfesor()) {
            $query->where('teacher_user_id', $user->id)
                ->when($selectedParallelId, function ($query) use ($selectedParallelId) {
                    return $query->where('parallel_id', $selectedParallelId);
                });

            $assignedSubjectIds = TeachingAssignment::where('teacher_user_id', $user->id)
                ->where('active', true)
                ->distinct()
                ->pluck('subject_id');
            $ownedParallelIds = Student::where('teacher_user_id', $user->id)
                ->whereNotNull('parallel_id')
                ->distinct()
                ->pluck('parallel_id');

            $subjects = $subjects->whereIn('id', $assignedSubjectIds)->values();
            $parallels = $parallels->whereIn('id', $ownedParallelIds)->values();
        } elseif ($selectedParallelId) {
            $query->where('parallel_id', $selectedParallelId);
        }

        $students = $query->get();
        $studentIds = $students->pluck('id')->all();
        $today = now()->toDateString();

        $todayAttendance = Attendance::whereIn('student_id', $studentIds)
            ->when($selectedSubjectId, function ($query) use ($selectedSubjectId) {
                return $query->where('subject_id', $selectedSubjectId);
            })
            ->whereDate('date', $today)
            ->get()
            ->keyBy('student_id');

        $gradesByStudent = Grade::whereIn('student_id', $studentIds)
            ->where('status', 'completed')
            ->where('quarter', $selectedQuarter)
            ->when($selectedSubjectId, function ($query) use ($selectedSubjectId) {
                return $query->where('subject_id', $selectedSubjectId);
            })
            ->get()
            ->groupBy('student_id')
            ->mapWithKeys(function ($grades, $studentId) {
                return [$studentId => $grades->groupBy('type')->map(function ($typeGrades) {
                    return $typeGrades->keyBy('activity_number');
                })];
            });

        $attendanceStatuses = ['Presente', 'Falta', 'Atraso'];
        $robotCategories = [
            'Alimentos',
            'Colores',
            'Casa',
            'Naturaleza',
            'Familia',
            'Herramientas',
            'Lugares',
        ];

        return view('grades.index', compact('students', 'subjects', 'parallels', 'selectedSubjectId', 'selectedParallelId', 'selectedQuarter', 'attendanceStatuses', 'robotCategories', 'todayAttendance', 'gradesByStudent'));
    }

    public function show(Student $student)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAcademic() && !($user->isProfesor()
            && (TeachingAssignment::where('teacher_user_id', $user->id)
                ->where('parallel_id', $student->parallel_id)
                ->where('active', true)
                ->exists() || (int) $student->teacher_user_id === (int) $user->id))) {
            abort(403, __('messages.unauthorized'));
        }

        $grades = Grade::where('student_id', $student->id)->with('subject')->get();
        $history = GradeHistory::whereHas('grade', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })->with(['grade', 'user'])->latest()->paginate(15);

        return view('grades.show', compact('student', 'grades', 'history'));
    }

    public function historyForStudent(Student $student)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAcademic() && !($user->isProfesor()
            && (TeachingAssignment::where('teacher_user_id', $user->id)
                ->where('parallel_id', $student->parallel_id)
                ->where('active', true)
                ->exists() || (int) $student->teacher_user_id === (int) $user->id))) {
            abort(403, __('messages.unauthorized'));
        }

        $grade = $student->grades()->with('subject')->latest()->first();
        $history = GradeHistory::whereHas('grade', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })->with(['grade', 'user'])->latest()->paginate(15);

        return view('grades.history', compact('student', 'grade', 'history'));
    }

    public function edit(Grade $grade)
    {
        /** @var User $user */
        $user = Auth::user();
        $student = $grade->student;

        if (!$user->isProfesor() || ! $this->hasTeachingAssignment($user, $student, (int) $grade->subject_id)) {
            abort(403, __('messages.unauthorized'));
        }

        return view('grades.edit', compact('grade', 'student'));
    }

    public function update(Request $request, Grade $grade)
    {
        /** @var User $user */
        $user = Auth::user();
        $student = $grade->student;

        if (!$user->isProfesor() || ! $this->hasTeachingAssignment($user, $student, (int) $grade->subject_id)) {
            abort(403, __('messages.unauthorized'));
        }

        $data = $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'type' => 'nullable|string|max:50',
            'observations' => 'nullable|string|max:1000',
            'reason' => 'nullable|string|max:500',
            'attendance' => 'nullable|string|in:Presente,Falta,Atraso',
            'exam_score' => 'nullable|numeric|min:0|max:100',
            'activity_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $grade->update([
            'score' => $data['score'],
            'type' => $data['type'] ?? $grade->type,
            'observations' => $data['observations'] ?? $grade->observations,
        ]);

        if (!empty($data['attendance'])) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $grade->subject_id,
                    'date' => now()->toDateString(),
                ],
                [
                    'status' => $data['attendance'],
                ]
            );
        }

        if (isset($data['exam_score'])) {
            $student->update(['exam_score' => $data['exam_score']]);
        }

        if (isset($data['activity_score'])) {
            $student->update(['score' => $data['activity_score']]);
        }

        if ($data['reason'] ?? null) {
            GradeHistory::create([
                'grade_id' => $grade->id,
                'user_id' => $user->id,
                'old_score' => $grade->getOriginal('score'),
                'new_score' => $data['score'],
                'reason' => $data['reason'],
            ]);
        }

        return redirect()->route('grades.show', $student->id)
            ->with('success', __('messages.grade_updated'));
    }

    public function batchUpdate(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $data = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'attendance' => 'array',
            'attendance.*' => 'nullable|string|in:Presente,Falta,Atraso',
            'activity_score' => 'array',
            'activity_score.*' => 'nullable|numeric|min:0|max:100',
            'exam_score' => 'array',
            'exam_score.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $studentIds = array_unique(array_merge(
            array_keys($data['attendance'] ?? []),
            array_keys($data['activity_score'] ?? []),
            array_keys($data['exam_score'] ?? [])
        ));

        foreach ($studentIds as $studentId) {
            $student = Student::find($studentId);
            if (! $student || ! $this->hasTeachingAssignment($user, $student, (int) $data['subject_id'])) {
                continue;
            }

            if (isset($data['attendance'][$studentId]) && $data['attendance'][$studentId]) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'subject_id' => $data['subject_id'],
                        'date' => now()->toDateString(),
                    ],
                    [
                        'status' => $data['attendance'][$studentId],
                    ]
                );
            }

            $studentUpdates = [];
            if (isset($data['activity_score'][$studentId])) {
                $studentUpdates['score'] = $data['activity_score'][$studentId];
            }
            if (isset($data['exam_score'][$studentId])) {
                $studentUpdates['exam_score'] = $data['exam_score'][$studentId];
            }

            if (! empty($studentUpdates)) {
                $student->update($studentUpdates);
            }
        }

        return back()->with('success', __('messages.grades_updated'));
    }

    public function save(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'type' => 'required|string|in:activity,exam,robot',
            'activity_number' => 'required|integer|min:1|max:3',
            'quarter' => 'required|integer|in:1,2,3',
            'score' => 'required|numeric|min:0|max:100',
            'subject_id' => 'required|integer|exists:subjects,id',
        ]);

        $student = Student::findOrFail($data['student_id']);
        if (! $this->hasTeachingAssignment($user, $student, (int) $data['subject_id'])) {
            abort(403, __('messages.unauthorized'));
        }

        $subjectId = (int) $data['subject_id'];

        $grade = Grade::where('student_id', $student->id)
            ->where('type', $data['type'])
            ->where('activity_number', $data['activity_number'])
            ->where('quarter', $data['quarter'])
            ->where('subject_id', $subjectId)
            ->first();

        if (! $grade) {
            $grade = new Grade();
            $grade->student_id = $student->id;
            $grade->subject_id = $subjectId;
            $grade->type = $data['type'];
            $grade->activity_number = $data['activity_number'];
            $grade->quarter = $data['quarter'];
            $grade->status = 'completed';
        }

        $oldScore = $grade->exists ? $grade->score : null;
        $grade->score = $data['score'];
        $grade->save();

        if ($grade->wasRecentlyCreated || (string) $oldScore === (string) $data['score']) {
            return response()->json(['success' => true, 'grade_id' => $grade->id]);
        }

        GradeHistory::create([
            'grade_id' => $grade->id,
            'user_id' => $user->id,
            'old_score' => $oldScore,
            'new_score' => $data['score'],
            'reason' => 'Actualización desde el cuaderno pedagógico',
        ]);

        return response()->json(['success' => true, 'grade_id' => $grade->id]);
    }

    public function updateScore(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'first_period' => ['nullable', 'required_without_all:second_period,exam', 'numeric', 'min:0', 'max:100'],
            'second_period' => ['nullable', 'required_without_all:first_period,exam', 'numeric', 'min:0', 'max:100'],
            'exam' => ['nullable', 'required_without_all:first_period,second_period', 'numeric', 'min:0', 'max:100'],
        ], [
            'student_id.required' => 'Student is required.',
            'student_id.exists' => 'The selected student does not exist.',
            'subject_id.required' => 'Subject is required.',
            'subject_id.exists' => 'The selected subject does not exist.',
            '*.numeric' => 'Scores must be numeric.',
            '*.min' => 'Scores cannot be lower than 0.',
            '*.max' => 'Scores cannot be higher than 100.',
        ]);

        $student = Student::findOrFail($data['student_id']);
        if (! $this->hasTeachingAssignment($user, $student, (int) $data['subject_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $updatedGrades = [];
        foreach (['first_period', 'second_period', 'exam'] as $scoreType) {
            if (! array_key_exists($scoreType, $data) || $data[$scoreType] === null) {
                continue;
            }

            $grade = Grade::firstOrNew([
                'student_id' => $data['student_id'],
                'subject_id' => $data['subject_id'],
                'type' => $scoreType,
            ]);
            $grade->score = $data[$scoreType];
            $grade->status = 'completed';
            $grade->save();

            $updatedGrades[$scoreType] = [
                'id' => $grade->id,
                'score' => (float) $grade->score,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Scores updated successfully.',
            'grades' => $updatedGrades,
        ]);
    }

    public function history(Grade $grade)
    {
        /** @var User $user */
        $user = Auth::user();
        $student = $grade->student;

        if (! $user || (! $user->isAdmin() && ! $user->isAcademic() && ! ($user->isProfesor()
            && TeachingAssignment::where('teacher_user_id', $user->id)->where('parallel_id', $student->parallel_id)->where('active', true)->exists()))) {
            abort(403, __('messages.unauthorized'));
        }

        $history = GradeHistory::where('grade_id', $grade->id)
            ->with('user')
            ->latest()
            ->paginate(10);

        return view('grades.history', compact('student', 'grade', 'history'));
    }

    public function createRobotActivity()
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $parallels = Parallel::with('course')->get();
        $subjects = Subject::where('active', true)->orderBy('sort_order')->orderBy('name')->get();
        $categories = [
            'Alimentos' => 'robot_alimentos',
            'Colores' => 'robot_colores',
            'Elementos de Casa' => 'robot_casa_items',
            'Elementos Naturales' => 'robot_naturaleza_items',
            'Familia' => 'robot_familia_items',
            'Herramientas' => 'robot_herramientas_items',
            'Lugares' => 'robot_lugares_items',
        ];

        return view('robot.create', compact('parallels', 'subjects', 'categories'));
    }

    public function storeRobotActivity(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $request->validate([
            'parallel_id' => 'required|exists:parallels,id',
            'subject_id' => 'required|exists:subjects,id',
            'category' => 'required|string|in:Alimentos,Colores,Elementos de Casa,Elementos Naturales,Familia,Herramientas,Lugares',
            'description' => 'nullable|string|max:500',
        ]);

        $parallel = Parallel::with('students')->findOrFail($request->parallel_id);
        if (! TeachingAssignment::where('teacher_user_id', $user->id)
            ->where('subject_id', $request->subject_id)
            ->where('parallel_id', $parallel->id)
            ->where('active', true)
            ->exists()) {
            abort(403, __('messages.unauthorized'));
        }
        $student = $parallel->students->first();

        if (! $student) {
            return back()->with('error', 'No se pudo crear la actividad porque el paralelo no tiene estudiantes registrados.');
        }

        Grade::create([
            'student_id' => $student->id,
            'subject_id' => $request->subject_id,
            'score' => 0,
            'type' => 'YURA',
            'observations' => $request->description,
            'status' => 'pending',
            'activity_category' => $request->category,
            'is_robot_activity' => true,
        ]);

        return redirect()->route('grades.index')->with('success', 'Actividad de YURA registrada en estado pendiente.');
    }

    public function saveAttendance(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'status' => 'required|string',
            'date' => 'required|date',
        ]);

        $student = Student::findOrFail($data['student_id']);
        if (! $this->hasTeachingAssignment($user, $student, (int) $data['subject_id'])) {
            abort(403, __('messages.unauthorized'));
        }

        try {
            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $data['subject_id'],
                    'date' => $data['date'],
                ],
                [
                    'status' => $data['status'],
                ]
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // Log the exception for debugging (optional)
            report($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function hasTeachingAssignment(User $user, Student $student, int $subjectId): bool
    {
        $assignmentQuery = TeachingAssignment::where('teacher_user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('active', true);

        if ((int) $student->teacher_user_id === (int) $user->id) {
            return $assignmentQuery->exists();
        }

        return $assignmentQuery->where('parallel_id', $student->parallel_id)->exists();
    }
}
