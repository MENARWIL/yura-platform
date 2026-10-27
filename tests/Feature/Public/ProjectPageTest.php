<?php

namespace Tests\Feature\Public;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPageTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> Archivos de foto que esta prueba creó (se borran siempre). */
    private array $createdPhotos = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPhotos as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    private function photoPath(int $n): string
    {
        return public_path("img/robot-yura-{$n}.jpg");
    }

    private function anyPhotoExists(): bool
    {
        return is_file($this->photoPath(1)) || is_file($this->photoPath(2)) || is_file($this->photoPath(3));
    }

    private function mainHtml(string $html): string
    {
        $this->assertSame(1, preg_match('/<main\b.*<\/main>/is', $html, $main), 'No se encontró <main> en la página.');

        return $main[0];
    }

    private function stats(bool $show, int $students = 40): array
    {
        return ['students' => $students, 'teachers' => 3, 'activities' => 12, 'grades' => 480, 'show' => $show];
    }

    public function test_project_page_responds_ok_with_product_content(): void
    {
        $this->get('/feria')
            ->assertOk()
            ->assertSee('YURA: el robot que escucha y acompaña la práctica del quechua en primaria')
            ->assertSee('El quechua se aprende hablándolo')
            ->assertSee('Cómo funciona, paso a paso')
            ->assertSee('Qué gana cada quien')
            ->assertSee('Lo que incluye hoy')
            ->assertSee('Preguntas frecuentes')
            ->assertSee('Lleva YURA a tu colegio');
    }

    public function test_route_name_feria_is_kept(): void
    {
        $this->assertSame(url('/feria'), route('feria'));
    }

    public function test_page_has_a_single_h1(): void
    {
        $html = $this->get('/feria')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<h1[\s>]/i', $html), 'La página debe tener un solo <h1>.');
    }

    public function test_page_does_not_contain_internal_report_language_or_fake_figures(): void
    {
        $this->get('/feria')
            ->assertOk()
            ->assertDontSee('Estado del proyecto para la feria')
            ->assertDontSee('Próximo paso recomendado')
            ->assertDontSee('Plataforma real para feria')
            ->assertDontSee('listo para feria')
            ->assertDontSee('+12');

        // «100%» puede aparecer en el CSS; en el texto de la página no.
        $text = preg_replace('/<style\b.*?<\/style>/is', '', $this->mainHtml($this->get('/feria')->getContent()));
        $this->assertStringNotContainsString('100%', $text);
    }

    public function test_page_uses_the_public_layout(): void
    {
        $this->get('/feria')
            ->assertOk()
            ->assertSee('<title>Proyecto YURA | Robot que practica quechua y cuaderno digital</title>', false)
            ->assertSee('class="main-header"', false);
    }

    public function test_page_declares_its_meta_description(): void
    {
        $this->get('/feria')
            ->assertOk()
            ->assertSee(__('project.meta.description'));
    }

    public function test_hero_links_to_login_and_to_the_faq(): void
    {
        $this->get('/feria')
            ->assertOk()
            ->assertSee('Ingresar')
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="#preguntas"', false);
    }

    public function test_logged_in_user_sees_a_panel_button_instead_of_login(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'activo']);

        $this->actingAs($user)
            ->get('/feria')
            ->assertOk()
            ->assertSee('Ir a mi panel');
    }

    public function test_faq_uses_accessible_details_and_summary_without_script(): void
    {
        $html = $this->get('/feria')->assertOk()->getContent();

        $details = preg_match_all('/<details[\s>]/i', $html);
        $summaries = preg_match_all('/<summary[\s>]/i', $html);

        $this->assertGreaterThanOrEqual(6, $details);
        $this->assertLessThanOrEqual(8, $details);
        $this->assertSame($details, $summaries);

        // Dentro del contenido principal (el layout puede traer sus propios scripts).
        $this->assertStringNotContainsString('<script', $this->mainHtml($html), 'La página no debería necesitar JavaScript propio.');
    }

    public function test_faq_answers_only_what_the_system_confirms_about_audio(): void
    {
        $this->get('/feria')
            ->assertOk()
            ->assertSee('La plataforma no recibe audios');
    }

    public function test_gallery_is_hidden_when_there_are_no_photo_files_or_video(): void
    {
        if ($this->anyPhotoExists()) {
            $this->markTestSkipped('Ya existen fotos reales del robot en public/img; la galería se mostrará.');
        }

        config(['yura.video_url' => null]);

        $this->get('/feria')
            ->assertOk()
            ->assertDontSee('id="galeria"', false)
            ->assertDontSee('robot-yura-1.jpg')
            ->assertDontSee('Mira a YURA');
    }

    public function test_gallery_shows_only_the_photos_that_exist(): void
    {
        if ($this->anyPhotoExists()) {
            $this->markTestSkipped('Ya existen fotos reales del robot en public/img; no se tocan.');
        }

        $path = $this->photoPath(2);
        $this->createdPhotos[] = $path;
        file_put_contents($path, 'imagen de prueba');

        config(['yura.video_url' => null]);

        $this->get('/feria')
            ->assertOk()
            ->assertSee('id="galeria"', false)
            ->assertSee('robot-yura-2.jpg')
            ->assertDontSee('robot-yura-1.jpg')
            ->assertDontSee('robot-yura-3.jpg')
            ->assertSee('Fotografía 2 del robot YURA');
    }

    public function test_video_block_follows_the_configured_url(): void
    {
        if ($this->anyPhotoExists()) {
            $this->markTestSkipped('Ya existen fotos reales del robot en public/img.');
        }

        config(['yura.video_url' => 'https://videos.example.org/yura']);
        $this->get('/feria')
            ->assertOk()
            ->assertSee('id="galeria"', false)
            ->assertSee('href="https://videos.example.org/yura"', false)
            ->assertSee('Ver el video');

        config(['yura.video_url' => 'https://videos.example.org/yura.mp4']);
        $this->get('/feria')
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee('https://videos.example.org/yura.mp4');

        config(['yura.video_url' => 'javascript:alert(1)']);
        $this->get('/feria')
            ->assertOk()
            ->assertDontSee('id="galeria"', false)
            ->assertDontSee('javascript:alert(1)');
    }

    public function test_stats_block_is_hidden_when_the_service_says_not_to_show_it(): void
    {
        $this->get('/feria')
            ->assertOk()
            ->assertDontSee('YURA en cifras');

        app()->setLocale('es');
        $this->view('home', ['stats' => $this->stats(show: false)])
            ->assertDontSee('YURA en cifras');
    }

    public function test_stats_block_is_shown_with_real_counts_when_allowed(): void
    {
        app()->setLocale('es');
        $this->view('home', ['stats' => $this->stats(show: true)])
            ->assertSee('YURA en cifras')
            ->assertSee('Estudiantes')
            ->assertSee('Notas registradas')
            ->assertSee('480');
    }

    public function test_stats_block_skips_figures_that_are_zero(): void
    {
        app()->setLocale('es');
        $stats = ['students' => 25, 'teachers' => 0, 'activities' => 0, 'grades' => 0, 'show' => true];

        $this->view('home', ['stats' => $stats])
            ->assertSee('YURA en cifras')
            ->assertSee('Estudiantes')
            ->assertDontSee('<span>Docentes</span>', false)
            ->assertDontSee('Notas registradas');
    }

    public function test_app_download_link_only_appears_when_configured(): void
    {
        config(['yura.app_url' => null]);
        $this->get('/feria')->assertOk()->assertDontSee('Descargar la app');

        config(['yura.app_url' => 'https://descargas.example.org/yura.apk']);
        $this->get('/feria')
            ->assertOk()
            ->assertSee('Descargar la app')
            ->assertSee('href="https://descargas.example.org/yura.apk"', false);
    }

    public function test_page_responds_in_quechua(): void
    {
        $this->withSession(['locale' => 'qu'])
            ->get('/feria')
            ->assertOk()
            ->assertSee('lang="qu"', false)
            ->assertSee('Runasimiqa rimaspa yachakun')
            ->assertSee('Sapa runapaq ima allin')
            ->assertDontSee('El quechua se aprende hablándolo');
    }

    public function test_spanish_and_quechua_files_have_the_same_keys(): void
    {
        $es = $this->flatKeys(require lang_path('es/project.php'));
        $qu = $this->flatKeys(require lang_path('qu/project.php'));

        $this->assertNotEmpty($es);
        $this->assertSame([], array_values(array_diff($es, $qu)), 'Faltan claves en qu/project.php');
        $this->assertSame([], array_values(array_diff($qu, $es)), 'Sobran claves en qu/project.php');
    }

    public function test_quechua_file_carries_the_review_comment(): void
    {
        $this->assertStringContainsString(
            'REVISAR: traducción pendiente de revisión por hablante nativo',
            file_get_contents(lang_path('qu/project.php'))
        );
    }

    /** @return array<int, string> */
    private function flatKeys(array $array, string $prefix = ''): array
    {
        $keys = [];
        foreach ($array as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                $keys = array_merge($keys, $this->flatKeys($value, $path));
            } else {
                $keys[] = $path;
            }
        }

        sort($keys);

        return $keys;
    }
}
