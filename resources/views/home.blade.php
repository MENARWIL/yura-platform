{{--
    Página pública «Proyecto YURA» (ruta /feria, nombre 'feria').
    Textos: resources/lang/{es,qu}/project.php. Cada afirmación debe poder comprobarse en el código o en docs/.

    PENDIENTE (dueño del proyecto): subir 3 fotos reales del robot (con permiso de las familias) como
    public/img/robot-yura-1.jpg, robot-yura-2.jpg y robot-yura-3.jpg, y poner YURA_VIDEO_URL en el .env.
    Mientras no existan, el bloque de galería/video no se muestra.
--}}
@extends('layouts.public')

@section('title', __('project.meta.title'))
@section('meta_description', __('project.meta.description'))
@section('og_type', 'website')

@section('content')
@php
    // Cifras reales (solo conteos agregados). Se pueden inyectar desde fuera ($stats); si no, se piden al servicio.
    $stats = $stats ?? rescue(fn () => \App\Services\PublicStats::summary(), ['show' => false]);
    $statKeys = collect(['students', 'teachers', 'activities', 'grades'])
        ->filter(fn ($key) => (int) ($stats[$key] ?? 0) > 0)
        ->values();
    $showStats = (bool) ($stats['show'] ?? false) && $statKeys->isNotEmpty();

    // Solo se aceptan enlaces http(s) completos.
    $cleanUrl = fn ($url) => is_string($url) && preg_match('#^https?://\S+$#i', trim($url)) ? trim($url) : null;
    $appUrl = $cleanUrl(config('yura.app_url'));
    $videoUrl = $cleanUrl(config('yura.video_url'));
    $videoIsFile = $videoUrl && preg_match('/\.(mp4|webm)(\?.*)?$/i', $videoUrl);

    // Fotos: solo las que existen en public/img.
    $photos = collect([1, 2, 3])
        ->filter(fn ($n) => is_file(public_path("img/robot-yura-{$n}.jpg")))
        ->values();
    $showMedia = $photos->isNotEmpty() || $videoUrl;
@endphp

<style>
    .pj-wrap { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    .pj-wrap > * { min-width: 0; }
    .pj-section { padding-top: 60px; scroll-margin-top: 90px; }
    .pj-section-head { margin-bottom: 26px; max-width: 760px; }
    .pj-section-head h2 { font-size: clamp(1.4rem, 2.6vw, 1.85rem); font-weight: 800; color: var(--dark); line-height: 1.2; letter-spacing: -0.01em; }
    .pj-section-head h2::after { content: ""; display: block; width: 56px; height: 4px; margin-top: 12px; background: var(--accent); border-radius: 2px; }
    .pj-section-head p { margin-top: 12px; color: var(--muted); font-size: 1.02rem; line-height: 1.7; }
    .pj-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

    /* Hero */
    .pj-hero { background: linear-gradient(135deg, #082C6C 0%, #0B3D91 65%, #1450B4 100%); color: #fff; padding: 56px 0 64px; }
    .pj-hero-inner { display: grid; grid-template-columns: 1.25fr 0.75fr; gap: 40px; align-items: center; }
    .pj-hero-inner > * { min-width: 0; }
    .pj-eyebrow { display: inline-flex; align-items: center; gap: 8px; background: var(--accent); color: #fff; padding: 6px 14px; border-radius: 999px; font-size: 0.85rem; font-weight: 600; margin-bottom: 18px; }
    .pj-hero h1 { font-size: clamp(1.75rem, 3.4vw, 2.7rem); line-height: 1.15; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 16px; overflow-wrap: break-word; }
    .pj-lead { font-size: 1.08rem; color: #D6E0F5; line-height: 1.7; margin-bottom: 28px; max-width: 62ch; }
    .pj-actions { display: flex; flex-wrap: wrap; gap: 12px; }
    .pj-actions .btn-portal { justify-content: center; }

    .pj-flow { list-style: none; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 18px; padding: 22px; display: grid; gap: 0; }
    .pj-flow li { display: flex; gap: 14px; align-items: flex-start; position: relative; padding-bottom: 20px; }
    .pj-flow li:last-child { padding-bottom: 0; }
    .pj-flow li:not(:last-child)::before { content: ""; position: absolute; left: 23px; top: 50px; bottom: 2px; width: 2px; background: rgba(255, 255, 255, 0.28); }
    .pj-flow-icon { width: 48px; height: 48px; flex-shrink: 0; border-radius: 12px; background: #fff; color: var(--primary); display: grid; place-items: center; font-size: 1.25rem; border-bottom: 3px solid var(--accent); }
    .pj-flow-text strong { display: block; font-size: 1.05rem; font-weight: 700; color: #fff; }
    .pj-flow-text span { display: block; font-size: 0.92rem; color: #D6E0F5; line-height: 1.5; }

    /* Tarjetas y listas */
    .pj-card { background: #fff; border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow); padding: 26px; }
    .pj-card h3 { font-size: 1.15rem; color: var(--dark); margin-bottom: 14px; line-height: 1.3; }
    .pj-card-red { border-top: 4px solid var(--accent); }
    .pj-card-blue { border-top: 4px solid var(--primary); }
    .pj-card-dark { border-top: 4px solid var(--primary-dark); }
    .pj-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .pj-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .pj-list { list-style: none; }
    .pj-list li { position: relative; padding-left: 22px; margin-bottom: 12px; color: var(--slate); line-height: 1.6; }
    .pj-list li:last-child { margin-bottom: 0; }
    .pj-list li::before { content: ""; position: absolute; left: 0; top: 0.62em; width: 9px; height: 9px; border-radius: 50%; background: var(--accent); }
    .pj-list-blue li::before { background: var(--primary); }
    .pj-checks { list-style: none; }
    .pj-checks li { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 12px; color: var(--slate); line-height: 1.6; }
    .pj-checks li:last-child { margin-bottom: 0; }
    .pj-checks i { color: var(--primary); margin-top: 0.3em; flex-shrink: 0; font-size: 0.9rem; }
    .pj-note { margin-top: 22px; padding: 16px 20px; background: var(--primary-light); border-left: 4px solid var(--accent); border-radius: 0 12px 12px 0; color: var(--primary-dark); font-weight: 500; line-height: 1.7; }
    .pj-chip { display: inline-block; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 12px; border-radius: 999px; background: var(--primary-light); color: var(--primary-dark); margin-bottom: 12px; }
    .pj-chip-red { background: var(--accent-light); color: var(--accent-dark); }
    .pj-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 16px; font-weight: 700; color: var(--action); text-decoration: none; padding: 6px 0; }
    .pj-link:hover { color: var(--action-hover); text-decoration: underline; }
    .pj-link:focus-visible, .pj-faq summary:focus-visible { outline: 3px solid var(--action); outline-offset: 3px; border-radius: 6px; }

    /* Cómo funciona */
    .pj-steps { list-style: none; display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
    .pj-step { background: #fff; border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow); padding: 24px 20px; }
    .pj-step-num { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 1.05rem; margin-bottom: 14px; border-bottom: 3px solid var(--accent); }
    .pj-step h3 { font-size: 1.08rem; color: var(--dark); margin-bottom: 8px; line-height: 1.3; }
    .pj-step p { color: var(--muted); font-size: 0.95rem; line-height: 1.65; }

    /* Lo que incluye */
    .pj-feature { display: flex; gap: 14px; align-items: flex-start; background: #fff; border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow); padding: 20px; }
    .pj-feature-icon { width: 46px; height: 46px; flex-shrink: 0; border-radius: 12px; background: var(--primary-light); color: var(--primary); display: grid; place-items: center; font-size: 1.15rem; }
    .pj-feature h3 { font-size: 1.02rem; color: var(--dark); margin-bottom: 4px; line-height: 1.3; }
    .pj-feature p { font-size: 0.93rem; color: var(--muted); line-height: 1.6; }

    /* Cifras */
    .pj-stats { background: var(--primary-light); border: 1px solid var(--action-border); border-radius: 16px; padding: 28px; }
    .pj-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; }
    .pj-stat { text-align: center; padding: 8px; }
    .pj-stat strong { display: block; font-size: 2.3rem; font-weight: 800; color: var(--primary-dark); line-height: 1.1; }
    .pj-stat span { display: block; margin-top: 6px; color: var(--muted); font-size: 0.95rem; line-height: 1.4; }

    /* Galería y video */
    .pj-gallery { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
    .pj-gallery img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 16px; border: 1px solid var(--border); background: var(--primary-light); display: block; }
    .pj-video { margin-top: 16px; }
    .pj-video video { width: 100%; max-height: 520px; border-radius: 16px; background: #000; display: block; }

    /* Preguntas frecuentes */
    .pj-faq { display: grid; gap: 12px; max-width: 880px; }
    .pj-faq details { background: #fff; border: 1px solid var(--border); border-radius: 14px; box-shadow: var(--shadow); }
    .pj-faq details[open] { border-color: var(--action-border); }
    .pj-faq summary { list-style: none; cursor: pointer; position: relative; display: flex; align-items: center; min-height: 56px; padding: 14px 54px 14px 20px; font-weight: 700; color: var(--dark); line-height: 1.4; }
    .pj-faq summary::-webkit-details-marker { display: none; }
    .pj-faq summary::after { content: "+"; content: "+" / ""; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 1.6rem; font-weight: 500; line-height: 1; color: var(--action); }
    .pj-faq details[open] summary::after { content: "\2212"; content: "\2212" / ""; }
    .pj-faq summary:hover { background: var(--action-soft); border-radius: 14px; }
    .pj-answer { padding: 0 20px 20px; color: var(--slate); line-height: 1.75; }
    .pj-answer a { display: inline-flex; margin-top: 10px; font-weight: 700; color: var(--action); }
    .pj-faq-more { margin-top: 18px; }

    @media (prefers-reduced-motion: no-preference) { html { scroll-behavior: smooth; } }

    @media (max-width: 980px) {
        .pj-hero-inner { grid-template-columns: 1fr; gap: 28px; }
        .pj-steps { grid-template-columns: repeat(2, 1fr); }
        .pj-grid-3 { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 760px) {
        .pj-grid-2, .pj-grid-3, .pj-steps { grid-template-columns: 1fr; }
        .pj-section { padding-top: 46px; }
    }
    @media (max-width: 520px) {
        .pj-hero { padding: 40px 0 46px; }
        .pj-actions .btn-portal { flex: 1 1 100%; }
        .pj-card, .pj-stats { padding: 20px; }
        .pj-flow { padding: 18px; }
    }
</style>

<!-- Hero -->
<section class="pj-hero" aria-labelledby="pj-title">
    <div class="pj-wrap pj-hero-inner">
        <div>
            <span class="pj-eyebrow"><i class="fas fa-robot" aria-hidden="true"></i> {{ __('project.hero.eyebrow') }}</span>
            <h1 id="pj-title">{{ __('project.hero.title') }}</h1>
            <p class="pj-lead">{{ __('project.hero.lead') }}</p>
            <div class="pj-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-portal btn-portal-lg btn-portal-light">
                        <i class="fas fa-gauge" aria-hidden="true"></i> {{ __('project.hero.cta_panel') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-portal btn-portal-lg btn-portal-light">
                        <i class="fas fa-sign-in-alt" aria-hidden="true"></i> {{ __('project.hero.cta_login') }}
                    </a>
                @endauth
                <a href="#preguntas" class="btn-portal btn-portal-lg btn-portal-ghost-light">
                    <i class="fas fa-circle-question" aria-hidden="true"></i> {{ __('project.hero.cta_faq') }}
                </a>
            </div>
        </div>

        <ol class="pj-flow" aria-label="{{ __('project.hero.flow_label') }}">
            @foreach(__('project.hero.flow') as $i => $piece)
                <li>
                    <span class="pj-flow-icon" aria-hidden="true"><i class="fas {{ ['fa-robot', 'fa-book-open', 'fa-mobile-screen-button'][$i] ?? 'fa-circle' }}"></i></span>
                    <div class="pj-flow-text">
                        <strong>{{ $piece['title'] }}</strong>
                        <span>{{ $piece['text'] }}</span>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

<!-- El problema y la propuesta -->
<section class="pj-section" id="problema" aria-labelledby="pj-problem-title">
    <div class="pj-wrap">
        <div class="pj-section-head">
            <h2 id="pj-problem-title">{{ __('project.problem.title') }}</h2>
            <p>{{ __('project.problem.intro') }}</p>
        </div>
        <div class="pj-grid-2">
            <div class="pj-card pj-card-red">
                <h3>{{ __('project.problem.challenge_title') }}</h3>
                <ul class="pj-list">
                    @foreach(__('project.problem.challenge') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="pj-card pj-card-blue">
                <h3>{{ __('project.problem.proposal_title') }}</h3>
                <ul class="pj-list pj-list-blue">
                    @foreach(__('project.problem.proposal') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <p class="pj-note">{{ __('project.problem.note') }}</p>
    </div>
</section>

<!-- Cómo funciona -->
<section class="pj-section" id="como-funciona" aria-labelledby="pj-how-title">
    <div class="pj-wrap">
        <div class="pj-section-head">
            <h2 id="pj-how-title">{{ __('project.how.title') }}</h2>
            <p>{{ __('project.how.intro') }}</p>
        </div>
        <ol class="pj-steps">
            @foreach(__('project.how.steps') as $i => $step)
                <li class="pj-step">
                    <span class="pj-step-num"><span class="pj-sr">{{ __('project.how.step_label') }} </span>{{ $i + 1 }}</span>
                    <h3>{{ $step['title'] }}</h3>
                    <p>{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>
        <p class="pj-note">{{ __('project.how.note') }}</p>
    </div>
</section>

<!-- Beneficios por público -->
<section class="pj-section" id="beneficios" aria-labelledby="pj-benefits-title">
    <div class="pj-wrap">
        <div class="pj-section-head">
            <h2 id="pj-benefits-title">{{ __('project.benefits.title') }}</h2>
            <p>{{ __('project.benefits.intro') }}</p>
        </div>
        <div class="pj-grid-3">
            @foreach(__('project.benefits.cards') as $i => $card)
                <article class="pj-card {{ ['pj-card-blue', 'pj-card-red', 'pj-card-dark'][$i] ?? 'pj-card-blue' }}">
                    <span class="pj-chip {{ $i === 1 ? 'pj-chip-red' : '' }}">{{ $card['label'] }}</span>
                    <h3>{{ $card['title'] }}</h3>
                    <ul class="pj-checks">
                        @foreach($card['items'] as $item)
                            <li><i class="fas fa-check" aria-hidden="true"></i><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                    @if(isset($card['link']) && Route::has('public.privacy'))
                        <a href="{{ route('public.privacy') }}" class="pj-link">{{ $card['link'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>

<!-- Lo que incluye hoy -->
<section class="pj-section" id="incluye" aria-labelledby="pj-features-title">
    <div class="pj-wrap">
        <div class="pj-section-head">
            <h2 id="pj-features-title">{{ __('project.features.title') }}</h2>
            <p>{{ __('project.features.intro') }}</p>
        </div>
        <div class="pj-grid-3">
            @foreach(__('project.features.items') as $feature)
                <div class="pj-feature">
                    <span class="pj-feature-icon" aria-hidden="true"><i class="fas {{ $feature['icon'] }}"></i></span>
                    <div>
                        <h3>{{ $feature['title'] }}</h3>
                        <p>{{ $feature['text'] }}</p>
                        {{-- PENDIENTE: enlace de descarga de la app (YURA_APP_URL); sin él no se muestra el botón. --}}
                        @if($feature['icon'] === 'fa-mobile-screen-button' && $appUrl)
                            <a href="{{ $appUrl }}" class="pj-link" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-download" aria-hidden="true"></i> {{ __('project.features.app_link') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@if($showStats)
    <!-- Cifras reales (solo si superan el mínimo configurado) -->
    <section class="pj-section" id="cifras" aria-labelledby="pj-stats-title">
        <div class="pj-wrap">
            <div class="pj-stats">
                <div class="pj-section-head">
                    <h2 id="pj-stats-title">{{ __('project.stats.title') }}</h2>
                    <p>{{ __('project.stats.intro') }}</p>
                </div>
                <ul class="pj-stats-grid" style="list-style: none;">
                    @foreach($statKeys as $key)
                        <li class="pj-stat">
                            <strong>{{ number_format((int) $stats[$key], 0, ',', '.') }}</strong>
                            <span>{{ __('project.stats.' . $key) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
@endif

{{-- PENDIENTE: fotos y video del robot (public/img/robot-yura-1.jpg, -2.jpg, -3.jpg y YURA_VIDEO_URL) --}}
@if($showMedia)
    <section class="pj-section" id="galeria" aria-labelledby="pj-media-title">
        <div class="pj-wrap">
            <div class="pj-section-head">
                <h2 id="pj-media-title">{{ __('project.media.title') }}</h2>
                <p>{{ __('project.media.intro') }}</p>
            </div>

            @if($photos->isNotEmpty())
                <div class="pj-gallery">
                    @foreach($photos as $n)
                        <img src="{{ asset("img/robot-yura-{$n}.jpg") }}" alt="{{ __('project.media.photo_alt', ['n' => $n]) }}" loading="lazy" decoding="async">
                    @endforeach
                </div>
            @endif

            @if($videoUrl)
                <div class="pj-video">
                    @if($videoIsFile)
                        <video controls preload="none" aria-label="{{ __('project.media.video_title') }}">
                            <source src="{{ $videoUrl }}">
                        </video>
                    @else
                        <div class="pj-card">
                            <h3>{{ __('project.media.video_title') }}</h3>
                            <p style="color: var(--muted); line-height: 1.7;">{{ __('project.media.video_text') }}</p>
                            <a href="{{ $videoUrl }}" class="btn-portal btn-portal-primary" target="_blank" rel="noopener noreferrer" style="margin-top: 14px;">
                                <i class="fas fa-circle-play" aria-hidden="true"></i> {{ __('project.media.video_cta') }}
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endif

<!-- Preguntas frecuentes -->
{{--
    PENDIENTE (dueño): confirmar con el equipo técnico si el robot guarda audio dentro de su propio equipo y por cuánto
    tiempo, y agregarlo a la respuesta «audio». Hoy solo se afirma lo comprobable: la plataforma no recibe ni envía audios
    (docs/yura-activity-contract-v1.md). Confirmar también las condiciones/costo para la respuesta «price».
--}}
<section class="pj-section" id="preguntas" aria-labelledby="pj-faq-title">
    <div class="pj-wrap">
        <div class="pj-section-head">
            <h2 id="pj-faq-title">{{ __('project.faq.title') }}</h2>
            <p>{{ __('project.faq.intro') }}</p>
        </div>
        <div class="pj-faq">
            @foreach(__('project.faq.items') as $item)
                <details>
                    <summary>{{ $item['q'] }}</summary>
                    <div class="pj-answer">
                        <p>{{ $item['a'] }}</p>
                        @if($item['id'] === 'audio' && Route::has('public.privacy'))
                            <a href="{{ route('public.privacy') }}">{{ __('project.faq.link_privacy') }}</a>
                        @endif
                        @if($item['id'] === 'app' && $appUrl)
                            <a href="{{ $appUrl }}" target="_blank" rel="noopener noreferrer">{{ __('project.faq.link_app') }}</a>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</section>

<div style="margin-top: 60px;">
    @include('public.partials.cta-band', ['title' => __('project.cta.title'), 'text' => __('project.cta.text')])
</div>
@endsection
