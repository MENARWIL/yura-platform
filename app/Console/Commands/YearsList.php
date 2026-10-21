<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class YearsList extends YearCommand
{
    protected $signature = 'years:list';

    protected $description = 'Lista las gestiones con su estado y cuántos datos tiene cada una';

    public function handle(): int
    {
        $rows = AcademicYear::orderByDesc('year')->get()->map(fn (AcademicYear $y) => [
            $y->year,
            $y->is_current ? 'ACTUAL' : '',
            $y->isClosed() ? 'cerrada ' . $y->closed_at->format('d/m/Y') : 'abierta',
            DB::table('enrollments')->where('academic_year_id', $y->id)->count(),
            DB::table('grades')->where('academic_year_id', $y->id)->count(),
            DB::table('attendance')->where('academic_year_id', $y->id)->count(),
        ]);
        $this->table(['Gestión', 'Actual', 'Estado', 'Matrículas', 'Notas', 'Asistencias'], $rows);

        return self::SUCCESS;
    }
}
