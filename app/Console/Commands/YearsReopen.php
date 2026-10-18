<?php

namespace App\Console\Commands;

use App\Services\AcademicYearService;

class YearsReopen extends YearCommand
{
    protected $signature = 'years:reopen {year} {--yes : Confirma la reapertura}';

    protected $description = 'Reabre una gestión cerrada (acción excepcional, queda en la auditoría)';

    public function handle(AcademicYearService $years): int
    {
        $year = $this->findYear((int) $this->argument('year'));
        if (! $year) {
            return self::FAILURE;
        }
        if (! $this->option('yes')) {
            $this->error("Reabrir {$year->name} vuelve a permitir cambios en datos de un año cerrado. Repite con --yes para confirmar.");

            return self::FAILURE;
        }

        return $this->attempt(function () use ($years, $year) {
            $years->reopen(null, $year);
            $this->info("{$year->name} reabierta.");
        });
    }
}
