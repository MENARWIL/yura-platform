<?php

namespace Tests\Feature\Public;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Pantalla de ingreso (marca, accesibilidad, ayuda) y página de Privacidad (borrador honesto).
 * La lógica de autenticación no cambia: aquí solo se comprueba que sigue funcionando.
 */
class LoginPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'yura.contact.whatsapp' => null,
            'yura.contact.email' => null,
            'yura.contact.address' => null,
            'yura.contact.hours' => null,
            'yura.org_name' => null,
        ]);
    }

    private function h1Count(string $html): int
    {
        return preg_match_all('/<h1[\s>]/i', $html);
    }

    // ---------------------------------------------------------------- Login

    public function test_login_page_is_branded_accessible_and_has_one_h1_and_link_home(): void
    {
        $response = $this->get('/login')->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, $this->h1Count($html));
        $response->assertSee('<html lang="es">', false);
        $response->assertSee('<title>Ingresar | YURA Platform</title>', false);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('favicon.svg', false);
        $response->assertSee('Ingresa para ver las notas, la asistencia y el cuaderno pedagógico');

        // Enlace para volver al inicio y a la privacidad.
        $response->assertSee('href="' . route('public.home') . '"', false);
        $response->assertSee('href="' . route('public.privacy') . '"', false);

        // Campos con etiqueta visible y autocompletado correcto.
        $response->assertSee('<label for="email">Correo electrónico</label>', false);
        $response->assertSee('<label for="password">Contraseña</label>', false);
        $response->assertSee('autocomplete="username"', false);
        $response->assertSee('autocomplete="current-password"', false);

        // Botón principal y botón de mostrar contraseña accesible.
        $response->assertSee('btn-portal-primary btn-portal-lg', false);
        $response->assertSee('Ingresar');
        $response->assertSee('aria-controls="password"', false);
        $response->assertSee('aria-pressed="false"', false);

        // Autónoma: sin AdminLTE, jQuery ni CDN de scripts.
        $response->assertDontSee('adminlte', false);
        $response->assertDontSee('jquery', false);
        $response->assertDontSee('cdnjs', false);
        $response->assertDontSee('unpkg', false);
        $response->assertDontSee('jsdelivr', false);
    }

    public function test_login_page_has_no_example_credentials_on_screen(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringNotContainsString('admin@', $html);
        $this->assertStringNotContainsString('password123', $html);
        $this->assertStringNotContainsString('nombre@ejemplo.com', $html);
        $this->assertSame(1, preg_match_all('/<input[^>]+type="password"[^>]*>/', $html));
        $this->assertDoesNotMatchRegularExpression('/type="password"[^>]*value=/', $html);
    }

    public function test_login_without_recovery_route_shows_help_text_and_no_link(): void
    {
        $this->assertFalse(Route::has('password.request'), 'Si se agrega la recuperación de contraseña, actualiza esta prueba.');

        $response = $this->get('/login')->assertOk();

        $response->assertSee('Si olvidaste tu contraseña, pide a tu docente o administrador que la restablezca.');
        $response->assertSee('Si aún no tienes acceso, pídelo en tu unidad educativa.');
        $response->assertDontSee('¿Olvidaste tu contraseña?');
        $response->assertDontSee('href="#"', false);
    }

    public function test_login_links_recovery_only_when_the_route_exists(): void
    {
        Route::get('/recuperar-clave-prueba', fn () => 'ok')->name('password.request');
        app('router')->getRoutes()->refreshNameLookups();

        $response = $this->get('/login')->assertOk();

        $response->assertSee('¿Olvidaste tu contraseña?');
        $response->assertSee('href="' . route('password.request') . '"', false);
        $response->assertDontSee('pide a tu docente o administrador que la restablezca');
    }

    public function test_whatsapp_button_appears_only_when_configured(): void
    {
        $this->get('/login')->assertOk()
            ->assertDontSee('wa.me')
            ->assertDontSee('Escribir por WhatsApp');

        config(['yura.contact.whatsapp' => '+591 7123-4567']);

        $this->get('/login')->assertOk()
            ->assertSee('https://wa.me/59171234567', false)
            ->assertSee('Escribir por WhatsApp')
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_valid_login_still_works(): void
    {
        $user = User::factory()->create([
            'email' => 'familia@example.test',
            'password' => Hash::make('clave-segura-123'),
            'role' => 'padre',
            'status' => 'activo',
        ]);

        $response = $this->post('/login', [
            'email' => 'familia@example.test',
            'password' => 'clave-segura-123',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('dashboard', (string) $response->headers->get('Location'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_shows_a_clear_accessible_error_and_keeps_the_email(): void
    {
        User::factory()->create([
            'email' => 'familia@example.test',
            'password' => Hash::make('clave-segura-123'),
            'role' => 'padre',
            'status' => 'activo',
        ]);

        $response = $this->followingRedirects()->from('/login')->post('/login', [
            'email' => 'familia@example.test',
            'password' => 'otra-clave',
        ]);

        $this->assertGuest();
        $response->assertOk();
        $response->assertSee('role="alert"', false);
        $response->assertSee('No pudimos ingresar');
        $response->assertSee('El correo o la contraseña no son correctos.');
        $response->assertSee('aria-describedby="login-error"', false);
        $response->assertSee('aria-invalid="true"', false);
        $response->assertSee('value="familia@example.test"', false);
        $response->assertDontSee('otra-clave');
        $this->assertSame(1, $this->h1Count($response->getContent()));
    }

    public function test_suspended_account_gets_a_clear_message(): void
    {
        User::factory()->create([
            'email' => 'suspendido@example.test',
            'password' => Hash::make('clave-segura-123'),
            'role' => 'padre',
            'status' => 'inactivo',
        ]);

        $this->followingRedirects()->from('/login')->post('/login', [
            'email' => 'suspendido@example.test',
            'password' => 'clave-segura-123',
        ])->assertSee('Tu cuenta está suspendida');
    }

    public function test_validation_errors_are_shown_in_the_page_language(): void
    {
        $response = $this->followingRedirects()->from('/login')->post('/login', []);

        $response->assertOk();
        $response->assertSee('Escribe tu correo electrónico y tu contraseña para ingresar.');
        $response->assertDontSee('field is required');
    }

    public function test_login_page_works_in_quechua(): void
    {
        $response = $this->withSession(['locale' => 'qu'])->get('/login')->assertOk();

        $response->assertSee('<html lang="qu">', false);
        $response->assertSee('YURAman yaykuy');
        $response->assertSee('Qallariyman kutiy');
        $response->assertDontSee('Volver al inicio');
        $this->assertSame(1, $this->h1Count($response->getContent()));
    }

    public function test_login_language_links_switch_the_locale(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee(route('lang.set', ['locale' => 'es']), false)
            ->assertSee(route('lang.set', ['locale' => 'qu']), false);

        $this->from('/login')->get(route('lang.set', ['locale' => 'qu']))->assertRedirect();
        $this->get('/login')->assertSee('YURAman yaykuy');
    }

    // ------------------------------------------------------------ Privacidad

    public function test_privacy_page_responds_with_one_h1_and_the_draft_notice(): void
    {
        $response = $this->get(route('public.privacy'))->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, $this->h1Count($html));
        $response->assertSee('Cómo cuidamos los datos en YURA');
        $response->assertSee('Borrador pendiente de revisión por la unidad educativa y asesoría legal');
        $response->assertSee('role="note"', false);
        $response->assertSee('PENDIENTE');
        $response->assertSee('Última actualización del borrador: 25 de octubre de 2026');

        // SEO: título y meta descripción propios.
        $this->assertMatchesRegularExpression('/<title>[^<]*Privacidad[^<]*<\/title>/u', $html);
        $this->assertMatchesRegularExpression('/<meta name="description" content="[^"]*datos[^"]*"/u', $html);
    }

    public function test_privacy_page_explains_data_roles_retention_and_how_to_ask_for_changes(): void
    {
        $this->get(route('public.privacy'))->assertOk()
            ->assertSee('Qué datos guarda la plataforma')
            ->assertSee('Notas y asistencia')
            ->assertSee('Actividades con el robot')
            ->assertSee('Quién puede ver los datos')
            ->assertSee('Familias (padre, madre o tutor)')
            ->assertSee('Para qué se usan')
            ->assertSee('Cuánto tiempo se conservan')
            ->assertSee('Cómo pedir ver, corregir o eliminar datos')
            ->assertSee('la unidad educativa y el equipo del proyecto YURA');
    }

    public function test_privacy_page_does_not_claim_legal_compliance(): void
    {
        $html = mb_strtolower(strip_tags($this->get(route('public.privacy'))->assertOk()->getContent()));

        foreach (['cumple con', 'cumplimos', 'en cumplimiento', 'conforme a la ley', 'gdpr', 'rgpd', 'ley 164', 'ley n', 'certificad', '100% seguro', 'totalmente seguro'] as $claim) {
            $this->assertStringNotContainsString($claim, $html, "La privacidad no debe afirmar: {$claim}");
        }
    }

    public function test_privacy_page_does_not_invent_contact_data_and_shows_configured_data(): void
    {
        $this->get(route('public.privacy'))->assertOk()
            ->assertDontSee('mailto:')
            ->assertDontSee('wa.me')
            ->assertSee('Pregunta a tu docente o en la dirección de tu unidad educativa.');

        config([
            'yura.org_name' => 'Unidad Educativa de Prueba',
            'yura.contact.email' => 'contacto@ejemplo.test',
            'yura.contact.whatsapp' => '59171234567',
            'yura.contact.address' => 'Calle Falsa 123',
            'yura.contact.hours' => 'Lunes a viernes 8:00 a 12:00',
        ]);

        $this->get(route('public.privacy'))->assertOk()
            ->assertSee('Unidad Educativa de Prueba')
            ->assertSee('mailto:contacto@ejemplo.test', false)
            ->assertSee('https://wa.me/59171234567', false)
            ->assertSee('Calle Falsa 123')
            ->assertSee('Lunes a viernes 8:00 a 12:00')
            ->assertDontSee('Nombre oficial de la unidad educativa y del equipo responsable.<');
    }

    public function test_privacy_page_works_in_quechua(): void
    {
        $response = $this->withSession(['locale' => 'qu'])->get(route('public.privacy'))->assertOk();

        $response->assertSee('lang="qu"', false);
        $response->assertSee('Imaynatam willakuykunata waqaychanchik YURApi');
        $response->assertSee('Borrador:');
        $response->assertSee('25 p\'unchaw octubre killapi, 2026');
        $response->assertDontSee('Cómo cuidamos los datos en YURA');
        $this->assertSame(1, $this->h1Count($response->getContent()));
    }

    public function test_spanish_and_quechua_files_have_the_same_keys(): void
    {
        foreach (['auth_public', 'privacy'] as $file) {
            $es = array_keys(Arr::dot(require resource_path("lang/es/{$file}.php")));
            $qu = array_keys(Arr::dot(require resource_path("lang/qu/{$file}.php")));

            $this->assertEqualsCanonicalizing($es, $qu, "Las claves de {$file}.php difieren entre es y qu.");
            $this->assertStringContainsString(
                'REVISAR: traducción pendiente de revisión por hablante nativo',
                file_get_contents(resource_path("lang/qu/{$file}.php"))
            );
        }
    }
}
