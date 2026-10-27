<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\SeoController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Diseño base del portal público: SEO, vista previa al compartir, cabecera, pie,
 * franja de contacto y archivos para buscadores.
 */
class LayoutSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fuera de una petición web el idioma por defecto de la app es "en"; el portal arranca en castellano.
        app()->setLocale('es');
    }

    /** Sin datos de contacto configurados (aunque el .env local los tenga). */
    private function withoutContactConfig(): void
    {
        config()->set('yura.contact.whatsapp', null);
        config()->set('yura.contact.email', null);
        config()->set('yura.contact.address', null);
        config()->set('yura.contact.hours', null);
        config()->set('yura.org_name', null);
        config()->set('yura.app_url', null);
        config()->set('yura.social.facebook', null);
        config()->set('yura.social.tiktok', null);
        config()->set('yura.social.youtube', null);
    }

    /** Página mínima que usa el layout público. */
    private function renderLayout(string $sections = '', string $content = '<p>contenido</p>'): string
    {
        return Blade::render("@extends('layouts.public') {$sections} @section('content') {$content} @endsection");
    }

    // ---- Configuración ----

    public function test_config_yura_tiene_las_claves_del_contrato(): void
    {
        foreach ([
            'yura.contact.whatsapp', 'yura.contact.email', 'yura.contact.address', 'yura.contact.hours',
            'yura.app_url', 'yura.video_url', 'yura.org_name',
            'yura.social.facebook', 'yura.social.tiktok', 'yura.social.youtube',
        ] as $key) {
            $this->assertTrue(Arr::has(config()->all(), $key), "Falta la clave de config {$key}");
        }

        $this->assertIsInt(config('yura.stats_min'));
    }

    // ---- SEO y vista previa ----

    public function test_la_portada_tiene_meta_og_canonical_y_favicon(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<meta name="description" content="', false);
        $response->assertSee('<meta property="og:image" content="'.asset('img/og-yura.png').'"', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:description"', false);
        $response->assertSee('<meta property="og:type" content="website"', false);
        $response->assertSee('<meta property="og:url"', false);
        $response->assertSee('<meta property="og:locale" content="es_LA"', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image"', false);
        $response->assertSee('<link rel="canonical" href="'.route('public.home').'"', false);
        $response->assertSee('rel="icon"', false);
        $response->assertSee('favicon.svg', false);
        $response->assertSee('<meta name="theme-color" content="#0B3D91"', false);
        $response->assertSee('<html lang="es"', false);
    }

    public function test_la_portada_tiene_exactamente_un_h1(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<h1[\s>]/i', $html), 'La portada debe tener un solo <h1>.');
    }

    public function test_el_layout_por_si_solo_no_emite_ningun_h1(): void
    {
        $this->assertSame(0, preg_match_all('/<h1[\s>]/i', $this->renderLayout()));
        $this->assertSame(1, preg_match_all('/<h1[\s>]/i', $this->renderLayout('', '<h1>Título</h1>')));
    }

    public function test_las_vistas_pueden_personalizar_los_metadatos(): void
    {
        $html = $this->renderLayout(
            "@section('title', 'Proyecto & YURA')"
            ."@section('meta_description', 'Descripción \"propia\" de la página')"
            ."@section('og_image', 'img/otra.png')"
            ."@section('canonical', 'https://ejemplo.test/pagina')"
            ."@section('og_type', 'article')"
        );

        $this->assertStringContainsString('<title>Proyecto &amp; YURA</title>', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Proyecto &amp; YURA"', $html);
        $this->assertStringContainsString('content="Descripción &quot;propia&quot; de la página"', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.asset('img/otra.png').'"', $html);
        $this->assertStringContainsString('<meta name="twitter:image" content="'.asset('img/otra.png').'"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://ejemplo.test/pagina"', $html);
        $this->assertStringContainsString('<meta property="og:url" content="https://ejemplo.test/pagina"', $html);
        $this->assertStringContainsString('<meta property="og:type" content="article"', $html);
        // Con imagen propia no se declara el tamaño de la imagen por defecto
        $this->assertStringNotContainsString('og:image:width', $html);
    }

    public function test_los_valores_por_defecto_estan_en_espanol_y_cambian_con_el_idioma(): void
    {
        $this->get('/')->assertSee(__('layout.meta_description', [], 'es'), false);

        $this->withSession(['locale' => 'qu'])->get('/')
            ->assertOk()
            ->assertSee('<html lang="qu"', false)
            ->assertSee('<meta property="og:locale" content="qu_PE"', false)
            ->assertSee(__('layout.login', [], 'qu'), false);
    }

    public function test_el_idioma_activo_se_marca_en_la_barra_superior(): void
    {
        $this->get('/')->assertSee('lang="es" class="is-active" aria-current="true"', false);

        $this->withSession(['locale' => 'qu'])->get('/')
            ->assertSee('lang="qu" class="is-active" aria-current="true"', false);
    }

    public function test_los_idiomas_es_y_qu_tienen_las_mismas_claves_de_layout(): void
    {
        $es = array_keys(require resource_path('lang/es/layout.php'));
        $qu = array_keys(require resource_path('lang/qu/layout.php'));

        $this->assertSame([], array_diff($es, $qu), 'Faltan claves en qu/layout.php');
        $this->assertSame([], array_diff($qu, $es), 'Sobran claves en qu/layout.php');
        $this->assertStringContainsString('REVISAR', file_get_contents(resource_path('lang/qu/layout.php')));
    }

    // ---- Pie y cabecera ----

    public function test_el_pie_no_muestra_datos_inventados_ni_la_pila_tecnologica(): void
    {
        $this->withoutContactConfig();

        $response = $this->get('/')->assertOk();

        foreach (['Laravel 12', 'MySQL 8.0', '244-0000', 'yura-platform.edu.bo', 'Ventanilla', 'Inspirada en el modelo', 'Plataforma Segura'] as $texto) {
            $response->assertDontSee($texto, false);
        }

        $response->assertSee(route('public.privacy'), false);
    }

    public function test_el_contacto_no_aparece_si_no_esta_configurado(): void
    {
        $this->withoutContactConfig();

        $response = $this->get('/')->assertOk();

        $response->assertDontSee('wa.me/', false);
        $response->assertDontSee('mailto:', false);
        $response->assertDontSee('fa-whatsapp', false);
        $response->assertDontSee('Contáctanos', false);
    }

    public function test_el_contacto_aparece_solo_si_config_lo_define(): void
    {
        $this->withoutContactConfig();
        config()->set('yura.contact.whatsapp', '+591 71234567');
        config()->set('yura.contact.email', 'contacto@ejemplo.test');
        config()->set('yura.contact.address', 'Calle de prueba 123');
        config()->set('yura.contact.hours', 'Lunes a viernes, 9:00 a 17:00');
        config()->set('yura.org_name', 'Unidad Educativa de Prueba');
        config()->set('yura.app_url', 'https://ejemplo.test/app');
        config()->set('yura.social.facebook', 'https://facebook.com/ejemplo');

        $response = $this->get('/')->assertOk();

        $response->assertSee('https://wa.me/59171234567?text=', false);
        $response->assertSee(rawurlencode('Hola, quiero saber más de YURA'), false);
        $response->assertSee('mailto:contacto@ejemplo.test', false);
        $response->assertSee('Calle de prueba 123');
        $response->assertSee('Lunes a viernes, 9:00 a 17:00');
        $response->assertSee('Unidad Educativa de Prueba');
        $response->assertSee('https://ejemplo.test/app', false);
        $response->assertSee('https://facebook.com/ejemplo', false);
        $response->assertSee('href="#contacto"', false);
        // Las redes no configuradas no se pintan
        $response->assertDontSee('fa-tiktok', false);
        $response->assertDontSee('fa-youtube', false);
    }

    public function test_los_valores_de_contacto_invalidos_se_ignoran(): void
    {
        $this->withoutContactConfig();
        config()->set('yura.contact.whatsapp', 'sin números');
        config()->set('yura.contact.email', 'esto no es un correo');
        config()->set('yura.social.facebook', 'javascript:alert(1)');
        config()->set('yura.app_url', 'javascript:alert(1)');

        $response = $this->get('/')->assertOk();

        $response->assertDontSee('wa.me/', false);
        $response->assertDontSee('mailto:', false);
        $response->assertDontSee('javascript:alert', false);
    }

    public function test_la_cabecera_tiene_ingresar_y_menu_movil_accesible(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(route('login'), $html);
        $this->assertStringContainsString('Ingresar', $html);
        $this->assertStringContainsString('id="nav-toggle"', $html);
        $this->assertStringContainsString('aria-controls="primary-nav"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        // El botón "Comunicados" que llevaba a documentos ya no existe en la cabecera
        $this->assertStringNotContainsString('fa-bullhorn', $html);
    }

    public function test_el_css_define_los_botones_nuevos_con_foco_visible(): void
    {
        $html = $this->renderLayout();

        foreach (['.btn-portal-lg', '.btn-portal-light', '.btn-portal-ghost-light'] as $clase) {
            $this->assertStringContainsString($clase, $html);
        }
        $this->assertStringContainsString('.btn-portal-light:focus-visible', $html);
        $this->assertStringContainsString('prefers-reduced-motion', $html);
    }

    // ---- Franja de contacto ----

    public function test_la_franja_de_contacto_solo_muestra_ingresar_sin_datos(): void
    {
        $this->withoutContactConfig();

        $html = Blade::render("@include('public.partials.cta-band', ['title' => 'Hablemos', 'text' => 'Texto de prueba'])");

        $this->assertStringContainsString('Hablemos', $html);
        $this->assertStringContainsString('Texto de prueba', $html);
        $this->assertStringContainsString(route('login'), $html);
        $this->assertStringContainsString('btn-portal-light', $html);
        $this->assertStringNotContainsString('wa.me', $html);
        $this->assertStringNotContainsString('mailto:', $html);
    }

    public function test_la_franja_de_contacto_agrega_whatsapp_y_correo_si_existen(): void
    {
        $this->withoutContactConfig();
        config()->set('yura.contact.whatsapp', '59171234567');
        config()->set('yura.contact.email', 'contacto@ejemplo.test');

        $html = Blade::render("@include('public.partials.cta-band', ['title' => 'Hablemos', 'text' => 'Texto'])");

        $this->assertStringContainsString('https://wa.me/59171234567?text='.rawurlencode('Hola, quiero saber más de YURA'), $html);
        $this->assertStringContainsString('mailto:contacto@ejemplo.test', $html);
        $this->assertStringContainsString(route('login'), $html);
        // Un solo botón principal (relleno blanco): WhatsApp; correo e Ingresar van con borde
        $this->assertSame(1, preg_match_all('/class="[^"]*\bbtn-portal-light\b/', $html));
        $this->assertSame(2, preg_match_all('/class="[^"]*\bbtn-portal-ghost-light\b/', $html));
    }

    // ---- Buscadores ----

    public function test_sitemap_responde_xml_con_las_paginas_publicas(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));

        $xml = $response->getContent();
        $this->assertNotFalse(simplexml_load_string($xml), 'El sitemap no es XML válido.');

        foreach (['public.home', 'public.services', 'feria', 'public.documents', 'public.privacy'] as $name) {
            $this->assertStringContainsString('<loc>'.route($name).'</loc>', $xml);
        }
        $this->assertStringContainsString('<lastmod>', $xml);
        // Nada interno
        $this->assertStringNotContainsString('/login', $xml);
        $this->assertStringNotContainsString('/dashboard', $xml);
        $this->assertStringNotContainsString('/search', $xml);
    }

    public function test_robots_txt_estatico_bloquea_lo_interno_y_coincide_con_el_controlador(): void
    {
        $archivo = str_replace("\r\n", "\n", file_get_contents(public_path('robots.txt')));

        $this->assertStringContainsString("User-agent: *\nAllow: /", $archivo);
        foreach (['/dashboard', '/login', '/admin', '/api/'] as $ruta) {
            $this->assertStringContainsString('Disallow: '.$ruta."\n", $archivo);
        }
        foreach (SeoController::DISALLOWED_PATHS as $ruta) {
            $this->assertStringContainsString('Disallow: '.$ruta."\n", $archivo, "robots.txt no coincide con SeoController::DISALLOWED_PATHS ({$ruta})");
        }

        // Versión dinámica (aún no registrada como ruta): calcula la URL del sitemap sola
        $dinamico = app(SeoController::class)->robots();
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $dinamico->getContent());
        $this->assertStringContainsString('text/plain', $dinamico->headers->get('Content-Type'));
    }

    // ---- Marca ----

    public function test_la_imagen_para_compartir_y_el_favicon_existen(): void
    {
        $this->assertFileExists(public_path('favicon.svg'));
        $this->assertStringContainsString('#0B3D91', file_get_contents(public_path('favicon.svg')));

        $this->assertFileExists(public_path('img/og-yura.png'));
        [$ancho, $alto] = getimagesize(public_path('img/og-yura.png'));
        $this->assertSame([1200, 630], [$ancho, $alto]);
    }
}
