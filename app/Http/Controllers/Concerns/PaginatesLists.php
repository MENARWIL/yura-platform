<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Cantidad de registros por página en las listas de la plataforma: el usuario elige 5, 10, 15 o 20
 * (parámetro per_page). Cualquier otro valor vuelve al de la pantalla.
 */
trait PaginatesLists
{
    public const PER_PAGE_OPTIONS = [5, 10, 15, 20];

    protected function perPage(Request $request, int $default = 10): int
    {
        $value = (int) $request->query('per_page');

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : $default;
    }
}
