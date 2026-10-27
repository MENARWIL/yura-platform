<?php

namespace Tests\Feature\Public;

use App\Models\EducationalService;
use App\Models\OfficialDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * Páginas públicas de servicios, documentos y búsqueda:
 * solo se muestra lo que tiene valor real, con estados vacíos útiles y textos humanos.
 */
class PortalPagesTest extends TestCase
{
    use RefreshDatabase;

    private function service(array $attributes = []): EducationalService
    {
        static $n = 0;
        $n++;

        return EducationalService::create(array_merge([
            'name' => "Servicio de prueba {$n}",
            'subsystem' => 'regular',
            'description' => "Descripción del servicio {$n}.",
            'icon' => 'fa-book-open',
            'action_url' => '/login',
            'target_audience' => 'Docentes',
            'active' => true,
            'sort_order' => $n,
        ], $attributes));
    }

    private function document(array $attributes = []): OfficialDocument
    {
        static $n = 0;
        $n++;

        return OfficialDocument::create(array_merge([
            'title' => "Comunicado de prueba {$n}",
            'code' => "COM-{$n}/2026",
            'category' => 'comunicado',
            'summary' => "Resumen del comunicado {$n}.",
            'year' => 2026,
            'file_path' => null,
            'published_at' => now()->subDays(3)->toDateString(),
            'active' => true,
        ], $attributes));
    }

    // ---------------------------------------------------------------- servicios

    public function test_services_without_real_link_are_not_listed_in_services_or_home(): void
    {
        $this->service(['name' => 'Servicio Bueno Interno', 'action_url' => '/login']);
        $this->service(['name' => 'Servicio Bueno Externo', 'action_url' => 'https://example.org/yura']);

        $this->service(['name' => 'Servicio Con Almohadilla', 'action_url' => '#']);
        $this->service(['name' => 'Servicio Con Ancla', 'action_url' => '#seccion']);
        $this->service(['name' => 'Servicio Sin Enlace', 'action_url' => null]);
        $this->service(['name' => 'Servicio Enlace Vacio', 'action_url' => '']);
        $this->service(['name' => 'Servicio Enlace Espacios', 'action_url' => '   ']);
        $this->service(['name' => 'Servicio Javascript', 'action_url' => 'javascript:alert(1)']);
        $this->service(['name' => 'Servicio Ruta Inexistente', 'action_url' => '/modulo-que-ya-no-existe']);
        $this->service(['name' => 'Servicio Ruta Inexistente Con Query', 'action_url' => '/procedures?x=1']);
        $this->service(['name' => 'Servicio Inactivo', 'action_url' => '/login', 'active' => false]);

        $hidden = [
            'Servicio Con Almohadilla', 'Servicio Con Ancla', 'Servicio Sin Enlace', 'Servicio Enlace Vacio',
            'Servicio Enlace Espacios', 'Servicio Javascript', 'Servicio Ruta Inexistente',
            'Servicio Ruta Inexistente Con Query', 'Servicio Inactivo',
        ];

        foreach (['/services', '/'] as $url) {
            $response = $this->get($url)->assertOk();
            $response->assertSee('Servicio Bueno Interno')->assertSee('Servicio Bueno Externo');
            foreach ($hidden as $name) {
                $response->assertDontSee($name);
            }
        }
    }

    public function test_home_receives_flat_limited_services_documents_and_stats(): void
    {
        foreach (range(1, 8) as $i) {
            $this->service(['name' => "Servicio numero {$i}"]);
        }
        $this->service(['name' => 'Servicio Roto', 'action_url' => '#', 'sort_order' => 0]);
        $this->document();

        $response = $this->get('/')->assertOk();

        $services = $response->viewData('services');
        $this->assertCount(6, $services);
        $this->assertTrue($services->every(fn ($s) => $s->hasWorkingLink()));
        $this->assertSame(range(0, 5), $services->keys()->all(), 'La portada recibe una colección plana, no agrupada.');
        $response->assertDontSee('Servicio Roto');

        $this->assertCount(1, $response->viewData('recentDocuments'));
        $this->assertEqualsCanonicalizing(
            ['students', 'teachers', 'activities', 'grades', 'show'],
            array_keys($response->viewData('stats'))
        );
    }

    public function test_services_page_shows_human_subsystem_names_and_no_technical_keys(): void
    {
        $this->service(['name' => 'Servicio Alternativo', 'subsystem' => 'alternativa_especial']);
        $this->service(['name' => 'Servicio Superior', 'subsystem' => 'superior']);
        $this->service(['name' => 'Servicio Raro', 'subsystem' => 'otra_categoria_nueva']);

        foreach (['/services', '/'] as $url) {
            $response = $this->get($url)->assertOk();
            $response->assertDontSee('ALTERNATIVA_ESPECIAL');
            $response->assertDontSee('>alternativa_especial<', false);
            $response->assertDontSee('otra_categoria_nueva</', false);
        }

        $this->get('/services')
            ->assertSee('Alternativa y Especial')
            ->assertSee('Educación Superior')
            ->assertSee('Otra Categoria Nueva'); // clave desconocida: se vuelve texto legible

        $this->assertSame('Alternativa y Especial', EducationalService::subsystemLabel('alternativa_especial'));
        $this->assertSame('Educación Regular', $this->service()->subsystem_label);
    }

    public function test_services_filter_tabs_only_offer_subsystems_with_services(): void
    {
        $this->service(['subsystem' => 'regular']);
        $this->service(['subsystem' => 'superior', 'name' => 'Servicio Superior']);
        $this->service(['subsystem' => 'alternativa_especial', 'name' => 'Servicio Oculto', 'action_url' => '#']);

        $html = $this->get('/services')->assertOk()->getContent();
        $this->assertSame(1, preg_match('#<nav class="pp-tabs".*?</nav>#s', $html, $tabs), 'Debe haber pestañas de subsistema.');
        // El layout puede enlazar otros subsistemas; solo se revisan las pestañas de la página
        $this->assertStringContainsString('subsystem=superior', $tabs[0]);
        $this->assertStringNotContainsString('subsystem=alternativa_especial', $tabs[0]);

        $this->get('/services?subsystem=superior')->assertOk()
            ->assertSee('Servicio Superior')
            ->assertDontSee('Servicio Oculto');
    }

    public function test_services_filter_tabs_are_hidden_when_there_is_one_subsystem(): void
    {
        $this->service(['subsystem' => 'regular']);

        $this->get('/services')->assertOk()->assertDontSee('class="pp-tabs"', false);
    }

    public function test_services_empty_state_has_a_next_step(): void
    {
        $response = $this->get('/services')->assertOk();
        $response->assertSee('Estamos preparando los servicios');
        $response->assertSee(route('feria'), false);
        $response->assertSee('Conocer el proyecto YURA');

        // Solo hay servicios rotos: sigue viéndose el estado vacío
        $this->service(['action_url' => '#']);
        $this->get('/services')->assertSee('Estamos preparando los servicios');
    }

    public function test_services_empty_subsystem_state_links_back_to_all_services(): void
    {
        $this->service();

        $this->get('/services?subsystem=no_existe')->assertOk()
            ->assertSee('No hay servicios en esta categoría')
            ->assertSee('Ver todos los servicios');

        // Parámetros raros no rompen la página
        $this->get('/services?subsystem[]=x')->assertOk();
        $this->get('/services?subsystem=' . str_repeat('a', 300))->assertOk();
    }

    public function test_internal_service_link_resolves_with_url_helper_and_guest_hint(): void
    {
        $service = $this->service(['action_url' => '/login']);

        $this->assertSame(url('/login'), $service->link_url);
        $this->assertTrue($service->is_internal_link);

        $this->get('/services')->assertSee('href="' . url('/login') . '"', false)
            ->assertSee('Necesitas una cuenta de tu colegio para usarlo.');
    }

    public function test_service_scope_and_link_checks_are_generic(): void
    {
        $ok = $this->service(['action_url' => '/login']);
        $ext = $this->service(['action_url' => 'https://example.org/x']);
        $sameHostMissing = $this->service(['action_url' => rtrim(config('app.url'), '/') . '/ruta-inexistente']);
        $postOnly = $this->service(['action_url' => '/logout']);
        $protocolRelative = $this->service(['action_url' => '//malicioso.example/x']);
        $this->service(['action_url' => '#']);
        $this->service(['action_url' => null]);

        $visible = EducationalService::publicVisible()->pluck('id')->all();
        $this->assertContains($ok->id, $visible);
        $this->assertContains($ext->id, $visible);
        $this->assertCount(5, $visible); // '#' y null quedan fuera ya en SQL

        $this->assertTrue($ok->hasWorkingLink());
        $this->assertTrue($ext->hasWorkingLink());
        $this->assertFalse($sameHostMissing->hasWorkingLink());
        $this->assertFalse($postOnly->hasWorkingLink(), 'Una ruta que solo existe como POST no sirve de enlace.');
        $this->assertFalse($protocolRelative->hasWorkingLink());

        $listed = EducationalService::publicList()->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$ok->id, $ext->id], $listed);

        // El scope active() de las pantallas internas no cambia
        $this->assertCount(7, EducationalService::active()->get());
    }

    // ---------------------------------------------------------------- documentos

    public function test_documents_page_lists_documents_with_honest_language(): void
    {
        $this->document([
            'title' => 'Calendario del segundo trimestre',
            'code' => 'CIRC-015/2026',
            'category' => 'circular',
        ]);

        $response = $this->get('/documents')->assertOk();
        $response->assertSee('Documentos y comunicados del proyecto');
        $response->assertSee('Calendario del segundo trimestre');
        $response->assertSee('Circular');
        $response->assertDontSee('>circular<', false); // la clave técnica no se muestra como texto
        $response->assertDontSee('Documentación Oficial');
        $response->assertDontSee('Normativa Oficial');
        $response->assertSee('no reemplazan la normativa oficial');
    }

    public function test_document_without_file_does_not_promise_a_download(): void
    {
        $this->document(['title' => 'Documento sin archivo', 'file_path' => null]);

        $this->get('/documents')->assertOk()
            ->assertSee('Documento sin archivo')
            ->assertSee('Solo resumen')
            ->assertDontSee('Descargar documento')
            ->assertDontSee("alert('");
    }

    public function test_document_with_real_url_shows_download_button(): void
    {
        $doc = $this->document(['title' => 'Documento con archivo', 'file_path' => 'https://example.org/doc.pdf']);

        $this->assertSame('https://example.org/doc.pdf', $doc->download_url);
        $this->get('/documents')->assertOk()
            ->assertSee('Descargar documento')
            ->assertSee('https://example.org/doc.pdf', false)
            ->assertDontSee('Solo resumen');
    }

    public function test_document_with_missing_local_file_has_no_download(): void
    {
        $doc = $this->document(['file_path' => 'documents/no-existe-' . uniqid() . '.pdf']);
        $this->assertNull($doc->download_url);

        $traversal = $this->document(['file_path' => '../../.env']);
        $this->assertNull($traversal->download_url);
    }

    public function test_documents_page_empty_states(): void
    {
        $this->get('/documents')->assertOk()
            ->assertSee('Aún no hay documentos publicados')
            ->assertSee('Conocer el proyecto YURA');

        $this->document(['title' => 'Unico documento']);

        $this->get('/documents?q=zzzzzz')->assertOk()
            ->assertSee('No encontramos documentos con esos filtros')
            ->assertSee('Quitar filtros')
            ->assertDontSee('Unico documento');

        $this->get('/documents?category=reglamento')->assertOk()
            ->assertSee('No encontramos documentos con esos filtros');
    }

    public function test_documents_filters_work_and_invalid_values_are_ignored(): void
    {
        $this->document(['title' => 'Circular de enero', 'category' => 'circular', 'year' => 2025, 'code' => 'C-1']);
        $this->document(['title' => 'Comunicado de marzo', 'category' => 'comunicado', 'year' => 2026, 'code' => 'C-2']);

        $this->get('/documents?category=circular')->assertOk()
            ->assertSee('Circular de enero')->assertDontSee('Comunicado de marzo');

        $this->get('/documents?year=2026')->assertOk()
            ->assertSee('Comunicado de marzo')->assertDontSee('Circular de enero');

        $this->get('/documents?q=C-2')->assertOk()
            ->assertSee('Comunicado de marzo')->assertDontSee('Circular de enero');

        $this->get('/documents?category=inventada&year=abc')->assertOk()
            ->assertSee('Circular de enero')->assertSee('Comunicado de marzo');

        $this->get('/documents?q[]=a&category[]=b&year[]=3')->assertOk();
    }

    public function test_inactive_and_future_documents_are_hidden(): void
    {
        $this->document(['title' => 'Documento inactivo', 'active' => false]);
        $this->document(['title' => 'Documento futuro', 'published_at' => now()->addMonth()->toDateString()]);
        $this->document(['title' => 'Documento visible']);

        $this->get('/documents')->assertOk()
            ->assertSee('Documento visible')
            ->assertDontSee('Documento inactivo')
            ->assertDontSee('Documento futuro');

        $this->get('/')->assertOk()->assertDontSee('Documento futuro');
    }

    public function test_documents_pagination_renders_without_framework_styles(): void
    {
        foreach (range(1, 14) as $i) {
            $this->document(['code' => "PAG-{$i}/2026"]);
        }

        $this->get('/documents')->assertOk()
            ->assertSee('Página 1 de 2')
            ->assertSee('Siguiente');
        $this->get('/documents?page=2')->assertOk()->assertSee('Página 2 de 2');
    }

    // ---------------------------------------------------------------- búsqueda

    public function test_empty_search_term_redirects_to_home(): void
    {
        $this->get('/search')->assertRedirect(route('public.home'));
        $this->get('/search?q=')->assertRedirect(route('public.home'));
        $this->get('/search?q=%20%20%20')->assertRedirect(route('public.home'));
        $this->get('/search?q[]=algo')->assertRedirect(route('public.home'));
    }

    public function test_search_shows_term_count_and_highlights_matches(): void
    {
        $this->service(['name' => 'Cuaderno Pedagógico Digital', 'description' => 'Registro de evaluaciones.']);
        $this->document(['title' => 'Guía del cuaderno para familias', 'code' => 'GUIA-1/2026']);

        $response = $this->get('/search?q=cuaderno')->assertOk();
        $response->assertSee('Resultados para:');
        $response->assertSee('cuaderno');
        $response->assertSee('2 resultados');
        $response->assertSee('<mark>Cuaderno</mark> Pedagógico Digital', false);
        $response->assertSee('Guía del <mark>cuaderno</mark> para familias', false);
        $response->assertDontSee('No encontramos resultados');
    }

    public function test_search_escapes_the_term_and_highlighting_never_injects_html(): void
    {
        $this->service(['name' => 'Servicio con <b>etiqueta</b> & más']);

        $response = $this->get('/search?q=' . urlencode('<script>alert(1)</script>'))->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('No encontramos resultados');

        // Buscar "&": se resalta el carácter ya escapado, sin romper la entidad &amp;
        $response = $this->get('/search?q=' . urlencode('&'))->assertOk();
        $response->assertSee('<mark>&amp;</mark>', false);

        $response = $this->get('/search?q=' . urlencode('<b>'))->assertOk();
        $response->assertSee('<mark>&lt;b&gt;</mark>etiqueta&lt;/b&gt; &amp; más', false)
            ->assertDontSee('<b>etiqueta</b>', false);
    }

    public function test_search_with_no_results_shows_empty_state_with_next_steps(): void
    {
        $this->service();

        $response = $this->get('/search?q=zzzzqqqq')->assertOk();
        $response->assertSee('Resultados para:');
        $response->assertSee('zzzzqqqq');
        $response->assertSee('0 resultados');
        $response->assertSee('No encontramos resultados');
        $response->assertSee('Prueba con:');
        $response->assertSee(route('public.services'), false);
        $response->assertSee(route('public.documents'), false);
        $response->assertSee('Ver todos los servicios');
    }

    public function test_search_does_not_return_hidden_services(): void
    {
        $this->service(['name' => 'Robot visible', 'action_url' => '/login']);
        $this->service(['name' => 'Robot sin enlace', 'action_url' => '#']);
        $this->service(['name' => 'Robot de un módulo retirado', 'action_url' => '/procedures']);

        $this->get('/search?q=robot')->assertOk()
            ->assertSee('1 resultado')
            ->assertSee('<mark>Robot</mark> visible', false)
            ->assertDontSee('sin enlace')
            ->assertDontSee('módulo retirado');
    }

    public function test_search_handles_long_terms_and_http_methods(): void
    {
        $this->get('/search?q=' . str_repeat('a', 500))->assertOk();
        $this->get('/search?q=100%25_')->assertOk();
    }

    // ---------------------------------------------------------------- idioma

    public function test_quechua_locale_works_on_all_pages(): void
    {
        $this->service(['name' => 'Cuaderno digital', 'subsystem' => 'superior']);
        $this->document(['title' => 'Comunicado del robot']);

        $qu = ['locale' => 'qu'];

        $this->withSession($qu)->get('/services')->assertOk()
            ->assertSee("Yachay yanapaykuna y digital llamk'anakuna")
            ->assertSee('Hatun yachay')
            ->assertDontSee('Servicios Educativos y Herramientas Digitales');

        $this->withSession($qu)->get('/documents')->assertOk()
            ->assertSee('Proyectoq qillqankuna y willakuyninkuna')
            ->assertSee('Willakuy (comunicado)');

        $this->withSession($qu)->get('/search?q=robot')->assertOk()
            ->assertSee('Kaypaq tarisqakuna:')
            ->assertSee('1 tarisqa');

        $this->withSession($qu)->get('/search?q=zzzz')->assertOk()
            ->assertSee('Mana imatapas tarinchu');
    }

    public function test_quechua_empty_states_are_translated(): void
    {
        $this->withSession(['locale' => 'qu'])->get('/services')->assertOk()
            ->assertSee('Yanapaykunata wakichishayku');
        $this->withSession(['locale' => 'qu'])->get('/documents')->assertOk()
            ->assertSee('Manaraq qillqakuna willasqa kanchu');
    }

    public function test_portal_translation_files_have_the_same_keys(): void
    {
        $es = require resource_path('lang/es/portal.php');
        $qu = require resource_path('lang/qu/portal.php');

        $this->assertEqualsCanonicalizing(array_keys(Arr::dot($es)), array_keys(Arr::dot($qu)));

        foreach (Arr::dot($qu) as $key => $value) {
            $this->assertNotSame('', trim((string) $value), "Texto vacío en qu: {$key}");
        }
        // Los marcadores :param deben ser los mismos en ambos idiomas
        foreach (Arr::dot($es) as $key => $value) {
            preg_match_all('/:[a-z_]+/', (string) $value, $esParams);
            preg_match_all('/:[a-z_]+/', (string) Arr::get($qu, $key), $quParams);
            $this->assertEqualsCanonicalizing($esParams[0], $quParams[0], "Parámetros distintos en {$key}");
        }

        $quSource = (string) file_get_contents(resource_path('lang/qu/portal.php'));
        $this->assertStringContainsString('// REVISAR: traducción pendiente de revisión por hablante nativo', $quSource);
    }
}
