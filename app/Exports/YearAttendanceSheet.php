<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\Attendance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Hoja «Asistencia»: cada registro de asistencia de la gestión, del más antiguo al más reciente. */
class YearAttendanceSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly AcademicYear $year)
    {
    }

    public function title(): string
    {
        return 'Asistencia';
    }

    public function headings(): array
    {
        return ['Fecha', 'Curso', 'Paralelo', 'Estudiante', 'Asignatura', 'Estado'];
    }

    public function collection(): Collection
    {
        $places = AcademicYearExport::placesByStudent($this->year);

        return Attendance::withoutGlobalScope('academic_year')
            ->where('academic_year_id', $this->year->id)
            ->with(['subject', 'student' => fn ($q) => $q->withTrashed()])
            ->get()
            ->map(function (Attendance $a) use ($places) {
                [$course, $parallel] = $places[$a->student_id] ?? ['Sin matrícula', ''];

                return [
                    'sort' => [$a->date?->toDateString() ?? '', $course, $parallel, AcademicYearExport::sortKey($a->student)],
                    'row' => [
                        $a->date?->format('d/m/Y') ?? '', $course, $parallel,
                        $a->student?->name ?? 'Estudiante #' . $a->student_id, $a->subject?->name ?? '', $a->status,
                    ],
                ];
            })
            ->sortBy('sort')
            ->pluck('row')
            ->values();
    }
}
