<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 1 del plan v1.1: reglas compartidas de administración de usuarios (U1–U6).
 */
class UserAdminRulesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function user(string $role = 'padre', array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'active'], $extra));
    }

    private function editPayload(User $user, array $override = []): array
    {
        return array_merge([
            'first_name' => 'Nombre',
            'last_name' => 'Apellido',
            'second_last_name' => 'Materno',
            'email' => $user->email,
            'rol' => $user->role,
            'telefono' => '70010011',
        ], $override);
    }

    // ---------------------------------------------------------------- U1

    public function test_admin_cannot_suspend_self_from_web_or_api(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch("/users/{$admin->id}/status");
        $this->assertSame('active', $admin->fresh()->status);

        $this->actingAs($admin, 'sanctum')->patchJson("/api/users/{$admin->id}/status")->assertStatus(422);
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put("/users/{$admin->id}", $this->editPayload($admin, ['rol' => 'profesor']));

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_can_still_suspend_another_user(): void
    {
        $admin = $this->admin();
        $other = $this->user('profesor');

        $this->actingAs($admin, 'sanctum')->patchJson("/api/users/{$other->id}/status")->assertOk();
        $this->assertSame('inactive', $other->fresh()->status);
    }

    public function test_the_last_active_admin_cannot_be_suspended_demoted_or_deleted(): void
    {
        $service = app(\App\Services\UserAdminService::class);
        $lastAdmin = $this->admin();
        // Un administrador que ya fue suspendido pero cuya solicitud sigue en curso (caso de dos solicitudes simultáneas).
        $staleAdmin = User::factory()->create(['role' => 'admin', 'status' => 'inactive']);

        foreach ([
            fn () => $service->toggleStatus($staleAdmin, $lastAdmin),
            fn () => $service->update($staleAdmin, $lastAdmin, ['rol' => 'profesor']),
            fn () => $service->delete($staleAdmin, $lastAdmin),
            fn () => $service->forceDelete($staleAdmin, $lastAdmin),
        ] as $action) {
            try {
                $action();
                $this->fail('el sistema quedó sin administrador activo');
            } catch (\App\Services\UserAdminException) {
                // esperado
            }
        }

        $fresh = $lastAdmin->fresh();
        $this->assertSame('admin', $fresh->role);
        $this->assertSame('active', $fresh->status);
        $this->assertNull($fresh->deleted_at);
    }

    public function test_a_failing_audit_rolls_back_the_change(): void
    {
        $admin = $this->admin();
        $target = $this->user('padre');
        $target->createToken('app');
        \Illuminate\Support\Facades\Schema::drop('audit_logs'); // fuerza el fallo de la auditoría

        try {
            app(\App\Services\UserAdminService::class)->toggleStatus($admin, $target);
            $this->fail('debía fallar');
        } catch (\Throwable) {
            // esperado
        }

        $this->assertSame('active', $target->fresh()->status, 'el cambio persistió aunque la auditoría falló');
        $this->assertSame(1, $target->tokens()->count(), 'las sesiones se cerraron aunque la operación falló');
    }

    // ---------------------------------------------------------------- U2

    public function test_api_user_creation_requires_a_strong_password(): void
    {
        $admin = $this->admin();
        $payload = ['name' => 'Ana Paz Lima', 'email' => 'ana@school.bo', 'rol' => 'padre', 'telefono' => '70010011'];

        $this->actingAs($admin, 'sanctum')->postJson('/api/users', $payload + ['password' => 'password1'])
            ->assertStatus(400);
        $this->assertDatabaseMissing('users', ['email' => 'ana@school.bo']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/users', $payload + ['password' => 'Clave#12345'])
            ->assertCreated();
    }

    // ---------------------------------------------------------------- U3

    public function test_api_user_list_does_not_expose_sensitive_columns(): void
    {
        $admin = $this->admin();
        $this->user('padre', ['ci' => '1234567']);

        $row = $this->actingAs($admin, 'sanctum')->getJson('/api/users')->assertOk()->json('data.0');

        foreach (['ci', 'password', 'remember_token', 'foto_path'] as $column) {
            $this->assertArrayNotHasKey($column, $row, "la lista expone {$column}");
        }
        $this->assertArrayHasKey('email', $row);
    }

    // ---------------------------------------------------------------- U4

    public function test_status_change_and_creation_are_audited(): void
    {
        $admin = $this->admin();
        $target = $this->user('profesor');

        $this->actingAs($admin, 'sanctum')->patchJson("/api/users/{$target->id}/status")->assertOk();
        $this->assertTrue(
            AuditLog::where('action', 'user.status')->where('entity_id', $target->id)->where('user_id', $admin->id)->exists(),
            'el cambio de estado no se registró'
        );

        $this->actingAs($admin, 'sanctum')->postJson('/api/users', [
            'name' => 'Luis Rojas Vega', 'email' => 'luis@school.bo', 'password' => 'Clave#12345',
            'rol' => 'padre', 'telefono' => '70010011',
        ])->assertCreated();
        $this->assertTrue(AuditLog::where('action', 'user.create')->exists(), 'la creación no se registró');
    }

    public function test_web_status_change_and_delete_are_audited(): void
    {
        $admin = $this->admin();
        $target = $this->user('profesor');

        $this->actingAs($admin)->patch("/users/{$target->id}/status");
        $this->assertTrue(AuditLog::where('action', 'user.status')->where('entity_id', $target->id)->exists());

        $this->actingAs($admin)->delete("/users/{$target->id}");
        $this->assertTrue(AuditLog::where('action', 'user.delete')->where('entity_id', $target->id)->exists());
    }

    // ---------------------------------------------------------------- U5

    public function test_suspending_a_user_closes_their_sessions(): void
    {
        $admin = $this->admin();
        $target = $this->user('padre');
        $target->createToken('app');

        $this->actingAs($admin, 'sanctum')->patchJson("/api/users/{$target->id}/status")->assertOk();

        $this->assertSame(0, $target->tokens()->count());
    }

    public function test_role_or_password_change_closes_sessions(): void
    {
        $admin = $this->admin();
        $target = $this->user('padre');
        $target->createToken('app');

        $this->actingAs($admin)->put("/users/{$target->id}", $this->editPayload($target, ['rol' => 'tutor']));
        $this->assertSame(0, $target->tokens()->count(), 'el cambio de rol no cerró sesiones');

        $target->createToken('app');
        $this->actingAs($admin)->put("/users/{$target->id}", $this->editPayload($target->fresh(), ['password' => 'Nueva#12345', 'password_confirmation' => 'Nueva#12345']));
        $this->assertSame(0, $target->tokens()->count(), 'el cambio de contraseña no cerró sesiones');
    }

    public function test_updating_only_contact_data_keeps_sessions(): void
    {
        $admin = $this->admin();
        $target = $this->user('padre');
        $target->createToken('app');

        $this->actingAs($admin)->put("/users/{$target->id}", $this->editPayload($target, ['telefono' => '70099999']));

        $this->assertSame(1, $target->tokens()->count());
    }

    public function test_deleting_a_user_closes_their_sessions(): void
    {
        $admin = $this->admin();
        $target = $this->user('padre');
        $target->createToken('app');

        $this->actingAs($admin)->delete("/users/{$target->id}");

        $this->assertSame(0, $target->tokens()->count());
    }

    // ---------------------------------------------------------------- U6

    public function test_academic_role_can_be_assigned_from_the_user_form(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/users', [
            'last_name' => 'Prueba', 'second_last_name' => 'Ejemplo', 'first_name' => 'Academico',
            'email' => 'academico@example.com', 'password' => 'Clave#12345', 'password_confirmation' => 'Clave#12345', 'rol' => 'academic',
        ])->assertRedirect('/users');

        $this->assertSame('academic', User::where('email', 'academico@example.com')->firstOrFail()->role);
    }
}
