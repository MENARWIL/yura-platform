<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

/**
 * Reglas de administración de usuarios compartidas por la web y la API:
 * protección del propio administrador, cierre de sesiones y auditoría.
 */
class UserAdminService
{
    /** Roles que un administrador puede asignar (el robot se crea aparte, con su seeder). */
    public const ROLES = ['padre', 'madre', 'tutor', 'profesor', 'admin', 'estudiante', 'academic'];

    public function create(User $actor, array $attributes): User
    {
        $user = User::create($attributes);
        $this->audit($actor, 'user.create', $user, ['role' => $user->role]);

        return $user;
    }

    /** Suspende o reactiva. Nadie puede suspenderse a sí mismo. */
    public function toggleStatus(User $actor, User $target): User
    {
        $this->guardSelf($actor, $target, 'No puedes suspender tu propia cuenta.');

        $current = strtolower(trim((string) $target->status));
        $wasActive = in_array($current, ['active', 'activo'], true);
        // Se conserva el idioma del valor que ya tenía la cuenta (activo/inactivo o active/inactive).
        $spanish = in_array($current, ['activo', 'inactivo'], true);
        $target->status = $wasActive
            ? ($spanish ? 'inactivo' : 'inactive')
            : ($spanish ? 'activo' : 'active');
        $target->save();

        if ($wasActive) {
            $this->revokeSessions($target);
        }
        $this->audit($actor, 'user.status', $target, ['from' => $current, 'to' => $target->status]);

        return $target;
    }

    /**
     * Actualiza datos. Cambiar el rol o la contraseña cierra las sesiones abiertas.
     * Un administrador no puede quitarse a sí mismo el rol de administrador.
     */
    public function update(User $actor, User $target, array $attributes): User
    {
        $newRole = $attributes['rol'] ?? $attributes['role'] ?? null;
        $roleChanges = $newRole !== null && $newRole !== $target->role;
        if ($roleChanges) {
            $this->guardSelf($actor, $target, 'No puedes cambiar tu propio rol.');
        }
        $passwordChanges = ! empty($attributes['password']);

        $before = $target->only(['name', 'email', 'telefono', 'role']);
        $target->update($attributes);
        $changed = array_keys(array_filter(
            $target->only(['name', 'email', 'telefono', 'role']),
            fn ($value, $key) => $value !== ($before[$key] ?? null),
            ARRAY_FILTER_USE_BOTH
        ));
        if ($passwordChanges) {
            $changed[] = 'password';
        }

        if ($roleChanges || $passwordChanges) {
            $this->revokeSessions($target);
        }
        $this->audit($actor, 'user.update', $target, ['changed' => $changed]);

        return $target;
    }

    public function delete(User $actor, User $target): void
    {
        $this->guardSelf($actor, $target, 'No puedes eliminar tu propia cuenta.');

        $target->delete();
        $this->revokeSessions($target);
        $this->audit($actor, 'user.delete', $target);
    }

    public function restore(User $actor, User $target): void
    {
        $target->restore();
        $this->audit($actor, 'user.restore', $target);
    }

    public function forceDelete(User $actor, User $target): void
    {
        $this->guardSelf($actor, $target, 'No puedes eliminar tu propia cuenta.');

        $this->revokeSessions($target);
        $this->audit($actor, 'user.force_delete', $target, ['email' => $target->email]);
        $target->forceDelete();
    }

    public function hashPassword(string $plain): string
    {
        return Hash::make($plain);
    }

    private function guardSelf(User $actor, User $target, string $message): void
    {
        if ($actor->is($target)) {
            throw new UserAdminException($message);
        }
    }

    private function revokeSessions(User $user): void
    {
        $user->tokens()->delete();
    }

    private function audit(User $actor, string $action, User $target, ?array $details = null): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'entity' => 'user',
            'entity_id' => $target->id,
            'ip_address' => Request::ip(),
            'details' => $details,
            'status' => 'success',
            'created_at' => now(),
        ]);
    }
}
