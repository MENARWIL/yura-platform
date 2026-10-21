<?php

namespace App\Models\Concerns;

use App\Models\AcademicYear;
use App\Services\ClosedAcademicYearException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Los datos de un modelo pertenecen a una gestión. Por defecto solo se ven los de la gestión actual;
 * una consulta con ?year_id=N (API) ve los de esa gestión. Al crear, se asigna la gestión actual.
 * Una gestión cerrada no admite crear, cambiar ni borrar sus datos (ver AcademicYearService).
 *
 * Límite conocido: las operaciones masivas de consulta (Model::where(...)->update()/delete())
 * no disparan eventos de modelo y no pasan por esta protección.
 */
trait BelongsToAcademicYear
{
    protected static function bootBelongsToAcademicYear(): void
    {
        static::addGlobalScope('academic_year', function (Builder $query) {
            $column = $query->getModel()->getTable() . '.academic_year_id';
            $selected = (int) request()->query('year_id');

            if ($selected > 0) {
                $query->where($column, $selected);

                return;
            }

            $query->whereIn($column, fn ($sub) => $sub->select('id')->from('academic_years')->where('is_current', true));
        });

        static::creating(function ($model) {
            if (! $model->academic_year_id) {
                $model->academic_year_id = AcademicYear::currentId();
            }
        });

        static::saving(fn ($model) => static::assertYearIsOpen($model));
        static::deleting(fn ($model) => static::assertYearIsOpen($model));
    }

    private static function assertYearIsOpen($model): void
    {
        // Antes de «creating» todavía no tiene gestión: se usa la actual, que es donde se va a guardar.
        $ids = array_filter(array_unique([
            $model->academic_year_id ?: AcademicYear::currentId(),
            $model->getOriginal('academic_year_id'),
        ]));

        $closed = AcademicYear::whereIn('id', $ids)->whereNotNull('closed_at')->first();
        if ($closed) {
            throw new ClosedAcademicYearException($closed->name);
        }
    }
}
