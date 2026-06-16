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

        if (in_array($user->rol, $roles)) {
            return $next($request);
        }

        return redirect('/')->with('error', 'No tienes permisos para acceder a esta sección.');
    }
}
