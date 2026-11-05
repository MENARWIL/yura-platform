<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta de la API a ciertos roles. Responde JSON 403 (CheckRole redirige, solo sirve para la web).
 */
class EnsureApiRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role;

        // El director y el Panel Administrativo (academic) entran a todo lo que permite el rol admin.
        $allowed = in_array($role, $roles, true)
            || (in_array($role, ['director', 'academic'], true) && in_array('admin', $roles, true));
        if (! $allowed) {
            return response()->json(['message' => 'No tienes permiso para esta acción.'], 403);
        }

        return $next($request);
    }
}
