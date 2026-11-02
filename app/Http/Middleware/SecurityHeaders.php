<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en todas las respuestas (web y API), sin depender del hosting:
 * evita que YURA se muestre dentro de otra página (clickjacking), que el navegador «adivine»
 * tipos de archivo, que se filtren direcciones internas y, con HTTPS, que se vuelva a http.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        // Política mínima que no rompe los scripts propios de las pantallas: solo bloquea marcos
        // ajenos, plugins, <base> inyectados y formularios que envíen datos a otro sitio.
        $headers->set('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=15552000');
        }

        $headers->remove('X-Powered-By');

        return $response;
    }
}
