<?php

namespace App\Services;

/** La casilla que se quiere descartar tiene notas: hay que confirmar que se borran. */
class NotebookColumnHasGrades extends UserAdminException
{
    public function __construct(public readonly string $label, public readonly int $column, public readonly int $count)
    {
        parent::__construct(
            "La casilla {$label} {$column} tiene {$count} nota(s). Si la descartas, esas notas se borran "
            . '(quedan copiadas en la Auditoría).'
        );
    }
}
