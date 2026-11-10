<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quien entró con una contraseña temporal solo puede cambiarla o salir.
 * Si la temporal venció mientras tenía la sesión abierta, se le cierra la sesión.
 */
class EnsurePasswordIsChanged
{
    private const ALLOWED_ROUTES = ['password.change', 'password.change.update', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->mustChangePassword() || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if ($user->temporaryPasswordExpired()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu contraseña temporal venció. Pide una nueva a la Dirección o a la Secretaría.',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Debes cambiar tu contraseña temporal antes de continuar.'], 403);
        }

        return redirect()->route('password.change');
    }
}
