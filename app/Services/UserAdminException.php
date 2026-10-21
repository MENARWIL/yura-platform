<?php

namespace App\Services;

use DomainException;

/** Una regla de administración de usuarios impide la acción (mensaje apto para mostrar). */
class UserAdminException extends DomainException
{
}
