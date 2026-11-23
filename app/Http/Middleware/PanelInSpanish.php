<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El panel interno (después de iniciar sesión) funciona en castellano. Toda la web ya va en
 * castellano (APP_LOCALE=es, sin selector de idioma desde la 0.7.3); esto lo asegura en el panel
 * aunque el .env de un equipo diga otro idioma.
 */
class PanelInSpanish
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('es');

        return $next($request);
    }
}
