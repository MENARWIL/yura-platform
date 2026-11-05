<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * Notas finales por trimestre (sobre 100) con el cálculo del Cuaderno Pedagógico (CuadernoNota).
 * La usan la página «Notas trimestrales», su planilla en PDF y Excel, y el boletín de cada estudiante.
 * Las notas son las de la gestión actual (ver BelongsToAcademicYear).
 */
class TrimesterReport
{
    public const QUARTERS = [1, 2, 3];

    /**
     * Planilla de un paralelo en una asignatura: una fila por estudiante activo.
     *
     * @return Collection<int, array{student: Student, quarters: array<int, ?float>, annual: ?float}>
     */
    public static function forParallel(int $subjectId, int $parallelId): Collection
    {
        $students = Student::active()->where('parallel_id', $parallelId)->orderedForList()->get();

        $grades = Grade::whereIn('student_id', $students->pluck('id'))
            ->where('status', 'completed')
            ->where('subject_id', $subjectId)
            ->whereIn('quarter', self::QUARTERS)
            ->get()
            ->groupBy('student_id');

        return $students->map(function (Student $student) use ($grades) {
            $notes = self::quarterNotes($grades[$student->id] ?? collect());

            return ['student' => $student, 'quarters' => $notes, 'annual' => CuadernoNota::annual($notes)];
        });
    }

    /**
     * Boletín de un estudiante: una fila por asignatura activa.
     *
     * @return Collection<int, array{subject: Subject, quarters: array<int, ?float>, annual: ?float}>
     */
    public static function forStudent(Student $student): Collection
    {
        $grades = Grade::where('student_id', $student->id)
            ->where('status', 'completed')
            ->whereIn('quarter', self::QUARTERS)
            ->get()
            ->groupBy('subject_id');

        return Subject::where('active', true)->orderBy('sort_order')->orderBy('name')->get()
            ->map(function (Subject $subject) use ($grades) {
                $notes = self::quarterNotes($grades[$subject->id] ?? collect());

                return ['subject' => $subject, 'quarters' => $notes, 'annual' => CuadernoNota::annual($notes)];
            });
    }

    /** Promedio de una columna (trimestre o anual) entre las filas que tienen nota. */
    public static function columnAverage(Collection $rows, ?int $quarter = null): ?float
    {
        $values = $rows->map(fn ($row) => $quarter === null ? $row['annual'] : $row['quarters'][$quarter])
            ->filter(fn ($v) => $v !== null);

        return $values->isEmpty() ? null : round($values->avg(), 2);
    }

    /** Nota de cada trimestre a partir de las notas de un estudiante en una asignatura. */
    private static function quarterNotes(Collection $grades): array
    {
        $byQuarter = $grades->groupBy('quarter');
        $notes = [];
        foreach (self::QUARTERS as $quarter) {
            $studentGrades = ($byQuarter[$quarter] ?? collect())
                ->groupBy('type')
                ->map(fn ($typeGrades) => $typeGrades->keyBy('activity_number'));
            $notes[$quarter] = CuadernoNota::summarize($studentGrades->isEmpty() ? null : $studentGrades)['nota'];
        }

        return $notes;
    }
}
