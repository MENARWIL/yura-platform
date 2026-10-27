<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Respaldo de una gestión escolar en un solo Excel: matrículas, notas y asistencia.
 * Solo contiene los datos de esa gestión; no mezcla otras. Una gestión cerrada se puede
 * descargar sin reabrirla (la lectura nunca modifica nada).
 */
class AcademicYearExport implements WithMultipleSheets
{
    public function __construct(private readonly AcademicYear $year)
    {
    }

    public function sheets(): array
    {
        return [
            new YearEnrollmentsSheet($this->year),
            new YearGradesSheet($this->year),
            new YearAttendanceSheet($this->year),
        ];
    }

    /**
     * Curso y paralelo que tenía cada estudiante en esa gestión: [student_id => [curso, paralelo]].
     * Se toman de la matrícula de la gestión, no del curso actual del estudiante.
     */
    public static function placesByStudent(AcademicYear $year): array
    {
        return Enrollment::where('academic_year_id', $year->id)
            ->with(['course', 'parallel'])
            ->get()
            ->mapWithKeys(fn (Enrollment $e) => [$e->student_id => [$e->course?->name ?? 'Sin curso', $e->parallel?->name ?? 'Sin paralelo']])
            ->all();
    }

    /** Clave para ordenar como una lista de curso: apellidos y nombres (o el nombre completo si no están separados). */
    public static function sortKey(?Student $student): string
    {
        if (! $student) {
            return '~';
        }
        $split = trim(($student->last_name ?? '') . ' ' . ($student->second_last_name ?? '') . ' ' . ($student->first_name ?? ''));

        return mb_strtolower($split !== '' ? $split : (string) $student->name);
    }
}
