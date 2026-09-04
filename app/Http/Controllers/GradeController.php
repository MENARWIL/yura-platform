<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\GradeHistory;
use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $courses = Course::all();
        $parallels = Parallel::all();
        $selectedCourseId = $request->query('course_id');
        $selectedQuarter = $request->input('quarter', 1);

        $query = Student::with(['parent', 'teacher', 'parallel']);

        if ($user->isProfesor()) {
            $teacherForeignKey = (new Student())->getTeacherUserForeignKey();
            $query->where($teacherForeignKey, $user->id);
        }

        if ($selectedCourseId) {
            $query->where('course_id', $selectedCourseId);
        }

        $students = $query->get();
        $studentIds = $students->pluck('id')->all();
        $today = now()->toDateString();

        $todayAttendance = Attendance::whereIn('student_id', $studentIds)
            ->when($selectedCourseId, function ($query) use ($selectedCourseId) {
                return $query->where('subject_id', $selectedCourseId);
            })
            ->whereDate('date', $today)
            ->get()
            ->keyBy('student_id');

        $gradesByStudent = Grade::whereIn('student_id', $studentIds)
            ->where('status', 'completed')
            ->where('quarter', $selectedQuarter)
            ->when($selectedCourseId, function ($query) use ($selectedCourseId) {
                return $query->where('subject_id', $selectedCourseId);
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

        return view('grades.index', compact('students', 'courses', 'parallels', 'selectedCourseId', 'selectedQuarter', 'attendanceStatuses', 'robotCategories', 'todayAttendance', 'gradesByStudent'));
    }

    public function show(Student $student)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAcademic() && !($user->isProfesor() && $student->getAttribute($student->getTeacherUserForeignKey()) == $user->id)) {
            abort(403, __('messages.unauthorized'));
        }

        $grades = Grade::where('student_id', $student->id)->with('subject')->get();
        $history = GradeHistory::whereHas('grade', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })->with(['grade', 'user'])->latest()->paginate(15);

        return view('grades.show', compact('student', 'grades', 'history'));
    }

    public function edit(Grade $grade)
    {
        /** @var User $user */
        $user = Auth::user();
        $student = $grade->student;

        if (!$user->isAdmin() && !($user->isProfesor() && $student->getAttribute($student->getTeacherUserForeignKey()) == $user->id)) {
            abort(403, __('messages.unauthorized'));
        }

        return view('grades.edit', compact('grade', 'student'));
    }

    public function update(Request $request, Grade $grade)
    {
        /** @var User $user */
        $user = Auth::user();
        $student = $grade->student;

        if (!$user->isAdmin() && !($user->isProfesor() && $student->getAttribute($student->getTeacherUserForeignKey()) == $user->id)) {
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

        if (!$user->isAdmin() && !$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $data = $request->validate([
            'course_id' => 'nullable|integer|exists:courses,id',
            'attendance' => 'array',
            'attendance.*' => 'nullable|string|in:Presente,Falta,Atraso',
            'activity_score' => 'array',
            'activity_score.*' => 'nullable|numeric|min:0|max:100',
            'exam_score' => 'array',
            'exam_score.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $teacherForeignKey = (new Student())->getTeacherUserForeignKey();
        $attendanceCourseId = $data['course_id'] ?? null;

        $studentIds = array_unique(array_merge(
            array_keys($data['attendance'] ?? []),
            array_keys($data['activity_score'] ?? []),
            array_keys($data['exam_score'] ?? [])
        ));

        foreach ($studentIds as $studentId) {
            $student = Student::where($teacherForeignKey, $user->id)->find($studentId);
            if (! $student) {
                continue;
            }

            if (isset($data['attendance'][$studentId]) && $data['attendance'][$studentId]) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'subject_id' => $attendanceCourseId ?? $student->course_id,
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

        if (!$user->isAdmin() && !$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'type' => 'required|string|in:activity,exam,robot',
            'activity_number' => 'required|integer|min:1|max:3',
            'quarter' => 'required|integer|in:1,2,3',
            'score' => 'required|numeric|min:0|max:100',
            'subject_id' => 'nullable|integer|exists:courses,id',
        ]);

        $student = Student::findOrFail($data['student_id']);
        $teacherForeignKey = (new Student())->getTeacherUserForeignKey();
        if ($user->isProfesor() && $student->getAttribute($teacherForeignKey) != $user->id) {
            abort(403, __('messages.unauthorized'));
        }

        $subjectId = $data['subject_id'] ?? $student->course_id;

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

        $grade->score = $data['score'];
        $grade->save();

        return response()->json(['success' => true, 'grade_id' => $grade->id]);
    }

    public function updateScore(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isAdmin() && ! $user->isProfesor()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'subject_id' => ['required', 'integer', 'exists:courses,id'],
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
        if ($user->isProfesor() && $student->getAttribute($student->getTeacherUserForeignKey()) != $user->id) {
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
            && $student->getAttribute($student->getTeacherUserForeignKey()) == $user->id))) {
            abort(403, __('messages.unauthorized'));
        }

        $history = GradeHistory::where('grade_id', $grade->id)
            ->with('user')
            ->latest()
            ->paginate(10);

        return view('grades.history', compact('grade', 'history'));
    }

    public function createRobotActivity()
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $parallels = Parallel::with('course')->get();
        $categories = [
            'Alimentos' => 'robot_alimentos',
            'Colores' => 'robot_colores',
            'Elementos de Casa' => 'robot_casa_items',
            'Elementos Naturales' => 'robot_naturaleza_items',
            'Familia' => 'robot_familia_items',
            'Herramientas' => 'robot_herramientas_items',
            'Lugares' => 'robot_lugares_items',
        ];

        return view('robot.create', compact('parallels', 'categories'));
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
            'course_id' => 'required|exists:courses,id',
            'category' => 'required|string|in:Alimentos,Colores,Elementos de Casa,Elementos Naturales,Familia,Herramientas,Lugares',
            'description' => 'nullable|string|max:500',
        ]);

        $parallel = Parallel::with('students')->findOrFail($request->parallel_id);
        $student = $parallel->students->first();

        if (! $student) {
            return back()->with('error', 'No se pudo crear la actividad porque el paralelo no tiene estudiantes registrados.');
        }

        Grade::create([
            'student_id' => $student->id,
            'subject_id' => $request->course_id,
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

        if (! $user->isAdmin() && ! $user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'course_id' => 'nullable|integer|exists:courses,id',
            'status' => 'required|string',
            'date' => 'required|date',
        ]);

        $student = Student::findOrFail($data['student_id']);
        $teacherForeignKey = (new Student())->getTeacherUserForeignKey();
        if ($user->isProfesor() && $student->getAttribute($teacherForeignKey) != $user->id) {
            abort(403, __('messages.unauthorized'));
        }

        try {
            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $data['course_id'] ?? null,
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
}
