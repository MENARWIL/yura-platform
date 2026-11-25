<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Al cambiar la contraseña («Cambia tu contraseña» tras la temporal y «Mi perfil») se pide lo mismo
 * que al crear usuarios: 8 caracteres, mayúscula, minúscula, número y símbolo. Las contraseñas que
 * ya existen siguen sirviendo para entrar.
 */
class PasswordStrengthTest extends TestCase
{
    use RefreshDatabase;

    /** Cada una le falta algo: largo, mayúscula, minúscula, número o símbolo. */
    private const WEAK = ['Cl4ve!', 'clave2026!', 'CLAVE2026!', 'ClaveSegura!', 'NuevaClave2026'];

    private const STRONG = 'NuevaClave2026!';

    private function user(array $extra = []): User
    {
        return User::factory()->create($extra + ['role' => 'profesor', 'status' => 'active', 'password' => Hash::make('vieja1234')]);
    }

    public function test_change_after_temporary_password_requires_upper_lower_number_and_symbol(): void
    {
        $user = $this->user(['password_must_change' => true, 'temporary_password_expires_at' => now()->addHours(24)]);
        $this->actingAs($user);

        foreach (self::WEAK as $weak) {
            $this->put(route('password.change.update'), ['password' => $weak, 'password_confirmation' => $weak])
                ->assertSessionHasErrors('password');
        }
        $this->assertTrue($user->fresh()->password_must_change);

        $this->put(route('password.change.update'), ['password' => self::STRONG, 'password_confirmation' => self::STRONG])
            ->assertRedirect(route('dashboard'));
        $this->assertTrue(Hash::check(self::STRONG, $user->fresh()->password));
        $this->assertFalse($user->fresh()->password_must_change);
    }

    public function test_change_after_temporary_password_explains_the_missing_symbol_in_spanish(): void
    {
        $user = $this->user(['password_must_change' => true, 'temporary_password_expires_at' => now()->addHours(24)]);

        $this->actingAs($user)->put(route('password.change.update'), ['password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026'])
            ->assertSessionHasErrors(['password' => 'La nueva contraseña debe tener al menos un símbolo.']);
    }

    public function test_profile_password_requires_upper_lower_number_and_symbol(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $base = ['name' => $user->name, 'email' => $user->email];

        foreach (self::WEAK as $weak) {
            $this->patch(route('profile.update'), $base + ['password' => $weak, 'password_confirmation' => $weak])
                ->assertSessionHasErrors('password');
        }
        $this->assertTrue(Hash::check('vieja1234', $user->fresh()->password));

        $this->patch(route('profile.update'), $base + ['password' => self::STRONG, 'password_confirmation' => 'Otra2026!'])
            ->assertSessionHasErrors(['password' => 'Las dos contraseñas no coinciden.']);

        $this->patch(route('profile.update'), $base + ['password' => self::STRONG, 'password_confirmation' => self::STRONG])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check(self::STRONG, $user->fresh()->password));
    }

    public function test_profile_without_a_new_password_keeps_the_current_one(): void
    {
        $user = $this->user();

        $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'password' => '', 'password_confirmation' => ''])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('vieja1234', $user->fresh()->password));
    }

    public function test_existing_passwords_without_symbol_still_log_in(): void
    {
        $user = $this->user();

        $this->post('/login', ['email' => $user->email, 'password' => 'vieja1234'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_both_forms_show_the_rule_and_validate_on_the_page(): void
    {
        $hint = 'Al menos 8 caracteres, con una mayúscula, una minúscula, un número y un símbolo';

        $this->actingAs($this->user())->get(route('profile.edit'))->assertOk()
            ->assertSee($hint)
            ->assertSee('data-rule="password"', false)
            ->assertSee('data-match="password"', false)
            ->assertSee('js/user-form-validation.js', false);

        $this->actingAs($this->user(['password_must_change' => true, 'temporary_password_expires_at' => now()->addHours(24)]))
            ->get(route('password.change'))->assertOk()
            ->assertSee($hint)
            ->assertSee('data-rule="password"', false)
            ->assertSee('data-match="password"', false)
            ->assertSee('js/user-form-validation.js', false);
    }
}
