<?php

namespace App\Console\Commands;

use App\Services\AcademicYearService;

class YearsCreate extends YearCommand
{
    protected $signature = 'years:create {year : Año, por ejemplo 2027}';

    protected $description = 'Crea una gestión (no la pone como actual)';

    public function handle(AcademicYearService $years): int
    {
        return $this->attempt(function () use ($years) {
            $created = $years->create(null, (int) $this->argument('year'));
            $this->info("Creada: {$created->name} (no es la actual; usa years:set-current para cambiarla).");
        });
    }
}
