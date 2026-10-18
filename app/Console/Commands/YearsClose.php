<?php

namespace App\Console\Commands;

use App\Services\AcademicYearService;

class YearsClose extends YearCommand
{
    protected $signature = 'years:close {year} {--next= : Año que pasa a ser la actual (obligatorio si se cierra la actual)} {--yes : Confirma el cierre}';

    protected $description = 'Cierra una gestión: deja de aceptar cambios hasta que se reabra';

    public function handle(AcademicYearService $years): int
    {
        $year = $this->findYear((int) $this->argument('year'));
        if (! $year) {
            return self::FAILURE;
        }
        if (! $this->option('yes')) {
            $this->error("Cerrar {$year->name} impide cualquier cambio en sus datos. Repite el comando con --yes para confirmar.");

            return self::FAILURE;
        }
        $next = $this->option('next') ? $this->findYear((int) $this->option('next')) : null;
        if ($this->option('next') && ! $next) {
            return self::FAILURE;
        }

        return $this->attempt(function () use ($years, $year, $next) {
            $years->close(null, $year, $next);
            $this->info("{$year->name} cerrada." . ($next ? " La gestión actual es ahora {$next->name}." : ''));
        });
    }
}
