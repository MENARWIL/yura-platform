<?php

namespace App\Http\Middleware;

use App\Models\RobotDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrato v1.3. La llave del robot se entrega con un prefijo reconocible (yura_rbt_…) para detectarla si
 * se filtra; este middleware lo quita antes de que Sanctum busque el token, y marca la respuesta con la
 * versión del contrato. Debe ir antes de auth:sanctum.
 */
class RobotKeyHeader
{
    public const CONTRACT = '1.3';

    public function handle(Request $request, Closure $next): Response
    {
        $bearer = (string) $request->bearerToken();
        if (str_starts_with($bearer, RobotDevice::KEY_PREFIX)) {
            $request->headers->set('Authorization', 'Bearer ' . substr($bearer, strlen(RobotDevice::KEY_PREFIX)));
        }

        $response = $next($request);
        $response->headers->set('X-Yura-Contract', self::CONTRACT);

        // Último uso (fecha, hora e IP) para la pantalla «Robot YURA»; se anota cada medio minuto como máximo.
        $device = $request->user();
        if ($device instanceof RobotDevice && (! $device->last_used_at || $device->last_used_at->diffInSeconds(now(), true) >= 30)) {
            $device->forceFill(['last_used_at' => now(), 'last_used_ip' => $request->ip()])->saveQuietly();
        }

        return $response;
    }
}
