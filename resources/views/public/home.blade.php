@extends('layouts.public')

@section('title', __('home.meta_title'))
@section('meta_description', __('home.meta_description'))

@section('styles')
<style>
    /* Portada: clases con prefijo hp- para no chocar con otras páginas. Solo colores del layout. */

    /* Hero */
    .hp-hero {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 65%, #1450B4 100%);
        color: #FFFFFF;
        padding: 64px 20px 72px;
    }
    .hp-hero-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 48px;
        align-items: center;
    }
    .hp-hero-inner > * { min-width: 0; }
    .hp-hero-title {
        font-size: clamp(1.85rem, 3.6vw, 2.75rem);
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.02em;
        margin-bottom: 18px;
        overflow-wrap: break-word;
    }
    .hp-hero-title span {
        display: block;
        margin-top: 10px;
        font-size: 0.62em;
        font-weight: 600;
        letter-spacing: 0;
        line-height: 1.3;
        color: #D6E0F5;
    }
    .hp-hero-sub {
        font-size: 1.1rem;
        line-height: 1.7;
        color: #D6E0F5;
        max-width: 58ch;
        margin-bottom: 28px;
    }
    .hp-hero-actions { display: flex; flex-wrap: wrap; gap: 14px; }
    .hp-hero-note {
        margin-top: 18px;
        font-size: 0.9rem;
        line-height: 1.5;
        color: #D6E0F5;
        max-width: 58ch;
    }

    /* Recuadro del robot (sin foto real todavía) */
    .hp-robot-card {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-top: 4px solid var(--accent);
        border-radius: 16px;
        padding: 28px;
        text-align: center;
    }
    .hp-robot-icon {
        width: 112px;
        height: 112px;
        margin: 0 auto 14px;
        border-radius: 28px;
        background: #FFFFFF;
        color: var(--primary);
        display: grid;
        place-items: center;
        font-size: 3.4rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }
    .hp-robot-name { font-size: 1.3rem; font-weight: 800; margin-bottom: 16px; }
    .hp-robot-list { list-style: none; display: grid; gap: 10px; text-align: left; }
    .hp-robot-list li {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.95rem;
        line-height: 1.4;
    }
    .hp-robot-list i { width: 20px; text-align: center; flex-shrink: 0; }
    .hp-photo {
        display: block;
        width: 100%;
        aspect-ratio: 4 / 3;
        object-fit: cover;
        border-radius: 16px;
        border: 4px solid #FFFFFF;
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.25);
    }

    /* Franja de búsqueda (pequeña, secundaria) */
    .hp-search { background: #FFFFFF; border-bottom: 1px solid var(--border); }
    .hp-search-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px 20px;
        flex-wrap: wrap;
    }
    .hp-search-label { font-size: 0.95rem; font-weight: 600; color: var(--slate); }
    .hp-search-label i { color: var(--primary); margin-right: 6px; }
    .hp-search-form { display: flex; gap: 8px; flex: 1 1 380px; max-width: 520px; }
    .hp-search-form input {
        flex: 1;
        min-width: 0;
        min-height: 44px;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: #FFFFFF;
        color: var(--slate);
        font: inherit;
    }
    .hp-search-form input:focus-visible {
        outline: 3px solid var(--action);
        outline-offset: 1px;
        border-color: var(--action);
    }
    .hp-search-form .btn-portal { min-height: 44px; }

    /* Secciones */
    .hp-section { max-width: 1200px; margin: 64px auto 0; padding: 0 20px; }
    .hp-head { margin-bottom: 28px; }
    .hp-head h2 { font-size: 1.65rem; font-weight: 800; line-height: 1.25; color: var(--dark); }
    .hp-head p { margin-top: 4px; color: var(--muted); }
    .hp-head-row {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 8px 16px;
        flex-wrap: wrap;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border);
    }
    .hp-head-row .hp-head-text h2 { font-size: 1.4rem; }
    .hp-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 0;
        color: var(--primary);
        font-size: 0.95rem;
        font-weight: 700;
        text-decoration: none;
    }
    .hp-link:hover { text-decoration: underline; }
    .hp-link:focus-visible, .hp-doc:focus-visible {
        outline: 3px solid var(--action);
        outline-offset: 2px;
        border-radius: 6px;
    }

    .hp-card {
        background: #FFFFFF;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 26px;
    }
    .hp-tone-blue { --tone: var(--primary); --tone-bg: var(--primary-light); }
    .hp-tone-red { --tone: var(--accent-dark); --tone-bg: var(--accent-light); }
    .hp-tone-action { --tone: var(--action); --tone-bg: var(--action-soft); }
    .hp-ico {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        font-size: 1.4rem;
        background: var(--tone-bg);
        color: var(--tone);
    }

    /* Tres caminos */
    .hp-paths { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
    .hp-path { display: flex; flex-direction: column; align-items: flex-start; border-top: 4px solid var(--tone); }
    .hp-path h3 { margin: 16px 0 8px; font-size: 1.2rem; line-height: 1.3; color: var(--dark); }
    .hp-path p { flex: 1; margin-bottom: 20px; color: var(--muted); }

    /* Cómo funciona */
    .hp-steps { list-style: none; display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; padding-top: 16px; }
    .hp-step { position: relative; padding-top: 34px; }
    .hp-step-num {
        position: absolute;
        top: -18px;
        left: 24px;
        width: 40px;
        height: 40px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        border: 3px solid var(--light);
        background: var(--primary);
        color: #FFFFFF;
        font-weight: 800;
        box-shadow: 0 4px 10px rgba(11, 61, 145, 0.3);
    }
    .hp-step h3 { margin: 14px 0 8px; font-size: 1.15rem; line-height: 1.3; color: var(--dark); }
    .hp-step p { color: var(--muted); }
    .hp-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 26px; }
    .hp-chips-label { font-weight: 600; color: var(--slate); }
    .hp-chip {
        padding: 6px 14px;
        border: 1px solid var(--action-border);
        border-radius: 999px;
        background: var(--primary-light);
        color: var(--primary-dark);
        font-size: 0.9rem;
        font-weight: 600;
    }

    /* Cifras reales */
    .hp-stats-band {
        padding: 36px 28px;
        border: 1px solid var(--action-border);
        border-radius: 16px;
        background: var(--primary-light);
    }
    .hp-stats-band .hp-head { margin-bottom: 22px; }
    .hp-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; }
    .hp-stat {
        padding: 20px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: #FFFFFF;
        text-align: center;
    }
    .hp-stat strong { display: block; font-size: 2.4rem; font-weight: 800; line-height: 1.1; color: var(--primary); }
    .hp-stat span { color: var(--muted); font-weight: 600; }

    /* App móvil */
    .hp-app {
        display: flex;
        align-items: center;
        gap: 28px;
        padding: 30px;
        border: 1px solid var(--action-border);
        border-radius: 16px;
        background: var(--action-soft);
    }
    .hp-app-ico {
        width: 84px;
        height: 84px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        border-radius: 20px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #FFFFFF;
        font-size: 2.4rem;
        box-shadow: 0 8px 18px rgba(11, 61, 145, 0.3);
    }
    .hp-app-body { flex: 1; min-width: 0; }
    .hp-app-body h2 { font-size: 1.45rem; font-weight: 800; color: var(--dark); margin-bottom: 6px; }
    .hp-app-body p { color: var(--slate); }

    /* Servicios (compactos) */
    .hp-services { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
    .hp-service { display: flex; flex-direction: column; padding: 22px; }
    .hp-service-top { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .hp-service-top .hp-ico { width: 40px; height: 40px; border-radius: 10px; font-size: 1.05rem; }
    .hp-tag { font-size: 0.8rem; font-weight: 700; line-height: 1.3; color: var(--primary); }
    .hp-service h3 { margin-bottom: 6px; font-size: 1.08rem; line-height: 1.3; color: var(--dark); }
    .hp-service p { flex: 1; margin-bottom: 8px; font-size: 0.92rem; color: var(--muted); }

    /* Documentos (lista compacta) */
    .hp-docs {
        overflow: hidden;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: #FFFFFF;
        box-shadow: var(--shadow);
    }
    .hp-doc {
        display: flex;
        align-items: center;
        gap: 14px;
        min-height: 64px;
        padding: 14px 20px;
        border-bottom: 1px solid var(--border);
        color: inherit;
        text-decoration: none;
        transition: background 0.15s;
    }
    .hp-doc:last-child { border-bottom: 0; }
    .hp-doc:hover { background: var(--light); }
    .hp-doc-badge {
        flex: 0 0 auto;
        min-width: 112px;
        padding: 4px 10px;
        border: 1px solid transparent;
        border-radius: 6px;
        text-align: center;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    .hp-doc-badge-resolucion { background: var(--primary-light); color: var(--primary-dark); }
    .hp-doc-badge-convocatoria { background: var(--accent-light); color: var(--accent-dark); }
    .hp-doc-badge-circular, .hp-doc-badge-otro { background: var(--light); color: var(--slate); border-color: var(--border); }
    .hp-doc-badge-comunicado { background: #FFFFFF; color: var(--accent-dark); border-color: var(--accent); }
    .hp-doc-body { flex: 1; min-width: 0; }
    .hp-doc-body strong { display: block; font-size: 0.98rem; line-height: 1.35; color: var(--dark); }
    .hp-doc-body small { color: var(--muted); }
    .hp-doc-go { flex: 0 0 auto; font-size: 0.9rem; font-weight: 700; color: var(--primary); white-space: nowrap; }

    @media (max-width: 900px) {
        .hp-hero-inner { grid-template-columns: 1fr; gap: 32px; }
        .hp-paths, .hp-services { grid-template-columns: 1fr; }
        .hp-steps { grid-template-columns: 1fr; gap: 34px; }
    }
    @media (max-width: 600px) {
        .hp-hero { padding: 40px 16px 48px; }
        .hp-hero-sub { font-size: 1.02rem; }
        .hp-hero-actions .btn-portal { width: 100%; justify-content: center; }
        .hp-robot-card { padding: 22px 18px; }
        .hp-robot-icon { width: 92px; height: 92px; font-size: 2.8rem; }
        .hp-section { margin-top: 48px; padding: 0 16px; }
        .hp-head h2 { font-size: 1.4rem; }
        .hp-search-inner { padding: 14px 16px; }
        .hp-search-form { flex: 1 1 100%; max-width: none; }
        .hp-stats-band { padding: 26px 16px; }
        .hp-app { flex-direction: column; align-items: flex-start; padding: 24px 20px; }
        .hp-app .btn-portal { width: 100%; justify-content: center; }
        .hp-doc { flex-wrap: wrap; gap: 8px 12px; padding: 14px 16px; }
        .hp-doc-badge { min-width: 0; }
        .hp-doc-body { flex: 1 1 100%; order: 3; }
        .hp-doc-go { margin-left: auto; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hp-doc { transition: none; }
    }
</style>
@endsection

@section('content')
@php
    // El controlador envía $stats; si por algún motivo no llega, se calcula aquí (solo conteos agregados).
    $stats = $stats ?? \App\Services\PublicStats::summary();
    $services = collect($services ?? [])->flatten()->take(3);
    $recentDocuments = collect($recentDocuments ?? [])->take(3);

    // Enlace de descarga: solo si está configurado y es una URL http(s) o una ruta del sitio.
    $appUrl = trim((string) config('yura.app_url'));
    $appUrl = preg_match('#^(https?://|/)#i', $appUrl) === 1 ? $appUrl : null;

    $hasRobotPhoto = file_exists(public_path('img/robot-yura.jpg'));

    $paths = [
        ['key' => 'family', 'icon' => 'fa-house-user', 'tone' => 'blue', 'href' => route('login')],
        ['key' => 'teacher', 'icon' => 'fa-chalkboard-user', 'tone' => 'red', 'href' => route('login')],
        ['key' => 'press', 'icon' => 'fa-handshake', 'tone' => 'action', 'href' => route('feria')],
    ];
    $steps = [
        ['icon' => 'fa-clipboard-check', 'tone' => 'blue'],
        ['icon' => 'fa-robot', 'tone' => 'red'],
        ['icon' => 'fa-book-open', 'tone' => 'action'],
    ];
    $statItems = [
        'students' => 'fa-user-group',
        'teachers' => 'fa-chalkboard-user',
        'activities' => 'fa-robot',
        'grades' => 'fa-book-open',
    ];
@endphp

{{-- Hero: qué es YURA y qué hacer ahora. Un solo <h1> en toda la página. --}}
<section class="hp-hero" aria-labelledby="hp-hero-title">
    <div class="hp-hero-inner">
        <div>
            <h1 id="hp-hero-title" class="hp-hero-title">
                {{ __('home.hero_title_1') }}
                <span>{{ __('home.hero_title_2') }}</span>
            </h1>
            <p class="hp-hero-sub">{{ __('home.hero_subtitle') }}</p>

            <div class="hp-hero-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-portal btn-portal-light btn-portal-lg">
                        <i class="fas fa-gauge" aria-hidden="true"></i> {{ __('home.hero_cta_panel') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-portal btn-portal-light btn-portal-lg">
                        <i class="fas fa-right-to-bracket" aria-hidden="true"></i> {{ __('home.hero_cta_login') }}
                    </a>
                @endauth
                <a href="{{ route('feria') }}" class="btn-portal btn-portal-ghost-light btn-portal-lg">
                    {{ __('home.hero_cta_project') }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
            @guest
                <p class="hp-hero-note">{{ __('home.hero_note') }}</p>
            @endguest
        </div>

        <div>
            @if($hasRobotPhoto)
                {{-- PENDIENTE: reemplazar public/img/robot-yura.jpg por una foto real del robot con alumnos (con permiso de las familias). --}}
                <img src="{{ asset('img/robot-yura.jpg') }}" alt="{{ __('home.hero_photo_alt') }}" class="hp-photo">
            @else
                {{-- PENDIENTE: foto real del robot con alumnos en public/img/robot-yura.jpg (con permiso). Mientras no exista, se muestra este recuadro. --}}
                <div class="hp-robot-card">
                    <div class="hp-robot-icon" aria-hidden="true"><i class="fas fa-robot"></i></div>
                    <p class="hp-robot-name">{{ __('home.visual_caption') }}</p>
                    <ul class="hp-robot-list">
                        <li><i class="fas fa-comments" aria-hidden="true"></i> <span>{{ __('home.visual_1') }}</span></li>
                        <li><i class="fas fa-book-open" aria-hidden="true"></i> <span>{{ __('home.visual_2') }}</span></li>
                        <li><i class="fas fa-users" aria-hidden="true"></i> <span>{{ __('home.visual_3') }}</span></li>
                    </ul>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- Búsqueda: franja pequeña, ya no es protagonista. --}}
<div class="hp-search">
    <div class="hp-search-inner">
        <p class="hp-search-label"><i class="fas fa-magnifying-glass" aria-hidden="true"></i> {{ __('home.search_label') }}</p>
        <form action="{{ route('public.search') }}" method="GET" class="hp-search-form" role="search">
            <input type="search" name="q" maxlength="100" placeholder="{{ __('home.search_placeholder') }}" aria-label="{{ __('home.search_aria') }}" required>
            <button type="submit" class="btn-portal btn-portal-primary">{{ __('home.search_button') }}</button>
        </form>
    </div>
</div>

{{-- Tres caminos por público --}}
<section class="hp-section" aria-labelledby="hp-paths-title">
    <div class="hp-head">
        <h2 id="hp-paths-title">{{ __('home.paths_title') }}</h2>
        <p>{{ __('home.paths_lead') }}</p>
    </div>
    <div class="hp-paths">
        @foreach($paths as $path)
            <article class="hp-card hp-path hp-tone-{{ $path['tone'] }}">
                <div class="hp-ico" aria-hidden="true"><i class="fas {{ $path['icon'] }}"></i></div>
                <h3>{{ __('home.' . $path['key'] . '_title') }}</h3>
                <p>{{ __('home.' . $path['key'] . '_text') }}</p>
                <a href="{{ $path['href'] }}" class="btn-portal btn-portal-outline">{{ __('home.' . $path['key'] . '_cta') }}</a>
            </article>
        @endforeach
    </div>
</section>

{{-- Cómo funciona --}}
<section class="hp-section" aria-labelledby="hp-how-title">
    <div class="hp-head">
        <h2 id="hp-how-title">{{ __('home.how_title') }}</h2>
        <p>{{ __('home.how_lead') }}</p>
    </div>
    <ol class="hp-steps">
        @foreach($steps as $i => $step)
            <li class="hp-card hp-step hp-tone-{{ $step['tone'] }}">
                <span class="hp-step-num" aria-hidden="true">{{ $i + 1 }}</span>
                <div class="hp-ico" aria-hidden="true"><i class="fas {{ $step['icon'] }}"></i></div>
                <h3>{{ __('home.step_' . ($i + 1) . '_title') }}</h3>
                <p>{{ __('home.step_' . ($i + 1) . '_text') }}</p>
            </li>
        @endforeach
    </ol>
    <div class="hp-chips">
        <span class="hp-chips-label">{{ __('home.how_also') }}</span>
        <span class="hp-chip">{{ __('home.how_tag_notebook') }}</span>
        <span class="hp-chip">{{ __('home.how_tag_attendance') }}</span>
        <span class="hp-chip">{{ __('home.how_tag_robot') }}</span>
        <span class="hp-chip">{{ __('home.how_tag_family') }}</span>
    </div>
</section>

{{-- Cifras reales: solo si hay datos suficientes (PublicStats::summary()['show']). Nunca datos personales. --}}
@if(! empty($stats['show']))
    <section class="hp-section" aria-labelledby="hp-stats-title">
        <div class="hp-stats-band">
            <div class="hp-head">
                <h2 id="hp-stats-title">{{ __('home.stats_title') }}</h2>
                <p>{{ __('home.stats_lead') }}</p>
            </div>
            <div class="hp-stat-grid">
                @foreach($statItems as $key => $icon)
                    @if((int) ($stats[$key] ?? 0) > 0)
                        <div class="hp-stat">
                            <strong>{{ number_format((int) $stats[$key], 0, ',', '.') }}</strong>
                            <span>{{ trans_choice('home.stat_' . $key, (int) $stats[$key]) }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- App móvil: solo si hay enlace de descarga (YURA_APP_URL). --}}
@if($appUrl)
    <section class="hp-section" aria-labelledby="hp-app-title">
        <div class="hp-app">
            <div class="hp-app-ico" aria-hidden="true"><i class="fas fa-mobile-screen-button"></i></div>
            <div class="hp-app-body">
                <h2 id="hp-app-title">{{ __('home.app_title') }}</h2>
                <p>{{ __('home.app_text') }}</p>
            </div>
            <a href="{{ $appUrl }}" class="btn-portal btn-portal-primary btn-portal-lg" target="_blank" rel="noopener">
                <i class="fas fa-download" aria-hidden="true"></i> {{ __('home.app_cta') }}
            </a>
        </div>
    </section>
@endif

{{-- Servicios (compacto). Si no hay servicios publicables, no se muestra el bloque. --}}
@if($services->isNotEmpty())
    <section class="hp-section" aria-labelledby="hp-services-title">
        <div class="hp-head hp-head-row">
            <div class="hp-head-text">
                <h2 id="hp-services-title">{{ __('home.services_title') }}</h2>
                <p>{{ __('home.services_lead') }}</p>
            </div>
            <a href="{{ route('public.services') }}" class="hp-link">{{ __('home.services_all') }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="hp-services">
            @foreach($services as $service)
                @php
                    $serviceIcon = preg_match('/^fa-[a-z0-9-]+$/', (string) $service->icon) === 1 ? $service->icon : 'fa-circle-info';
                    $serviceTone = ['blue', 'red', 'action'][$loop->index % 3];
                @endphp
                <article class="hp-card hp-service hp-tone-{{ $serviceTone }}">
                    <div class="hp-service-top">
                        <span class="hp-ico" aria-hidden="true"><i class="fas {{ $serviceIcon }}"></i></span>
                        <span class="hp-tag">{{ $service->target_audience ?: $service->subsystem_label }}</span>
                    </div>
                    <h3>{{ $service->name }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit((string) $service->description, 130) }}</p>
                    @if($service->link_url)
                        <a href="{{ $service->link_url }}" class="hp-link">{{ __('home.service_open') }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif

{{-- Documentos recientes (compacto). Si no hay documentos publicados, no se muestra el bloque. --}}
@if($recentDocuments->isNotEmpty())
    <section class="hp-section" aria-labelledby="hp-docs-title">
        <div class="hp-head hp-head-row">
            <div class="hp-head-text">
                <h2 id="hp-docs-title">{{ __('home.docs_title') }}</h2>
                <p>{{ __('home.docs_lead') }}</p>
            </div>
            <a href="{{ route('public.documents') }}" class="hp-link">{{ __('home.docs_all') }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="hp-docs">
            @foreach($recentDocuments as $doc)
                @php
                    $docCategory = in_array($doc->category, ['resolucion', 'convocatoria', 'circular', 'comunicado'], true) ? $doc->category : 'otro';
                @endphp
                <a href="{{ route('public.documents', ['q' => $doc->code]) }}" class="hp-doc">
                    <span class="hp-doc-badge hp-doc-badge-{{ $docCategory }}">{{ $doc->category_label }}</span>
                    <span class="hp-doc-body">
                        <strong>{{ $doc->title }}</strong>
                        @if($doc->published_at)
                            <small>{{ __('home.doc_published', ['date' => $doc->published_at->format('d/m/Y')]) }}</small>
                        @endif
                    </span>
                    <span class="hp-doc-go">{{ __('home.doc_view') }} <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- Cierre: contacto (WhatsApp/correo solo si están configurados; si no, enlaza a Ingresar). --}}
@include('public.partials.cta-band', ['title' => __('home.cta_title'), 'text' => __('home.cta_text')])
@endsection
