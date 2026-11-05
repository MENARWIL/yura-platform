<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El panel interno (después de iniciar sesión) funciona solo en castellano: la mayoría de sus
 * pantallas no tienen traducción al quechua y la interfaz salía mezclada. El portal público y el
 * inicio de sesión siguen en castellano y quechua; la elección se conserva en la sesión.
 */
class PanelInSpanish
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('es');

        return $next($request);
    }
}
