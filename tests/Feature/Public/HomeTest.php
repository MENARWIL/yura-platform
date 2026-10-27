<?php

namespace Tests\Feature\Public;

use App\Models\Course;
use App\Models\EducationalService;
use App\Models\OfficialDocument;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** Portada pública: mensaje claro, un solo h1, botón Ingresar, cifras solo con datos suficientes y dos idiomas. */
class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['yura.stats_min' => 10, 'yura.app_url' => null]);
    }

    private function students(int $count): void
    {
        $course = Course::firstOrCreate(['name' => 'Sexto de Primaria']);
        $parallel = Parallel::firstOrCreate(['course_id' => $course->id, 'name' => 'A'], ['max_students' => 60]);

        for ($i = 1; $i <= $count; $i++) {
            Student::create([
                'name' => "Estudiante {$i}",
                'course' => $course->name,
                'course_id' => $course->id,
                'parallel_id' => $parallel->id,
                'status' => 'activo',
            ]);
        }
    }

    public function test_home_responds_ok_and_explains_what_yura_is(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('un robot que practica quechua con niñas y niños')
            ->assertSee('cuaderno digital para docentes y familias')
            ->assertSee('Conocer el proyecto')
            ->assertSee('Familias')
            ->assertSee('Docentes y directivos')
            ->assertSee('Jurado, prensa y aliados')
            ->assertSee('Cómo funciona, en tres pasos');
    }

    public function test_home_has_exactly_one_h1(): void
    {
        $html = $this->get(route('public.home'))->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<h1[\s>]/i', $html), 'la portada debe tener un solo <h1>');
    }

    public function test_login_button_says_ingresar_and_points_to_login(): void
    {
        $html = $this->get(route('public.home'))->assertOk()->getContent();
        $login = preg_quote(route('login'), '#');

        $this->assertMatchesRegularExpression(
            '#<a href="' . $login . '" class="btn-portal btn-portal-light btn-portal-lg">\s*<i[^>]*></i>\s*Ingresar\s*</a>#',
            $html
        );
        $this->assertMatchesRegularExpression(
            '#<a href="' . preg_quote(route('feria'), '#') . '" class="btn-portal btn-portal-ghost-light btn-portal-lg">\s*Conocer el proyecto#',
            $html
        );
    }

    public function test_search_is_a_small_secondary_form_that_still_works(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('action="' . route('public.search') . '"', false)
            ->assertSee('name="q"', false);
    }

    public function test_it_does_not_use_the_old_ministry_tone(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('Sistema Educativo Integrado')
            ->assertDontSee('Gestión Educativa, Seguimiento Académico y Servicios Institucionales');
    }

    public function test_stats_block_is_hidden_with_little_data(): void
    {
        $this->students(3);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('YURA en cifras')
            ->assertDontSee('Notas registradas');
    }

    public function test_stats_block_shows_real_figures_with_enough_data(): void
    {
        $this->students(12);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('YURA en cifras')
            ->assertSee('<strong>12</strong>', false)
            ->assertSee('Estudiantes')
            // Sin docentes, actividades ni notas reales no se muestran esas cifras en cero.
            ->assertDontSee('Actividades con el robot')
            ->assertDontSee('Notas registradas');
    }

    public function test_stat_labels_use_the_singular_for_one(): void
    {
        $this->students(10);
        User::factory()->create(['role' => 'profesor', 'status' => 'activo']);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('<span>Docente</span>', false)
            ->assertSee('<span>Estudiantes</span>', false);
    }

    public function test_stats_never_show_personal_data(): void
    {
        $this->students(11);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('Estudiante 1')
            ->assertDontSee('Estudiante 11');
    }

    public function test_the_stats_block_follows_the_configured_minimum(): void
    {
        $this->students(4);
        config(['yura.stats_min' => 4]);

        $this->get(route('public.home'))->assertOk()->assertSee('YURA en cifras');
    }

    public function test_app_block_only_appears_when_there_is_a_download_link(): void
    {
        $this->get(route('public.home'))->assertOk()->assertDontSee('Descargar la app');

        config(['yura.app_url' => 'https://example.test/yura.apk']);
        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Descargar la app')
            ->assertSee('href="https://example.test/yura.apk"', false);

        // Un enlace que no es http(s) ni una ruta del sitio se ignora.
        config(['yura.app_url' => 'javascript:alert(1)']);
        $this->get(route('public.home'))->assertOk()->assertDontSee('Descargar la app');
    }

    public function test_services_and_documents_blocks_are_hidden_when_empty(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('Lo que ofrece la plataforma')
            ->assertDontSee('Avisos y documentos recientes');
    }

    public function test_services_and_documents_blocks_show_when_there_is_data(): void
    {
        EducationalService::create([
            'name' => 'Cuaderno Pedagógico Digital',
            'subsystem' => 'regular',
            'description' => 'Registro de evaluaciones y asistencia.',
            'icon' => 'fa-book-open',
            'action_url' => '/grades',
            'target_audience' => 'Docentes de Educación Regular',
            'active' => true,
            'sort_order' => 1,
        ]);
        OfficialDocument::create([
            'title' => 'Circular de ejemplo para la portada',
            'code' => 'CIRC-001/2026',
            'category' => 'circular',
            'summary' => 'Resumen.',
            'year' => 2026,
            'published_at' => now()->subDay()->toDateString(),
            'active' => true,
        ]);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Lo que ofrece la plataforma')
            ->assertSee('Cuaderno Pedagógico Digital')
            ->assertSee('Docentes de Educación Regular')
            ->assertSee('Avisos y documentos recientes')
            ->assertSee('Circular de ejemplo para la portada')
            ->assertSee(route('public.documents', ['q' => 'CIRC-001/2026']), false);
    }

    public function test_robot_box_uses_an_icon_until_there_is_a_real_photo(): void
    {
        if (file_exists(public_path('img/robot-yura.jpg'))) {
            $this->markTestSkipped('Ya existe public/img/robot-yura.jpg: la portada muestra la foto real.');
        }

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('fa-robot', false)
            ->assertDontSee('robot-yura.jpg');
    }

    public function test_home_works_in_quechua(): void
    {
        $this->withSession(['locale' => 'qu'])
            ->get(route('public.home'))
            ->assertOk()
            ->assertSee('lang="qu"', false)
            ->assertSee('YURA, wawakunawan qhichwa simipi parlaq robot')
            ->assertSee('Yaykuy')
            ->assertSee('Proyectota riqsiy')
            ->assertDontSee('home.hero_title_1');

        $html = $this->withSession(['locale' => 'qu'])->get(route('public.home'))->getContent();
        $this->assertSame(1, preg_match_all('/<h1[\s>]/i', $html));
    }

    public function test_spanish_and_quechua_files_have_the_same_keys(): void
    {
        $es = require resource_path('lang/es/home.php');
        $qu = require resource_path('lang/qu/home.php');

        $this->assertSame(array_keys($es), array_keys($qu), 'es y qu deben tener las mismas claves, en el mismo orden');
        $this->assertNotEmpty($es);

        foreach ($qu as $key => $text) {
            $this->assertNotSame('', trim($text), "la clave qu '{$key}' está vacía");
        }

        $this->assertStringContainsString(
            '// REVISAR: traducción pendiente de revisión por hablante nativo',
            file_get_contents(resource_path('lang/qu/home.php'))
        );
    }

    public function test_every_home_key_used_by_the_view_exists(): void
    {
        $es = require resource_path('lang/es/home.php');
        $view = file_get_contents(resource_path('views/public/home.blade.php'));

        // Solo claves completas: las armadas con un prefijo se comprueban abajo.
        preg_match_all("/__\('home\.([a-z0-9_]+)'[,)]/", $view, $direct);
        foreach (array_unique($direct[1]) as $key) {
            $this->assertArrayHasKey($key, $es, "la vista usa home.{$key} y no existe en es/home.php");
        }

        // Claves armadas con un prefijo (caminos, pasos y cifras).
        foreach (['family', 'teacher', 'press'] as $path) {
            foreach (['title', 'text', 'cta'] as $part) {
                $this->assertArrayHasKey("{$path}_{$part}", $es);
            }
        }
        foreach ([1, 2, 3] as $step) {
            $this->assertArrayHasKey("step_{$step}_title", $es);
            $this->assertArrayHasKey("step_{$step}_text", $es);
        }
        foreach (['students', 'teachers', 'activities', 'grades'] as $stat) {
            $this->assertArrayHasKey("stat_{$stat}", $es);
        }
    }
}
