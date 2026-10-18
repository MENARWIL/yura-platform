<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un usuario suspendido pierde el acceso con el token que ya tenía, no solo el ingreso nuevo.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $status = strtolower(trim((string) ($user?->status ?? $user?->estado)));

        if ($user && ! in_array($status, ['active', 'activo'], true)) {
            return response()->json(['message' => 'Tu cuenta está suspendida. Contacta al administrador.'], 403);
        }

        return $next($request);
    }
}
