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
use App\Exports\TrimesterGradesExport;
use App\Http\Controllers\Concerns\PaginatesLists;
use App\Models\AcademicYear;
use App\Services\TeacherRecords;
use App\Services\TrimesterReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class GradeController extends Controller
{
    use PaginatesLists;

    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAcademic() && !$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        // El director y la Secretaría no usan el cuaderno: ven solo las notas finales de cada trimestre.
        if ($user->seesTrimesterGradesOnly()) {
            return redirect()->route('grades.trimesters', $request->only(['subject_id', 'parallel_id']));
        }

        $subjects = Subject::where('active', true)->orderBy('sort_order')->orderBy('name')->get();
        $parallels = Parallel::with('course')->orderBy('course_id')->orderBy('name')->get();
        $selectedSubjectId = $request->integer('subject_id');
        $selectedParallelId = $request->integer('parallel_id');
        $selectedQuarter = $request->input('quarter', 1);

        $query = Student::with(['parent', 'teacher', 'parallel'])->active();

        if ($user->isProfesor()) {
            $query->taughtBy($user, $selectedSubjectId ?: null)
                ->when($selectedParallelId, function ($query) use ($selectedParallelId) {
                    return $query->where('parallel_id', $selectedParallelId);
                });

            $assignedSubjectIds = TeachingAssignment::where('teacher_user_id', $user->id)
                ->where('active', true)
                ->distinct()
                ->pluck('subject_id');
            $teacherParallelIds = Student::where('teacher_user_id', $user->id)
                ->whereNotNull('parallel_id')
                ->distinct()
                ->pluck('parallel_id')
                ->merge(Student::assignedParallelIds($user));

            $subjects = $subjects->whereIn('id', $assignedSubjectIds)->values();
            $parallels = $parallels->whereIn('id', $teacherParallelIds)->values();
        } elseif ($selectedParallelId) {
            $query->where('parallel_id', $selectedParallelId);
        }

        $students = $query->orderedForList()->paginate($this->perPage($request, 20))->withQueryString();
        $studentIds = $students->pluck('id')->all();

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

        return view('grades.index', compact('students', 'subjects', 'parallels', 'selectedSubjectId', 'selectedParallelId', 'selectedQuarter', 'gradesByStudent'));
    }

    /**
     * Notas trimestrales (solo lectura, director y Panel Administrativo): la nota final sobre 100 de cada trimestre
     * por estudiante, con el mismo cálculo del Cuaderno Pedagógico, y el promedio anual.
     */
    public function trimesters(Request $request)
    {
        $subjects = Subject::where('active', true)->orderBy('sort_order')->orderBy('name')->get();
        $parallels = Parallel::with('course')->orderBy('course_id')->orderBy('name')->get();
        $selectedSubjectId = $request->integer('subject_id');
        $selectedParallelId = $request->integer('parallel_id');

        $rows = ($selectedSubjectId && $selectedParallelId)
            ? TrimesterReport::forParallel($selectedSubjectId, $selectedParallelId)
            : collect();

        return view('grades.trimesters', compact('subjects', 'parallels', 'selectedSubjectId', 'selectedParallelId', 'rows'));
    }

    /** Planilla de notas trimestrales de un paralelo en PDF (la misma tabla de la página). */
    public function trimestersPdf(Request $request)
    {
        [$subject, $parallel, $rows] = $this->trimesterSheet($request);

        return Pdf::loadView('reports.trimesters_pdf', [
            'subject' => $subject,
            'parallel' => $parallel,
            'rows' => $rows,
            'year' => AcademicYear::current(),
            'orgName' => config('yura.org_name'),
        ])->setPaper('A4', 'portrait')->download($this->trimesterFileName($subject, $parallel) . '.pdf');
    }

    /** Planilla de notas trimestrales de un paralelo en Excel. */
    public function trimestersExcel(Request $request)
    {
        [$subject, $parallel, $rows] = $this->trimesterSheet($request);

        return Excel::download(
            new TrimesterGradesExport($subject, $parallel, $rows, AcademicYear::current()),
            $this->trimesterFileName($subject, $parallel) . '.xlsx'
        );
    }

    /** @return array{0: Subject, 1: Parallel, 2: \Illuminate\Support\Collection} */
    private function trimesterSheet(Request $request): array
    {
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'parallel_id' => ['required', 'integer', 'exists:parallels,id'],
        ]);
        $subject = Subject::findOrFail($data['subject_id']);
        $parallel = Parallel::with('course')->findOrFail($data['parallel_id']);

        return [$subject, $parallel, TrimesterReport::forParallel($subject->id, $parallel->id)];
    }

    private function trimesterFileName(Subject $subject, Parallel $parallel): string
    {
        return 'notas_trimestrales_' . Str::slug($subject->name . ' ' . ($parallel->course?->name ?? '') . ' ' . $parallel->name, '_');
    }

    public function exportPdf(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAcademic() && !$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        // El PDF es el cuaderno completo: el director y la Secretaría no lo exportan.
        if ($user->seesTrimesterGradesOnly()) {
            return redirect()->route('grades.trimesters', $request->only(['subject_id', 'parallel_id']));
        }

        $selectedSubjectId = $request->integer('subject_id');
        $selectedParallelId = $request->integer('parallel_id');
        $selectedQuarter = $request->input('quarter', 1);

        $subject = $selectedSubjectId ? Subject::find($selectedSubjectId) : null;
        $parallel = $selectedParallelId ? Parallel::with('course')->find($selectedParallelId) : null;

        $query = Student::with(['parent', 'teacher', 'parallel'])->active();

        if ($user->isProfesor()) {
            $query->taughtBy($user, $selectedSubjectId ?: null)
                ->when($selectedParallelId, function ($query) use ($selectedParallelId) {
                    return $query->where('parallel_id', $selectedParallelId);
                });
        } elseif ($selectedParallelId) {
            $query->where('parallel_id', $selectedParallelId);
        }

        $students = $query->orderedForList()->get();
        $studentIds = $students->pluck('id')->all();

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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.grades_pdf', compact(
            'students',
            'gradesByStudent',
            'subject',
            'parallel',
            'selectedQuarter'
        ));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('cuaderno_pedagogico_' . date('Ymd_His') . '.pdf');
    }

    public function show(Student $student)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAcademic() && !($user->isProfesor()
            && (TeachingAssignment::where('teacher_user_id', $user->id)
                ->where('parallel_id', $student->parallel_id)
                ->where('active', true)
                ->exists() || (int) $student->teacher_user_id === (int) $user->id))
            && !($user->isResponsibleRole() && $user->isFamilyOf($student))) {
            abort(403, __('messages.unauthorized'));
        }

        $grades = Grade::where('student_id', $student->id)->with('subject')->get();
        $history = GradeHistory::whereHas('grade', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })->with(['grade', 'user'])->latest()->paginate(15);

        // Solo quien puede asignar responsables necesita la lista (ventana "Editar información").
        $canManageGuardian = $user->isAdmin() || $user->isAcademic();
        $guardianCandidates = $canManageGuardian
            ? User::whereIn('role', ['padre', 'madre', 'tutor'])->whereIn('status', ['active', 'activo'])->orderBy('name')->get(['id', 'name', 'ci', 'role'])
            : collect();
        $guardians = $canManageGuardian
            ? $student->familiares()->wherePivot('active', true)->orderByDesc('student_family_members.is_primary')->get()
            : collect();

        return view('grades.show', compact('student', 'grades', 'history', 'canManageGuardian', 'guardianCandidates', 'guardians'));
    }

    public function historyForStudent(Student $student)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAcademic() && !($user->isProfesor()
            && (TeachingAssignment::where('teacher_user_id', $user->id)
                ->where('parallel_id', $student->parallel_id)
                ->where('active', true)
                ->exists() || (int) $student->teacher_user_id === (int) $user->id))
            && !($user->isResponsibleRole() && $user->isFamilyOf($student))) {
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
            'score' => 'required|integer|min:1|max:100',
            'type' => 'nullable|string|max:50',
            'observations' => 'nullable|string|max:1000',
            'reason' => 'nullable|string|max:500',
            'attendance' => 'nullable|string|in:Presente,Falta,Atraso,Licencia',
            'exam_score' => 'nullable|integer|min:1|max:100',
            'activity_score' => 'nullable|integer|min:1|max:100',
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
            'attendance.*' => 'nullable|string|in:Presente,Falta,Atraso,Licencia',
            'activity_score' => 'array',
            'activity_score.*' => 'nullable|integer|min:1|max:100',
            'exam_score' => 'array',
            'exam_score.*' => 'nullable|integer|min:1|max:100',
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

    public function save(Request $request, TeacherRecords $records)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        // Mismas reglas que la app móvil (App\Services\TeacherRecords).
        $data = $request->validate(TeacherRecords::gradeRules(), TeacherRecords::messages());

        try {
            $grade = $records->saveGrade($user, $data);
        } catch (AuthorizationException) {
            abort(403, __('messages.unauthorized'));
        } catch (ValidationException $e) {
            // La pantalla del cuaderno espera { success: false, message } (nota del robot o fuera de rango).
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

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
            'first_period' => ['nullable', 'required_without_all:second_period,exam', 'integer', 'min:1', 'max:100'],
            'second_period' => ['nullable', 'required_without_all:first_period,exam', 'integer', 'min:1', 'max:100'],
            'exam' => ['nullable', 'required_without_all:first_period,second_period', 'integer', 'min:1', 'max:100'],
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
