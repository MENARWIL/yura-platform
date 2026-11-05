<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user) {
            return redirect('login');
        }

        $userRole = $user->role ?? $user->rol;

        // El director y el Panel Administrativo (academic) entran a todo lo que permite el rol admin;
        // lo que no pueden hacer se bloquea en cada acción (gestiones, cuentas, crear usuarios).
        $asAdmin = in_array($userRole, ['director', 'academic'], true) && in_array('admin', $roles, true);
        if (in_array($userRole, $roles, true) || $asAdmin) {
            return $next($request);
        }

        return redirect('/')->with('error', 'No tienes permisos para acceder a esta sección.');
    }
}
