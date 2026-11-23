<?php

namespace App\Support;

use App\Models\RobotDevice;
use Illuminate\Http\Request;

/**
 * Quién puede usar las rutas del robot.
 *  - Un robot registrado (contrato v1.3) con el permiso de la acción.
 *  - Transición: el usuario antiguo con rol «robot» (contrato v1.2), que se retira cuando el robot use su llave.
 */
class RobotAccess
{
    public static function device(Request $request): ?RobotDevice
    {
        $user = $request->user();

        return $user instanceof RobotDevice ? $user : null;
    }

    public static function allows(Request $request, string $ability): bool
    {
        $user = $request->user();
        if ($user instanceof RobotDevice) {
            return $user->isActive() && $user->tokenCan($ability);
        }

        return $user?->role === 'robot';
    }

    /** El usuario antiguo (v1.2), no un robot registrado. */
    public static function isLegacy(Request $request): bool
    {
        $user = $request->user();

        return ! $user instanceof RobotDevice && $user?->role === 'robot';
    }
}
