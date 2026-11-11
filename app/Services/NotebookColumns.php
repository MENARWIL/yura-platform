<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\NotebookColumn;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\User;

/**
 * Casillas visibles del cuaderno pedagógico. Cada dimensión empieza con 2 casillas; el profesor de la
 * materia y el paralelo agrega (hasta el máximo) o descarta la última (mínimo 1, y solo si nadie tiene
 * nota en ella). Una casilla con nota siempre se ve, aunque la haya llenado el robot.
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

    /** El profesor puede cambiar las casillas solo de la materia y el paralelo que tiene asignados. */
    public function canEdit(?User $user, ?int $subjectId, ?int $parallelId): bool
    {
        return $user && $user->isProfesor() && $subjectId && $parallelId
            && TeachingAssignment::where('teacher_user_id', $user->id)
                ->where('subject_id', $subjectId)
                ->where('parallel_id', $parallelId)
                ->where('active', true)
                ->exists();
    }

    /**
     * Agrega (+1) o descarta (-1) una casilla. Devuelve la nueva cantidad visible.
     *
     * @throws UserAdminException si no se puede
     */
    public function change(User $user, int $subjectId, int $parallelId, int $quarter, string $dimension, int $delta): int
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
        if ($delta < 0 && $this->highestUsed($subjectId, $parallelId, $quarter, $dimension) >= $current) {
            throw new UserAdminException("La casilla {$label} {$current} tiene notas; bórralas antes de descartarla.");
        }

        NotebookColumn::updateOrCreate(
            ['subject_id' => $subjectId, 'parallel_id' => $parallelId, 'quarter' => $quarter, 'dimension' => $dimension,
                'academic_year_id' => \App\Models\AcademicYear::currentId()],
            ['count' => $next, 'user_id' => $user->id],
        );

        return $next;
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
