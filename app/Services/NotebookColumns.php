<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Grade;
use App\Models\NotebookColumn;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Casillas visibles del cuaderno pedagógico. Cada dimensión empieza con 2 casillas; el profesor de la
 * materia y el paralelo agrega (hasta el máximo) o descarta la última (mínimo 1; si tiene notas, se borran
 * tras confirmar). Una casilla con nota siempre se ve, aunque la haya llenado el robot.
 */
class NotebookColumns
{
    public const DEFAULT = 2;

    /** Máximo de casillas por dimensión (igual que CuadernoNota). */
    public const MAX = ['ser' => 3, 'saber' => 5, 'hacer' => 5];

    /** Tipo de nota guardado en grades para cada dimensión. */
    public const TYPES = ['ser' => 'activity', 'saber' => 'exam', 'hacer' => 'robot'];

    /**
     * @return array{ser:int, saber:int, hacer:int}
     */
    public function visible(?int $subjectId, ?int $parallelId, int $quarter): array
    {
        // Sin materia y paralelo elegidos se mezclan cursos: se muestran todas las casillas.
        if (! $subjectId || ! $parallelId) {
            return self::MAX;
        }

        $stored = NotebookColumn::where('subject_id', $subjectId)->where('parallel_id', $parallelId)
            ->where('quarter', $quarter)->pluck('count', 'dimension')->all();

        $counts = [];
        foreach (self::MAX as $dimension => $max) {
            $wanted = (int) ($stored[$dimension] ?? self::DEFAULT);
            $used = $this->highestUsed($subjectId, $parallelId, $quarter, $dimension);
            $counts[$dimension] = min($max, max(1, $wanted, $used));
        }

        return $counts;
    }

    /**
     * El profesor cambia las casillas de los paralelos que ve en su cuaderno: los de su asignación docente
     * de esa materia, o donde tiene estudiantes asignados directamente (si dicta esa materia).
     */
    public function canEdit(?User $user, ?int $subjectId, ?int $parallelId): bool
    {
        if (! $user || ! $user->isProfesor() || ! $subjectId || ! $parallelId) {
            return false;
        }

        $assignments = TeachingAssignment::where('teacher_user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('active', true);

        if ((clone $assignments)->where('parallel_id', $parallelId)->exists()) {
            return true;
        }

        return $assignments->exists()
            && Student::where('teacher_user_id', $user->id)->where('parallel_id', $parallelId)->exists();
    }

    /**
     * Agrega (+1) o descarta (-1) una casilla. Devuelve la nueva cantidad visible.
     *
     * Si la casilla que se descarta tiene notas, hace falta $confirmed: esas notas se borran y quedan
     * copiadas en la auditoría (notebook.column_discarded) para poder recuperarlas.
     *
     * @throws UserAdminException si no se puede
     * @throws NotebookColumnHasGrades si hay notas y falta confirmar
     */
    public function change(User $user, int $subjectId, int $parallelId, int $quarter, string $dimension, int $delta, bool $confirmed = false): int
    {
        if (! isset(self::MAX[$dimension]) || ! in_array($delta, [1, -1], true)) {
            throw new UserAdminException('Cambio de casillas no válido.');
        }
        if (! $this->canEdit($user, $subjectId, $parallelId)) {
            throw new UserAdminException('Solo el profesor de esta materia y paralelo puede cambiar las casillas.');
        }

        $current = $this->visible($subjectId, $parallelId, $quarter)[$dimension];
        $label = strtoupper($dimension);
        $next = $current + $delta;

        if ($next > self::MAX[$dimension]) {
            throw new UserAdminException("{$label} llega como máximo a " . self::MAX[$dimension] . ' casillas.');
        }
        if ($next < 1) {
            throw new UserAdminException("{$label} necesita al menos una casilla.");
        }
        return DB::transaction(function () use ($user, $subjectId, $parallelId, $quarter, $dimension, $delta, $current, $next, $label, $confirmed) {
            if ($delta < 0) {
                $grades = $this->columnGrades($subjectId, $parallelId, $quarter, $dimension, $current)->lockForUpdate()->get();
                if ($grades->isNotEmpty()) {
                    if (! $confirmed) {
                        throw new NotebookColumnHasGrades($label, $current, $grades->whereNotNull('score')->count());
                    }
                    AuditLog::log('notebook.column_discarded', 'grade', null, [
                        'subject_id' => $subjectId, 'parallel_id' => $parallelId, 'quarter' => $quarter,
                        'column' => "{$label} {$current}",
                        'grades' => $grades->map(fn (Grade $g) => [
                            'student_id' => $g->student_id, 'score' => $g->score, 'status' => $g->status,
                        ])->values()->all(),
                    ]);
                    // Uno por uno: así se respeta la protección de las gestiones cerradas.
                    $grades->each->delete();
                }
            }

            NotebookColumn::updateOrCreate(
                ['subject_id' => $subjectId, 'parallel_id' => $parallelId, 'quarter' => $quarter, 'dimension' => $dimension,
                    'academic_year_id' => AcademicYear::currentId()],
                ['count' => $next, 'user_id' => $user->id],
            );

            return $next;
        });
    }

    /** Notas de una casilla en el paralelo (cualquier estado). */
    private function columnGrades(int $subjectId, int $parallelId, int $quarter, string $dimension, int $column)
    {
        return Grade::where('subject_id', $subjectId)
            ->where('quarter', $quarter)
            ->where('type', self::TYPES[$dimension])
            ->where('activity_number', $column)
            ->whereIn('student_id', Student::where('parallel_id', $parallelId)->select('id'));
    }

    /** Número de la casilla más alta con nota en el paralelo (0 si ninguna). */
    private function highestUsed(?int $subjectId, ?int $parallelId, int $quarter, string $dimension): int
    {
        if (! $subjectId || ! $parallelId) {
            return 0;
        }

        return (int) Grade::where('subject_id', $subjectId)
            ->where('quarter', $quarter)
            ->where('type', self::TYPES[$dimension])
            ->whereNotNull('score')
            ->whereIn('student_id', Student::where('parallel_id', $parallelId)->select('id'))
            ->max('activity_number');
    }
}
