<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\RobotDevice;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registrar, renovar y revocar la llave del robot (contrato v1.3). La llave completa solo existe en el
 * momento de crearla: en la base queda únicamente su huella (hash), que ni el administrador puede leer.
 */
class RobotDeviceService
{
    /** @return array{0: RobotDevice, 1: string} el robot y su llave (se muestra una sola vez) */
    public function register(string $name, Subject $subject, ?User $teacher, ?User $creator, ?Request $request = null): array
    {
        return DB::transaction(function () use ($name, $subject, $teacher, $creator, $request) {
            $device = RobotDevice::create([
                'name' => $name,
                'subject_id' => $subject->id,
                'teacher_user_id' => $teacher?->id,
                'created_by' => $creator?->id,
                'status' => 'active',
            ]);
            $key = $this->issueKey($device);
            $this->audit('robot.create', $device, ['name' => $name, 'subject' => $subject->name], $request);

            return [$device, $key];
        });
    }

    /** Genera una llave nueva y anula la anterior (reinstalación de la Raspberry o sospecha de filtración). */
    public function rotate(RobotDevice $device, ?Request $request = null): string
    {
        return DB::transaction(function () use ($device, $request) {
            $device->forceFill(['status' => 'active', 'revoked_at' => null])->save();
            $key = $this->issueKey($device);
            $this->audit('robot.rotate', $device, ['name' => $device->name], $request);

            return $key;
        });
    }

    /** Corta el acceso al instante: la llave deja de existir. */
    public function revoke(RobotDevice $device, ?Request $request = null): void
    {
        DB::transaction(function () use ($device, $request) {
            $device->tokens()->delete();
            $device->forceFill(['status' => 'revoked', 'revoked_at' => now()])->save();
            $this->audit('robot.revoke', $device, ['name' => $device->name], $request);
        });
    }

    private function issueKey(RobotDevice $device): string
    {
        $device->tokens()->delete();
        $plain = RobotDevice::KEY_PREFIX . $device->createToken('robot-yura', RobotDevice::ABILITIES)->plainTextToken;
        $device->forceFill(['key_hint' => substr($plain, -4), 'key_created_at' => now()])->save();

        return $plain;
    }

    private function audit(string $action, RobotDevice $device, array $details, ?Request $request): void
    {
        AuditLog::log($action, 'robot_device', $device->id, $details, $request);
    }
}
