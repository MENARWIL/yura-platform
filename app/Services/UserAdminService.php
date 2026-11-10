<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Reglas de administración de usuarios compartidas por la web y la API:
 * protección del propio administrador, siempre al menos un administrador activo,
 * cierre de sesiones y auditoría. Cada operación es atómica: o se aplica todo, o nada.
 */
class UserAdminService
{
    /**
     * Roles que un administrador puede asignar (el robot se crea aparte, con su seeder).
     * «estudiante» ya no se ofrece en los formularios, pero sigue siendo válido para cuentas antiguas.
     */
    public const ROLES = ['padre', 'madre', 'tutor', 'profesor', 'admin', 'director', 'estudiante', 'academic'];

    /** El director administra todo, menos las cuentas de administrador (ni crearlas ni tocarlas). */
    public const DIRECTOR_ADMIN_MESSAGE = 'El director no puede crear, modificar, suspender ni eliminar administradores.';

    /** El Panel Administrativo (rol academic) no agrega usuarios ni toca cuentas de administrador o de director. */
    public const SECRETARY_CREATE_MESSAGE = 'El Panel Administrativo no puede agregar usuarios.';

    public const SECRETARY_ACCOUNT_MESSAGE = 'El Panel Administrativo no puede crear, modificar, suspender ni eliminar cuentas de administrador o de director.';

    public function create(User $actor, array $attributes): User
    {
        // Usuarios y profesores: la Secretaría no los agrega (los familiares se registran al inscribir, por otro camino).
        if (! $actor->canCreateAccounts()) {
            throw new UserAdminException(self::SECRETARY_CREATE_MESSAGE);
        }
        $this->guardRoleAssignment($actor, $attributes['rol'] ?? $attributes['role'] ?? null);

        return DB::transaction(function () use ($actor, $attributes) {
            $user = User::create($attributes);
            $this->audit($actor, 'user.create', $user, ['role' => $user->role]);

            return $user;
        });
    }

    /** Suspende o reactiva. Nadie puede suspenderse a sí mismo ni dejar al sistema sin administrador activo. */
    public function toggleStatus(User $actor, User $target): User
    {
        $this->guardSelf($actor, $target, 'No puedes suspender tu propia cuenta.');
        $this->guardAdminAccount($actor, $target);

        return DB::transaction(function () use ($actor, $target) {
            $current = strtolower(trim((string) $target->status));
            $wasActive = in_array($current, ['active', 'activo'], true);
            if ($wasActive) {
                $this->guardLastAdmin($target, 'No se puede suspender al último administrador activo.');
            }

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
        });
    }

    /**
     * Actualiza datos. Cambiar el rol o la contraseña cierra las sesiones abiertas.
     * Un administrador no puede quitarse a sí mismo el rol, ni dejar al sistema sin administrador activo.
     */
    public function update(User $actor, User $target, array $attributes): User
    {
        $this->guardAdminAccount($actor, $target);
        $newRole = $attributes['rol'] ?? $attributes['role'] ?? null;
        $this->guardRoleAssignment($actor, $newRole);
        $roleChanges = $newRole !== null && $newRole !== $target->role;
        if ($roleChanges) {
            $this->guardSelf($actor, $target, 'No puedes cambiar tu propio rol.');
        }
        $passwordChanges = ! empty($attributes['password']);

        return DB::transaction(function () use ($actor, $target, $attributes, $roleChanges, $passwordChanges) {
            if ($roleChanges) {
                $this->guardLastAdmin($target, 'No se puede quitar el rol al último administrador activo.');
            }

            $before = $target->only(['name', 'email', 'telefono', 'role']);
            $target->update($attributes);
            $changed = array_keys(array_filter(
                $target->only(['name', 'email', 'telefono', 'role']),
                fn ($value, $key) => $value !== ($before[$key] ?? null),
                ARRAY_FILTER_USE_BOTH
            ));
            if ($passwordChanges) {
                $changed[] = 'password';
                // Una contraseña puesta a mano en «Editar» reemplaza a una temporal pendiente.
                $target->forceFill(['password_must_change' => false, 'temporary_password_expires_at' => null])->save();
            }

            if ($roleChanges || $passwordChanges) {
                $this->revokeSessions($target);
            }
            $this->audit($actor, 'user.update', $target, ['changed' => $changed]);

            return $target;
        });
    }

    public function delete(User $actor, User $target): void
    {
        $this->guardSelf($actor, $target, 'No puedes eliminar tu propia cuenta.');
        $this->guardAdminAccount($actor, $target);

        DB::transaction(function () use ($actor, $target) {
            $this->guardLastAdmin($target, 'No se puede eliminar al último administrador activo.');
            $target->delete();
            $this->revokeSessions($target);
            $this->audit($actor, 'user.delete', $target);
        });
    }

    public function restore(User $actor, User $target): void
    {
        $this->guardAdminAccount($actor, $target);

        DB::transaction(function () use ($actor, $target) {
            $target->restore();
            $this->audit($actor, 'user.restore', $target);
        });
    }

    public function forceDelete(User $actor, User $target): void
    {
        $this->guardSelf($actor, $target, 'No puedes eliminar tu propia cuenta.');
        $this->guardAdminAccount($actor, $target);

        DB::transaction(function () use ($actor, $target) {
            $this->guardLastAdmin($target, 'No se puede eliminar al último administrador activo.');
            $this->revokeSessions($target);
            $this->audit($actor, 'user.force_delete', $target, ['email' => $target->email]);
            $target->forceDelete();
        });
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

    /**
     * Cuentas protegidas: el director no toca administradores; el Panel Administrativo
     * no toca administradores ni directores.
     */
    private function guardAdminAccount(User $actor, User $target): void
    {
        if ($actor->isDirector() && $target->role === 'admin') {
            throw new UserAdminException(self::DIRECTOR_ADMIN_MESSAGE);
        }
        if ($actor->isAcademic() && in_array($target->role, ['admin', 'director'], true)) {
            throw new UserAdminException(self::SECRETARY_ACCOUNT_MESSAGE);
        }
    }

    /** Roles que no se pueden asignar: el director no asigna «admin»; la Secretaría, ni «admin» ni «director». */
    private function guardRoleAssignment(User $actor, ?string $role): void
    {
        if ($actor->isDirector() && $role === 'admin') {
            throw new UserAdminException(self::DIRECTOR_ADMIN_MESSAGE);
        }
        if ($actor->isAcademic() && in_array($role, ['admin', 'director'], true)) {
            throw new UserAdminException(self::SECRETARY_ACCOUNT_MESSAGE);
        }
    }

    /**
     * Si el objetivo es un administrador activo, debe quedar al menos otro administrador activo.
     * Se bloquean las filas de los administradores para que dos solicitudes simultáneas no se anulen entre sí.
     */
    private function guardLastAdmin(User $target, string $message): void
    {
        if ($target->role !== 'admin' || ! in_array(strtolower(trim((string) $target->status)), ['active', 'activo'], true)) {
            return;
        }

        $statusColumn = Schema::hasColumn('users', 'status') ? 'status' : 'estado';
        $otherActiveAdmins = User::ofRole('admin')
            ->whereIn($statusColumn, ['active', 'activo'])
            ->where('id', '!=', $target->id)
            ->lockForUpdate()
            ->count();

        if ($otherActiveAdmins < 1) {
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
