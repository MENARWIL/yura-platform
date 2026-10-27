<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Los formularios web que fijan una contraseña piden escribirla dos veces
 * (password + password_confirmation). La API de la app móvil no cambia.
 */
class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG = 'Clave#12345';

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function teacherPayload(array $extra = []): array
    {
        return array_merge([
            'last_name' => 'Quispe',
            'second_last_name' => 'Mamani',
            'first_name' => 'Rosa',
            'email' => 'rosa.quispe@example.test',
            'telefono' => '70000000',
            'main_subject_id' => Subject::where('code', 'QUE')->firstOrFail()->id,
        ], $extra);
    }

    public function test_teacher_creation_rejects_a_different_confirmation(): void
    {
        $response = $this->actingAs($this->admin())->from('/teachers/create')->post('/teachers', $this->teacherPayload([
            'password' => self::STRONG,
            'password_confirmation' => 'Otra#Clave99',
        ]));

        $response->assertRedirect('/teachers/create');
        $response->assertSessionHasErrors(['password' => __('messages.validation.password_confirm')]);
        $this->assertDatabaseMissing('users', ['email' => 'rosa.quispe@example.test']);
    }

    public function test_teacher_creation_rejects_a_missing_confirmation(): void
    {
        $this->actingAs($this->admin())->post('/teachers', $this->teacherPayload(['password' => self::STRONG]))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'rosa.quispe@example.test']);
    }

    public function test_teacher_creation_accepts_a_matching_confirmation(): void
    {
        $this->actingAs($this->admin())->post('/teachers', $this->teacherPayload([
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ]))->assertRedirect(route('teachers.index'));

        $teacher = User::where('email', 'rosa.quispe@example.test')->firstOrFail();
        $this->assertTrue(Hash::check(self::STRONG, $teacher->password));
    }

    public function test_teacher_edit_requires_confirmation_only_when_a_new_password_is_given(): void
    {
        $admin = $this->admin();
        $teacher = User::factory()->create([
            'role' => 'profesor', 'status' => 'active', 'email' => 'rosa.quispe@example.test',
            'password' => Hash::make('Original#123'),
        ]);
        $payload = $this->teacherPayload();

        // Contraseña nueva sin confirmación: se rechaza y la actual no cambia.
        $this->actingAs($admin)->put("/teachers/{$teacher->id}", $payload + ['password' => 'Nueva#12345'])
            ->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('Original#123', $teacher->fresh()->password));

        // Confirmación distinta: se rechaza.
        $this->actingAs($admin)->put("/teachers/{$teacher->id}", $payload + ['password' => 'Nueva#12345', 'password_confirmation' => 'Distinta#123'])
            ->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('Original#123', $teacher->fresh()->password));

        // Sin contraseña nueva (ni confirmación): se conserva la actual.
        $this->actingAs($admin)->put("/teachers/{$teacher->id}", $payload)
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Original#123', $teacher->fresh()->password));

        // Contraseña nueva con confirmación igual: se cambia.
        $this->actingAs($admin)->put("/teachers/{$teacher->id}", $payload + ['password' => 'Nueva#12345', 'password_confirmation' => 'Nueva#12345'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Nueva#12345', $teacher->fresh()->password));
    }

    public function test_user_and_tutor_creation_require_matching_confirmation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/users', [
            'last_name' => 'Prueba', 'second_last_name' => 'Ejemplo', 'first_name' => 'Maestro',
            'email' => 'maestro@example.com', 'password' => self::STRONG, 'password_confirmation' => 'Otra#Clave99', 'rol' => 'profesor',
        ])->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'maestro@example.com']);

        $tutor = [
            'ci' => '1234567', 'last_name' => 'Lima', 'second_last_name' => 'Paz', 'first_name' => 'Ana',
            'phone' => '70010011', 'email' => 'ana.lima@example.com', 'password' => self::STRONG,
        ];
        $this->actingAs($admin)->post('/tutors', $tutor + ['password_confirmation' => 'Otra#Clave99'])
            ->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'ana.lima@example.com']);

        $this->actingAs($admin)->post('/tutors', $tutor + ['password_confirmation' => self::STRONG])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'ana.lima@example.com']);
    }

    public function test_the_forms_show_the_confirmation_field(): void
    {
        $admin = $this->admin();
        $teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active']);
        $tutor = User::factory()->create(['role' => 'padre', 'status' => 'active']);

        foreach (['/teachers/create', '/users/create', '/tutors/create', "/teachers/{$teacher->id}/edit", "/users/{$teacher->id}/edit", "/tutors/{$tutor->id}/edit"] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()
                ->assertSee('name="password_confirmation"', false)
                ->assertSee('data-match="password"', false);
        }
    }

    public function test_the_mobile_api_contract_does_not_ask_for_a_confirmation(): void
    {
        $this->actingAs($this->admin(), 'sanctum')->postJson('/api/users', [
            'name' => 'Ana Paz Lima', 'email' => 'ana@school.bo', 'rol' => 'padre', 'telefono' => '70010011', 'password' => self::STRONG,
        ])->assertCreated();
    }
}
