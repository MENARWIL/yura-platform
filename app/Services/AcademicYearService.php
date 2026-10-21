<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Gestiones escolares: crear, cambiar la actual, cerrar y reabrir.
 * Solo el rol admin (director y administrador). Con $actor = null se asume el operador del servidor (comandos).
 * Cada operación es atómica y queda en la auditoría.
 */
class AcademicYearService
{
    public function create(?User $actor, int $year): AcademicYear
    {
        $this->assertAdmin($actor);
        if ($year < 2000 || $year > 2100) {
            throw new \InvalidArgumentException('El año debe estar entre 2000 y 2100.');
        }

        return DB::transaction(function () use ($actor, $year) {
            if (AcademicYear::where('year', $year)->exists()) {
                throw new \InvalidArgumentException("La gestión {$year} ya existe.");
            }
            $created = AcademicYear::create(['year' => $year, 'name' => "Gestión {$year}", 'is_current' => false]);
            $this->audit($actor, 'year.create', $created);

            return $created;
        });
    }

    /** Cambia cuál es la gestión actual (donde se guardan los datos nuevos). Una gestión cerrada no puede serlo. */
    public function setCurrent(?User $actor, AcademicYear $year): AcademicYear
    {
        $this->assertAdmin($actor);

        return DB::transaction(function () use ($actor, $year) {
            $year = AcademicYear::lockForUpdate()->findOrFail($year->id);
            if ($year->isClosed()) {
                throw new UserAdminException("La gestión {$year->year} está cerrada: reábrela antes de ponerla como actual.");
            }
            $this->makeCurrent($year);
            $this->audit($actor, 'year.current', $year);

            return $year->fresh();
        });
    }

    /**
     * Cierra una gestión: deja de aceptar cambios hasta que se reabra.
     * Si es la actual, hay que indicar cuál pasa a serlo (nunca queda el sistema sin dónde guardar).
     */
    public function close(?User $actor, AcademicYear $year, ?AcademicYear $next = null): AcademicYear
    {
        $this->assertAdmin($actor);

        return DB::transaction(function () use ($actor, $year, $next) {
            $year = AcademicYear::lockForUpdate()->findOrFail($year->id);
            if ($year->isClosed()) {
                throw new UserAdminException("La gestión {$year->year} ya está cerrada.");
            }

            if ($year->is_current) {
                if (! $next || $next->id === $year->id) {
                    throw new UserAdminException('Para cerrar la gestión actual indica cuál será la nueva gestión actual.');
                }
                $next = AcademicYear::lockForUpdate()->findOrFail($next->id);
                if ($next->isClosed()) {
                    throw new UserAdminException("La gestión {$next->year} está cerrada: no puede ser la nueva actual.");
                }
                $this->makeCurrent($next);
            }

            $year->forceFill(['closed_at' => now(), 'closed_by' => $actor?->id])->save();
            $this->audit($actor, 'year.close', $year, ['next_current' => $next?->year]);

            return $year->fresh();
        });
    }

    public function reopen(?User $actor, AcademicYear $year): AcademicYear
    {
        $this->assertAdmin($actor);

        return DB::transaction(function () use ($actor, $year) {
            $year = AcademicYear::lockForUpdate()->findOrFail($year->id);
            if (! $year->isClosed()) {
                throw new UserAdminException("La gestión {$year->year} no está cerrada.");
            }
            $year->forceFill(['closed_at' => null, 'closed_by' => null])->save();
            $this->audit($actor, 'year.reopen', $year);

            return $year->fresh();
        });
    }

    private function makeCurrent(AcademicYear $year): void
    {
        AcademicYear::where('is_current', true)->where('id', '!=', $year->id)->update(['is_current' => false]);
        $year->forceFill(['is_current' => true])->save();
    }

    private function assertAdmin(?User $actor): void
    {
        if ($actor !== null && ! $actor->isAdmin()) {
            throw new UserAdminException('Solo un administrador puede gestionar las gestiones escolares.');
        }
    }

    private function audit(?User $actor, string $action, AcademicYear $year, ?array $details = null): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'entity' => 'academic_year',
            'entity_id' => $year->id,
            'ip_address' => Request::ip(),
            'details' => ['year' => $year->year] + ($details ?? []),
            'status' => 'success',
            'created_at' => now(),
        ]);
    }
}
