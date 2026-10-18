<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use Illuminate\Console\Command;

/**
 * Base de los comandos de gestión escolar. El operador del servidor actúa sin usuario
 * (en la auditoría queda sin autor). Cerrar y reabrir exigen --yes.
 */
abstract class YearCommand extends Command
{
    protected function findYear(int $year): ?AcademicYear
    {
        $found = AcademicYear::where('year', $year)->first();
        if (! $found) {
            $this->error("No existe la gestión {$year}. Créala con: php artisan years:create {$year}");
        }

        return $found;
    }

    protected function attempt(callable $action): int
    {
        try {
            $action();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
