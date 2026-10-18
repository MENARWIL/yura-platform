<?php

namespace App\Models\Concerns;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Builder;

/**
 * Los datos de un modelo pertenecen a una gestión. Por defecto solo se ven los de la gestión actual;
 * una consulta con ?year_id=N (API) ve los de esa gestión. Al crear, se asigna la gestión actual.
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
    }
}
