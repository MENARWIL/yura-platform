<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\Parallel;
use App\Models\Subject;
use App\Services\TrimesterReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Planilla de notas trimestrales de un paralelo (la tabla de la página «Notas trimestrales»). */
class TrimesterGradesExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        private Subject $subject,
        private Parallel $parallel,
        private Collection $rows,
        private ?AcademicYear $year,
    ) {
    }

    public function headings(): array
    {
        return [
            ['Notas trimestrales — ' . $this->subject->name . ' — ' . trim(($this->parallel->course?->name ?? '') . ' ' . $this->parallel->name)
                . ($this->year ? ' — ' . $this->year->name : '')],
            ['Nro', 'Estudiante', '1.er trimestre', '2.º trimestre', '3.er trimestre', 'Promedio anual'],
        ];
    }

    public function array(): array
    {
        $data = $this->rows->values()->map(fn ($row, $i) => [
            $i + 1,
            $row['student']->name,
            $row['quarters'][1],
            $row['quarters'][2],
            $row['quarters'][3],
            $row['annual'],
        ])->all();

        $data[] = [
            '', 'Promedio del paralelo',
            TrimesterReport::columnAverage($this->rows, 1),
            TrimesterReport::columnAverage($this->rows, 2),
            TrimesterReport::columnAverage($this->rows, 3),
            TrimesterReport::columnAverage($this->rows),
        ];

        return $data;
    }

    public function title(): string
    {
        return 'Notas trimestrales';
    }
}
