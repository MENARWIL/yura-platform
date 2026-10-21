<?php

namespace App\Services;

use DomainException;

/** Se intentó cambiar datos de una gestión que un administrador ya cerró. */
class ClosedAcademicYearException extends DomainException
{
    public function __construct(string $yearName = 'la gestión')
    {
        parent::__construct("La gestión «{$yearName}» está cerrada y no admite cambios. Un administrador debe reabrirla primero.");
    }
}
