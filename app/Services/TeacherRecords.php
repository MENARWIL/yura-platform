<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\GradeHistory;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Lo que registra el profesor (asistencia y notas del cuaderno), con las mismas reglas para la web
 * y para la app móvil. Quién puede: el profesor con asignación docente activa de esa asignatura en
 * el paralelo del estudiante (o el profesor asignado directamente al estudiante).
 */
class TeacherRecords
{
    /** Puntos de cada dimensión del cuaderno SER/SABER/HACER; la autoevaluación vale 5. */
    public const DIMENSION_POINTS = ['ser' => 10, 'saber' => 45, 'hacer' => 40];

    /** Columnas del cuaderno: tipo guardado => [dimensión, cantidad de notas]. */
    public const NOTEBOOK = ['activity' => ['ser', 3], 'exam' => ['saber', 5], 'robot' => ['hacer', 5], 'autoeval' => ['autoeval', 1]];

    public static function attendanceRules(): array
    {
        return [
            'subject_id' => 'required|integer|exists:subjects,id',
            'date' => 'required|date|before_or_equal:today',
            'records' => 'required|array|min:1|max:300',
            'records.*.student_id' => 'required|integer|distinct|exists:students,id',
            'records.*.status' => ['required', 'string', Rule::in(Attendance::STATUSES)],
        ];
    }

    public static function gradeRules(): array
    {
        return [
            'student_id' => 'required|integer|exists:students,id',
            // activity = SER, exam = SABER, robot = HACER (lo llena el robot), autoeval = autoevaluación.
            'type' => 'required|string|in:activity,exam,robot,autoeval',
            'activity_number' => 'required|integer|min:1|max:5',
            'quarter' => 'required|integer|in:1,2,3',
            // Escala boliviana: notas enteras desde 1 (sin decimales, negativos ni cero).
            'score' => 'required|integer|min:1',
            'subject_id' => 'required|integer|exists:subjects,id',
        ];
    }

    public static function messages(): array
    {
        return [
            'date.before_or_equal' => 'No se puede registrar asistencia en una fecha futura.',
            'status.in' => 'Estado de asistencia no válido.',
            'records.*.status.in' => 'Estado de asistencia no válido.',
            'score.integer' => 'La nota debe ser un número entero, sin decimales.',
            'score.min' => 'La nota mínima es 1.',
        ];
    }

    public function canRecord(User $user, Student $student, int $subjectId): bool
    {
        // Un estudiante dado de baja ya no recibe notas ni asistencia (su historial se conserva).
        if (! $user->isProfesor() || $student->isWithdrawn()) {
            return false;
        }

        $assignments = TeachingAssignment::where('teacher_user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('active', true);

        if ((int) $student->teacher_user_id === (int) $user->id) {
            return $assignments->exists();
        }

        return $assignments->where('parallel_id', $student->parallel_id)->exists();
    }

    /**
     * Guarda (o corrige) la asistencia de varios estudiantes en una fecha. Todo o nada.
     *
     * @param  array<int, array{student_id:int, status:string}>  $records
     */
    public function saveAttendance(User $user, int $subjectId, string $date, array $records): int
    {
        $students = Student::whereIn('id', array_column($records, 'student_id'))->get();
        foreach ($students as $student) {
            if (! $this->canRecord($user, $student, $subjectId)) {
                throw new AuthorizationException(__('messages.unauthorized'));
            }
        }

        DB::transaction(function () use ($records, $subjectId, $date) {
            foreach ($records as $record) {
                Attendance::updateOrCreate(
                    ['student_id' => $record['student_id'], 'subject_id' => $subjectId, 'date' => $date],
                    ['status' => $record['status']]
                );
            }
        });

        return count($records);
    }

    /** Guarda una nota del cuaderno (y su historial si cambia). Los datos ya vienen validados con gradeRules(). */
    public function saveGrade(User $user, array $data): Grade
    {
        if ($data['type'] === 'robot') {
            throw ValidationException::withMessages([
                'type' => 'Las calificaciones HACER se registran al recibir los resultados de una actividad Yura.',
            ]);
        }
        $max = $data['type'] === 'autoeval' ? 5 : 100;
        if ($data['score'] > $max) {
            throw ValidationException::withMessages(['score' => "La nota no puede superar {$max} puntos para este campo."]);
        }

        $student = Student::findOrFail($data['student_id']);
        $subjectId = (int) $data['subject_id'];
        if (! $this->canRecord($user, $student, $subjectId)) {
            throw new AuthorizationException(__('messages.unauthorized'));
        }

        $grade = Grade::firstOrNew([
            'student_id' => $student->id,
            'subject_id' => $subjectId,
            'type' => $data['type'],
            'activity_number' => $data['activity_number'],
            'quarter' => $data['quarter'],
        ]);
        if (! $grade->exists) {
            $grade->status = 'completed';
        }

        $oldScore = $grade->exists ? $grade->score : null;
        $grade->score = $data['score'];
        $grade->save();

        if (! $grade->wasRecentlyCreated && (string) $oldScore !== (string) $data['score']) {
            GradeHistory::create([
                'grade_id' => $grade->id,
                'user_id' => $user->id,
                'old_score' => $oldScore,
                'new_score' => $data['score'],
                'reason' => 'Actualización desde el cuaderno pedagógico',
            ]);
        }

        return $grade;
    }

    /**
     * Puntos de cada dimensión y nota bimestral, igual que el cuaderno de la web: cada nota (0-100)
     * se promedia y se escala a los puntos de su dimensión; la autoevaluación se suma tal cual.
     *
     * @param  array<string, array<int, float|int|null>>  $scores  tipo => [n => nota]
     */
    public static function totals(array $scores): array
    {
        $scale = function (array $values, int $points): ?float {
            $numeric = array_values(array_filter($values, 'is_numeric'));

            return $numeric ? round(array_sum($numeric) / count($numeric) / 100 * $points, 2) : null;
        };

        $ser = $scale($scores['activity'] ?? [], self::DIMENSION_POINTS['ser']);
        $saber = $scale($scores['exam'] ?? [], self::DIMENSION_POINTS['saber']);
        $hacer = $scale($scores['robot'] ?? [], self::DIMENSION_POINTS['hacer']);
        $filled = array_filter([$ser, $saber, $hacer], fn ($v) => $v !== null);
        $total = $filled ? round(array_sum($filled), 2) : null;
        $autoeval = $scores['autoeval'][1] ?? null;
        $final = ($total !== null || is_numeric($autoeval)) ? round(($total ?? 0) + (is_numeric($autoeval) ? $autoeval : 0), 2) : null;

        return ['ser' => $ser, 'saber' => $saber, 'hacer' => $hacer, 'total' => $total, 'final' => $final];
    }
}
