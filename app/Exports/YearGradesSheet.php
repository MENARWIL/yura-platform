<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\Grade;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Hoja «Notas»: cada nota guardada en la gestión, con el curso y paralelo que tenía el estudiante entonces. */
class YearGradesSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly AcademicYear $year)
    {
    }

    public function title(): string
    {
        return 'Notas';
    }

    public function headings(): array
    {
        return ['Curso', 'Paralelo', 'Estudiante', 'Asignatura', 'Trimestre', 'Tipo', 'N.º actividad', 'Nota', 'Estado', 'Observaciones'];
    }

    public function collection(): Collection
    {
        $places = AcademicYearExport::placesByStudent($this->year);

        return Grade::withoutGlobalScope('academic_year')
            ->where('academic_year_id', $this->year->id)
            ->with(['subject', 'student' => fn ($q) => $q->withTrashed()])
            ->get()
            ->map(function (Grade $g) use ($places) {
                [$course, $parallel] = $places[$g->student_id] ?? ['Sin matrícula', ''];

                return [
                    'sort' => [$course, $parallel, AcademicYearExport::sortKey($g->student), (string) $g->subject?->name, (int) $g->quarter, (string) $g->type, (int) $g->activity_number],
                    'row' => [
                        $course, $parallel, $g->student?->name ?? 'Estudiante #' . $g->student_id, $g->subject?->name ?? '',
                        $g->quarter, $g->type, $g->activity_number, $g->score, $g->status, $g->observations,
                    ],
                ];
            })
            ->sortBy('sort')
            ->pluck('row')
            ->values();
    }
}
