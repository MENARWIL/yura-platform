<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => \App\Http\Middleware\CheckRole::class,
            'active.user' => \App\Http\Middleware\EnsureUserIsActive::class,
            'api.role' => \App\Http\Middleware\EnsureApiRole::class,
            'set.locale' => \App\Http\Middleware\SetLocale::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);

        $middleware->web(append: [\App\Http\Middleware\SetLocale::class, 'throttle:global']);
        $middleware->api(append: ['throttle:global']);
        
        $middleware->prependToGroup('api', \App\Http\Middleware\ForceJsonResponse::class);

        // Cabeceras de seguridad en todas las respuestas (web y API).
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Detrás de un intermediario (ngrok, Nginx, Cloudflare) Laravel debe confiar en él para saber
        // que la visita llegó por HTTPS; si no, arma enlaces http:// y la página sale sin estilos.
        // TRUSTED_PROXIES: IPs separadas por coma (ej. 127.0.0.1). Vacío = no confiar en ninguno.
        $trusted = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));
        $middleware->trustProxies(at: $trusted ?: null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Services\ClosedAcademicYearException $e, \Illuminate\Http\Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 409)
                : back()->with('error', $e->getMessage());
        });
    })->create();
