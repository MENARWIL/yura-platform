<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Services\TeacherRecords;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * App móvil del profesor: asistencia y cuaderno de notas de sus asignaciones docentes.
 * Las reglas son las mismas de la web (App\Services\TeacherRecords). Trabaja siempre en la
 * gestión actual: una gestión cerrada no acepta cambios (409).
 */
class AppTeacherController extends Controller
{
    public function __construct(private TeacherRecords $records)
    {
    }

    /** Asignaturas y paralelos que el profesor atiende (para elegir dónde trabajar). */
    public function assignments(Request $request): JsonResponse
    {
        $items = TeachingAssignment::with(['subject', 'parallel.course'])
            ->where('teacher_user_id', $request->user()->id)
            ->where('active', true)
            ->get()
            ->filter(fn ($a) => $a->subject && $a->parallel)
            ->sortBy(fn ($a) => [$a->parallel->course?->name, $a->parallel->name, $a->subject->name])
            ->map(fn ($a) => [
                'subject_id' => $a->subject_id,
                'subject' => $a->subject->name,
                'parallel_id' => $a->parallel_id,
                'parallel' => $a->parallel->name,
                'course' => $a->parallel->course?->name,
                'students' => Student::where('parallel_id', $a->parallel_id)->count(),
            ])
            ->values();

        return response()->json(['data' => $items]);
    }

    /** Lista del día: cada estudiante con su estado (null = sin registrar). */
    public function attendance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'parallel_id' => 'required|integer|exists:parallels,id',
            'date' => 'nullable|date|before_or_equal:today',
        ], TeacherRecords::messages());
        $this->ensureAssignment($request, (int) $data['subject_id'], (int) $data['parallel_id']);
        $date = Carbon::parse($data['date'] ?? now())->toDateString();

        $students = $this->students((int) $data['parallel_id']);
        $saved = Attendance::whereIn('student_id', $students->pluck('id'))
            ->where('subject_id', $data['subject_id'])
            ->whereDate('date', $date)
            ->pluck('status', 'student_id');

        return response()->json(['data' => [
            'date' => $date,
            'statuses' => Attendance::STATUSES,
            'students' => $students->values()->map(fn (Student $s, int $i) => [
                'id' => $s->id,
                'number' => $i + 1,
                'name' => $s->name,
                'status' => $saved[$s->id] ?? null,
            ]),
        ]]);
    }

    /** Guarda la lista (o una sola fila). Todo o nada. */
    public function saveAttendance(Request $request): JsonResponse
    {
        $data = $request->validate(TeacherRecords::attendanceRules(), TeacherRecords::messages());

        try {
            $saved = $this->records->saveAttendance($request->user(), (int) $data['subject_id'], $data['date'], $data['records']);
        } catch (AuthorizationException) {
            return response()->json(['message' => 'No tienes asignación docente para esos estudiantes.'], 403);
        }

        return response()->json(['data' => ['saved' => $saved]]);
    }

    /** Cuaderno de un trimestre: notas SER, SABER, HACER y autoevaluación con los puntos calculados. */
    public function grades(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'parallel_id' => 'required|integer|exists:parallels,id',
            'quarter' => 'nullable|integer|in:1,2,3',
        ]);
        $this->ensureAssignment($request, (int) $data['subject_id'], (int) $data['parallel_id']);
        $quarter = (int) ($data['quarter'] ?? 1);

        $students = $this->students((int) $data['parallel_id']);
        $grades = Grade::whereIn('student_id', $students->pluck('id'))
            ->where('subject_id', $data['subject_id'])
            ->where('quarter', $quarter)
            ->where('status', 'completed')
            ->get()
            ->groupBy('student_id');

        return response()->json(['data' => [
            'quarter' => $quarter,
            'columns' => collect(TeacherRecords::NOTEBOOK)->map(fn ($c, $type) => [
                'type' => $type, 'dimension' => $c[0], 'count' => $c[1],
                'max' => $type === 'autoeval' ? 5 : 100, 'editable' => $type !== 'robot',
            ])->values(),
            'students' => $students->values()->map(function (Student $s, int $i) use ($grades) {
                $scores = [];
                foreach (TeacherRecords::NOTEBOOK as $type => [, $count]) {
                    foreach (range(1, $count) as $n) {
                        $score = $grades->get($s->id)?->first(fn ($g) => $g->type === $type && (int) $g->activity_number === $n)?->score;
                        $scores[$type][$n] = $score === null ? null : (float) $score;
                    }
                }

                return ['id' => $s->id, 'number' => $i + 1, 'name' => $s->name, 'scores' => $scores, 'totals' => TeacherRecords::totals($scores)];
            }),
        ]]);
    }

    /** Guarda una nota y devuelve los puntos recalculados del estudiante. */
    public function saveGrade(Request $request): JsonResponse
    {
        $data = $request->validate(TeacherRecords::gradeRules(), TeacherRecords::messages());

        try {
            $grade = $this->records->saveGrade($request->user(), $data);
        } catch (AuthorizationException) {
            return response()->json(['message' => 'No tienes asignación docente para este estudiante.'], 403);
        }

        return response()->json(['data' => ['id' => $grade->id, 'score' => (float) $grade->score]]);
    }

    private function ensureAssignment(Request $request, int $subjectId, int $parallelId): void
    {
        $ok = TeachingAssignment::where('teacher_user_id', $request->user()->id)
            ->where('subject_id', $subjectId)->where('parallel_id', $parallelId)->where('active', true)->exists();
        abort_unless($ok, 403, 'No tienes asignación docente en esa asignatura y paralelo.');
    }

    private function students(int $parallelId)
    {
        return Student::where('parallel_id', $parallelId)->active()->orderedForList()->get(['id', 'last_name', 'second_last_name', 'first_name', 'parallel_id']);
    }
}
