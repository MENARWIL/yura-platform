<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Hoja «Matrículas»: quién estuvo en qué curso y paralelo, numerado dentro de cada paralelo. */
class YearEnrollmentsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly AcademicYear $year)
    {
    }

    public function title(): string
    {
        return 'Matrículas';
    }

    public function headings(): array
    {
        return ['Curso', 'Paralelo', 'Nro', 'Estudiante', 'Estado'];
    }

    public function collection(): Collection
    {
        $rows = Enrollment::where('academic_year_id', $this->year->id)
            ->with(['course', 'parallel', 'student' => fn ($q) => $q->withTrashed()])
            ->get()
            ->sortBy(fn (Enrollment $e) => [$e->course?->name ?? '~', $e->parallel?->name ?? '~', AcademicYearExport::sortKey($e->student)])
            ->groupBy(fn (Enrollment $e) => ($e->course?->name ?? 'Sin curso') . '|' . ($e->parallel?->name ?? 'Sin paralelo'));

        return $rows->flatMap(fn (Collection $group) => $group->values()->map(fn (Enrollment $e, int $i) => [
            $e->course?->name ?? 'Sin curso',
            $e->parallel?->name ?? 'Sin paralelo',
            $i + 1,
            $e->student?->name ?? 'Estudiante #' . $e->student_id,
            $e->status ?? '',
        ]))->values();
    }
}
