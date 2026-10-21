<?php

namespace App\Console\Commands;

use App\Services\AcademicYearService;

class YearsSetCurrent extends YearCommand
{
    protected $signature = 'years:set-current {year}';

    protected $description = 'Cambia la gestión actual: ahí se guardan las notas, asistencias y actividades nuevas';

    public function handle(AcademicYearService $years): int
    {
        $year = $this->findYear((int) $this->argument('year'));

        return $year ? $this->attempt(function () use ($years, $year) {
            $years->setCurrent(null, $year);
            $this->info("La gestión actual ahora es {$year->name}.");
        }) : self::FAILURE;
    }
}
