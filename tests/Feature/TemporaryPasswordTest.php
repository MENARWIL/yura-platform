<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\TemporaryPasswordService;
use App\Services\UserAdminException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Contraseñas temporales por WhatsApp: solo Dirección, administrador principal y Secretaría;
 * vencen a las 24 horas y obligan a cambiar la contraseña al entrar.
 */
class TemporaryPasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $mainAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        // Por defecto, sin envío automático (no depende del .env de la máquina).
        config(['services.whatsapp.token' => null, 'services.whatsapp.phone_number_id' => null]);
        Http::preventStrayRequests();
        // El administrador principal es la primera cuenta de administrador creada.
        $this->mainAdmin = $this->makeUser('admin');
    }

    private function makeUser(string $role, array $extra = []): User
    {
        return User::factory()->create($extra + ['rol' => $role, 'estado' => 'activo', 'telefono' => '71234567']);
    }

    private function issue(User $actor, User $target)
    {
        return $this->actingAs($actor)->post(route('users.temporary-password', $target));
    }

    public function test_director_generates_password_and_whatsapp_link(): void
    {
        $director = $this->makeUser('director');
        $padre = $this->makeUser('padre', ['first_name' => 'Mario']);
        $padre->createToken('app');

        $response = $this->issue($director, $padre)->assertOk();

        $password = $response->viewData('password');
        $this->assertMatchesRegularExpression('/^[A-Za-z2-9]{10}$/', $password);
        $this->assertFalse($response->viewData('sent'));
        $response->assertSee($password);
        $this->assertStringStartsWith('https://wa.me/59171234567?text=', $response->viewData('whatsapp_url'));
        $this->assertStringContainsString(rawurlencode($password), $response->viewData('whatsapp_url'));
        $response->assertHeader('Cache-Control');

        $padre->refresh();
        $this->assertTrue(Hash::check($password, $padre->password));
        $this->assertTrue($padre->password_must_change);
        $this->assertEqualsWithDelta(now()->addHours(24)->timestamp, $padre->temporary_password_expires_at->timestamp, 5);
        $this->assertSame(0, $padre->tokens()->count(), 'Los tokens de la app se revocan.');

        $audit = AuditLog::where('action', 'user.temporary_password')->firstOrFail();
        $this->assertSame($director->id, $audit->user_id);
        $this->assertStringNotContainsString($password, json_encode($audit->details));
    }

    public function test_main_admin_and_secretary_can_generate(): void
    {
        $profesor = $this->makeUser('profesor');
        $this->issue($this->mainAdmin, $profesor)->assertOk();

        $secretaria = $this->makeUser('academic');
        $this->issue($secretaria, $this->makeUser('madre'))->assertOk();
    }

    public function test_other_admins_and_staff_cannot_generate(): void
    {
        $secondAdmin = $this->makeUser('admin');
        $padre = $this->makeUser('padre');

        $this->issue($secondAdmin, $padre)->assertRedirect()->assertSessionHas('error');
        $this->assertFalse($padre->fresh()->password_must_change);

        // El profesor ni siquiera llega a la ruta (rol:admin).
        $this->issue($this->makeUser('profesor'), $padre)->assertRedirect('/');
        $this->assertFalse($padre->fresh()->password_must_change);
    }

    public function test_protected_accounts_respect_each_role(): void
    {
        $director = $this->makeUser('director');
        $secretaria = $this->makeUser('academic');

        $this->issue($director, $this->mainAdmin)->assertSessionHas('error');
        $this->issue($secretaria, $director)->assertSessionHas('error');
        $this->issue($secretaria, $this->mainAdmin)->assertSessionHas('error');
        $this->issue($director, $director)->assertSessionHas('error');

        // El administrador principal sí puede con el director.
        $this->issue($this->mainAdmin, $director)->assertOk();
    }

    public function test_requires_a_valid_whatsapp_number_and_active_account(): void
    {
        $director = $this->makeUser('director');

        $sinNumero = $this->makeUser('padre', ['telefono' => null]);
        $this->issue($director, $sinNumero)->assertSessionHas('error');
        $this->assertFalse($sinNumero->fresh()->password_must_change);

        $suspendido = $this->makeUser('padre', ['estado' => 'inactivo']);
        $this->issue($director, $suspendido)->assertSessionHas('error');
    }

    public function test_whatsapp_number_normalization(): void
    {
        $service = app(TemporaryPasswordService::class);
        $user = new User;

        foreach (['71234567', '7123 4567', '+591 71234567', '00591-71234567', '59171234567'] as $raw) {
            $user->phone = $raw;
            $this->assertSame('59171234567', $service->whatsappNumber($user), $raw);
        }

        $user->phone = '4423456'; // fijo de 7 dígitos: no tiene WhatsApp
        $this->expectException(UserAdminException::class);
        $service->whatsappNumber($user);
    }

    public function test_login_with_temporary_password_forces_the_change(): void
    {
        $padre = $this->makeUser('padre');
        $password = $this->issue($this->mainAdmin, $padre)->viewData('password');
        auth()->logout();

        $this->post('/login', ['email' => $padre->email, 'password' => $password])
            ->assertRedirect(route('password.change'));

        // No puede usar el panel hasta cambiarla.
        $this->get('/dashboard')->assertRedirect(route('password.change'));
        $this->get(route('password.change'))->assertOk()->assertSee('Cambia tu contraseña');

        // No sirve repetir la temporal.
        $this->put(route('password.change.update'), ['password' => $password, 'password_confirmation' => $password])
            ->assertSessionHasErrors('password');

        $this->put(route('password.change.update'), ['password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026'])
            ->assertRedirect(route('dashboard'));

        $padre->refresh();
        $this->assertFalse($padre->password_must_change);
        $this->assertNull($padre->temporary_password_expires_at);
        $this->assertTrue(Hash::check('NuevaClave2026', $padre->password));
        $this->get('/dashboard')->assertOk();
    }

    public function test_expired_temporary_password_cannot_log_in(): void
    {
        $padre = $this->makeUser('padre');
        $password = $this->issue($this->mainAdmin, $padre)->viewData('password');
        auth()->logout();

        $this->travel(25)->hours();

        $this->post('/login', ['email' => $padre->email, 'password' => $password])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_api_login_requires_changing_it_on_the_web_first(): void
    {
        $padre = $this->makeUser('padre');
        $password = $this->issue($this->mainAdmin, $padre)->viewData('password');

        $this->postJson('/api/login', ['email' => $padre->email, 'password' => $password])
            ->assertStatus(403)
            ->assertJsonPath('code', 'password_change_required');
    }

    private function enableWhatsAppApi(string $kind = 'authentication'): void
    {
        config([
            'services.whatsapp.token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.template' => 'yura_clave_temporal',
            'services.whatsapp.template_kind' => $kind,
            'services.whatsapp.api_version' => 'v21.0',
        ]);
    }

    public function test_sends_automatically_from_the_school_number(): void
    {
        $this->enableWhatsAppApi();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ABC123']]], 200)]);
        $padre = $this->makeUser('padre');

        $response = $this->issue($this->makeUser('director'), $padre)->assertOk();
        $password = $response->viewData('password');

        $this->assertTrue($response->viewData('sent'));
        $response->assertSee('Enviada por WhatsApp')->assertDontSee($password);

        Http::assertSent(function (HttpRequest $request) use ($password) {
            return $request->url() === 'https://graph.facebook.com/v21.0/1234567890/messages'
                && $request->hasHeader('Authorization', 'Bearer token-de-prueba')
                && $request['to'] === '59171234567'
                && $request['template']['name'] === 'yura_clave_temporal'
                && $request['template']['components'][0]['parameters'][0]['text'] === $password
                && $request['template']['components'][1]['sub_type'] === 'url';
        });

        $audit = AuditLog::where('action', 'user.temporary_password')->firstOrFail();
        $this->assertSame('api', $audit->details['channel']);
        $this->assertSame('wamid.ABC123', $audit->details['message_id']);
        $this->assertTrue($padre->fresh()->password_must_change);
    }

    public function test_utility_template_sends_the_five_variables(): void
    {
        $this->enableWhatsAppApi('utility');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);
        $padre = $this->makeUser('padre', ['first_name' => 'Mario']);

        $password = $this->issue($this->makeUser('director'), $padre)->viewData('password');

        Http::assertSent(function (HttpRequest $request) use ($password, $padre) {
            $texts = array_column($request['template']['components'][0]['parameters'], 'text');

            return count($request['template']['components']) === 1
                && $texts[0] === 'Mario' && $texts[1] === $password && $texts[3] === $padre->email;
        });
    }

    public function test_when_meta_rejects_it_falls_back_to_the_manual_button(): void
    {
        $this->enableWhatsAppApi();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template not found', 'code' => 132001]], 400)]);
        $padre = $this->makeUser('padre');

        $response = $this->issue($this->makeUser('director'), $padre)->assertOk();
        $password = $response->viewData('password');

        $this->assertFalse($response->viewData('sent'));
        $response->assertSee('Template not found')->assertSee($password)->assertSee('wa.me/59171234567', false);
        $this->assertSame('manual', AuditLog::where('action', 'user.temporary_password')->firstOrFail()->details['channel']);
    }

    public function test_without_configuration_it_does_not_call_meta(): void
    {
        Http::fake();
        $this->issue($this->makeUser('director'), $this->makeUser('padre'))->assertOk();
        Http::assertNothingSent();
    }

    public function test_users_list_shows_the_key_only_to_allowed_roles(): void
    {
        $padre = $this->makeUser('padre');
        $label = 'Contraseña temporal por WhatsApp para ' . $padre->name;

        $this->actingAs($this->makeUser('director'))->get(route('users.index'))->assertSee($label, false);
        $this->actingAs($this->makeUser('admin'))->get(route('users.index'))->assertDontSee($label, false);
    }
}
