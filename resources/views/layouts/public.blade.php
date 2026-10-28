<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    /*
     * Metadatos de la página. Cada vista puede definir: title, meta_description,
     * og_image, canonical, og_type y robots; todos tienen valor por defecto.
     * Se decodifica y se vuelve a escapar para que valga igual @section('x', 'texto')
     * que el formato en bloque.
     */
    $yuraSection = fn (string $name): string => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($__env->yieldContent($name)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

    $pageTitle = $yuraSection('title') ?: __('layout.default_title');
    $metaDescription = $yuraSection('meta_description') ?: __('layout.meta_description');
    $ogType = $yuraSection('og_type') ?: 'website';
    $robots = $yuraSection('robots');

    $canonicalUrl = $yuraSection('canonical')
        ?: (request()->routeIs('home') ? route('public.home') : url()->current());

    $ogImageCustom = $yuraSection('og_image');
    if ($ogImageCustom !== '') {
        $ogImage = preg_match('#^https?://#i', $ogImageCustom) ? $ogImageCustom : asset(ltrim($ogImageCustom, '/'));
    } else {
        $ogImage = asset('img/og-yura.png');
    }
    $ogImageIsDefault = $ogImageCustom === '';

    $ogLocale = app()->getLocale() === 'qu' ? 'qu_PE' : 'es_LA';

    /*
     * Datos de contacto configurados (config/yura.php, desde .env).
     * Lo que no esté configurado simplemente no se muestra.
     */
    $yuraSafeUrl = fn ($u) => preg_match('#^https?://#i', trim((string) $u)) ? trim((string) $u) : null;
    $yuraWa = preg_replace('/\D+/', '', (string) config('yura.contact.whatsapp'));
    $yuraWa = (strlen($yuraWa) >= 8 && strlen($yuraWa) <= 15) ? $yuraWa : null;
    $yuraEmail = filter_var(trim((string) config('yura.contact.email')), FILTER_VALIDATE_EMAIL) ?: null;
    $yuraAddress = trim((string) config('yura.contact.address')) ?: null;
    $yuraHours = trim((string) config('yura.contact.hours')) ?: null;
    $yuraOrg = trim((string) config('yura.org_name')) ?: null;
    $yuraAppUrl = $yuraSafeUrl(config('yura.app_url'));
    $yuraSocial = array_filter([
        'facebook' => $yuraSafeUrl(config('yura.social.facebook')),
        'tiktok' => $yuraSafeUrl(config('yura.social.tiktok')),
        'youtube' => $yuraSafeUrl(config('yura.social.youtube')),
    ]);
    $yuraHasContact = $yuraWa || $yuraEmail || $yuraAddress || $yuraHours || $yuraSocial;

    $structuredData = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $yuraOrg ?: 'YURA Platform',
        'alternateName' => $yuraOrg ? 'YURA Platform' : null,
        'url' => route('public.home'),
        'description' => __('layout.meta_description'),
        'sameAs' => $yuraSocial ? array_values($yuraSocial) : null,
    ]);
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if($robots !== '')
        <meta name="robots" content="{{ $robots }}">
    @endif
    <meta name="theme-color" content="#0B3D91">

    <!-- Ícono de la marca -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Open Graph (vista previa en WhatsApp, Facebook y otros) -->
    <meta property="og:site_name" content="YURA Platform">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:locale" content="{{ $ogLocale }}">
    <meta property="og:image" content="{{ $ogImage }}">
    @if($ogImageIsDefault)
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif
    <meta property="og:image:alt" content="{{ __('layout.og_image_alt') }}">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    <script>document.documentElement.className += ' js';</script>

    <!-- Google Font: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 local -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <style>
        :root {
            --primary: #0B3D91;
            --primary-dark: #082C6C;
            --primary-light: #E7EEF9;
            --secondary: #C8102E;
            --accent: #C8102E;
            --accent-dark: #9E0B24;
            --accent-light: #FCE8EB;
            --action: #1E56D6;
            --action-top: #2F6FED;
            --action-hover: #1847B5;
            --action-soft: #EEF3FD;
            --action-border: #C9D6F2;
            --action-shadow: 0 1px 2px rgba(16, 24, 40, 0.08), 0 6px 16px -4px rgba(30, 86, 214, 0.45);
            --dark: #082C6C;
            --slate: #1F2937;
            --muted: #5B6474;
            --light: #F4F6FA;
            --white: #ffffff;
            --border: #DDE3EC;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-padding-top: 90px; }

        body {
            font-family: 'Outfit', sans-serif;
            color: var(--slate);
            background-color: var(--light);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Foco visible para teclado en todo el portal */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 3px solid var(--action);
            outline-offset: 2px;
        }

        /* Skip link for accessibility */
        .skip-to-content {
            position: absolute;
            top: -48px;
            left: 0;
            background: var(--primary-dark);
            color: white;
            padding: 10px 16px;
            z-index: 100;
            text-decoration: none;
            font-weight: 600;
            border-radius: 0 0 4px 0;
            transition: top 0.2s;
        }
        .skip-to-content:focus { top: 0; outline-color: var(--white); }

        /* Barra superior */
        .top-bar {
            background-color: var(--dark);
            color: #D6E0F5;
            font-size: 0.85rem;
            padding: 4px 0;
        }
        .top-bar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .top-bar-tagline i { margin-right: 6px; }
        .top-bar-links { display: flex; align-items: center; gap: 4px; }
        .top-bar a {
            color: #D6E0F5;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 28px;
            transition: color 0.2s, background 0.2s;
        }
        .top-bar a:hover { color: #FFFFFF; background: rgba(255, 255, 255, 0.12); }
        .top-bar a:focus-visible { outline-color: var(--white); outline-offset: 0; }
        .top-bar a.is-active {
            color: #FFFFFF;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 4px;
            text-decoration-thickness: 2px;
        }

        /* Cabecera principal */
        .main-header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow);
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--dark);
            flex-shrink: 0;
            border-radius: 10px;
        }
        .brand-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 1.3rem;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(11, 61, 145, 0.3);
            flex-shrink: 0;
        }
        .brand-name {
            display: block;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--dark);
            line-height: 1.2;
        }
        .brand-text span {
            font-size: 0.82rem;
            color: var(--muted);
            display: block;
        }
        .brand-text .brand-name { font-size: 1.25rem; color: var(--dark); }

        /* Menú */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 6px;
            list-style: none;
        }
        .nav-link {
            white-space: nowrap;
            color: var(--slate);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 8px 14px;
            min-height: 44px;
            border-radius: 8px;
            transition: color 0.2s, background 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-link:hover, .nav-link:focus-visible {
            color: var(--primary);
            background: var(--primary-light);
        }
        .nav-link.active {
            color: var(--primary-dark);
            background: var(--primary-light);
            box-shadow: inset 0 -3px 0 var(--accent);
        }
        .nav-contact-mobile { display: none; }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-shrink: 0;
        }
        .header-actions .btn-user {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Botón de menú (solo móvil, y solo si hay JavaScript) */
        .nav-toggle {
            display: none;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--action-border);
            border-radius: 10px;
            background: var(--white);
            color: var(--primary-dark);
            font-size: 1.15rem;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }
        .nav-toggle:hover { background: var(--action-soft); border-color: var(--action); }

        /* Franja institucional rojo / blanco / azul */
        .tricolor-stripe {
            height: 5px;
            background: linear-gradient(90deg, var(--accent) 0 33.33%, var(--white) 33.33% 66.66%, var(--primary) 66.66% 100%);
        }

        .main-footer {
            border-top: 4px solid var(--accent);
        }

        /* Botones */
        .btn-portal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 18px;
            min-height: 44px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.92rem;
            line-height: 1.3;
            letter-spacing: 0.01em;
            text-decoration: none;
            transition: background 0.2s, border-color 0.2s, color 0.2s, transform 0.2s, box-shadow 0.2s;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .btn-portal-primary {
            background: linear-gradient(180deg, var(--action-top) 0%, var(--action) 100%);
            border-color: var(--action);
            color: #FFFFFF;
            box-shadow: var(--action-shadow);
        }
        .btn-portal-primary:hover {
            background: var(--action-hover);
            border-color: var(--action-hover);
            color: #FFFFFF;
            transform: translateY(-1px);
        }
        .btn-portal-outline {
            border-color: var(--action-border);
            color: var(--action);
            background: white;
        }
        .btn-portal-outline:hover {
            background: var(--action-soft);
            border-color: var(--action);
            color: var(--action-hover);
        }

        /* Más grande: para llamados a la acción */
        .btn-portal-lg {
            padding: 13px 28px;
            min-height: 52px;
            font-size: 1.05rem;
            border-radius: 12px;
        }

        /* Sobre fondo azul: relleno blanco (acción principal) */
        .btn-portal-light {
            background: var(--white);
            border-color: var(--white);
            color: var(--primary-dark);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15), 0 6px 16px -4px rgba(0, 0, 0, 0.35);
        }
        .btn-portal-light:hover {
            background: var(--primary-light);
            border-color: var(--primary-light);
            color: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Sobre fondo azul: solo borde blanco (acción secundaria) */
        .btn-portal-ghost-light {
            background: transparent;
            border-color: var(--white);
            box-shadow: inset 0 0 0 1px var(--white);
            color: var(--white);
        }
        .btn-portal-ghost-light:hover {
            background: rgba(255, 255, 255, 0.16);
            color: var(--white);
            transform: translateY(-1px);
        }

        .btn-portal-light:focus-visible,
        .btn-portal-ghost-light:focus-visible {
            outline: 3px solid var(--white);
            outline-offset: 3px;
        }

        /* Franja de contacto reutilizable (parcial public.partials.cta-band) */
        .cta-band {
            max-width: 1200px;
            margin: 50px auto 0;
            padding: 0 20px;
        }
        .cta-band-inner {
            background: var(--primary-dark);
            border-top: 4px solid var(--accent);
            border-radius: 16px;
            padding: 36px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px 40px;
            flex-wrap: wrap;
            color: #D6E0F5;
            box-shadow: var(--shadow-lg);
        }
        .cta-band-copy { flex: 1 1 340px; min-width: 0; }
        .cta-band-copy h2 {
            color: var(--white);
            font-size: clamp(1.4rem, 2.6vw, 1.9rem);
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.01em;
            margin-bottom: 8px;
        }
        .cta-band-copy p { font-size: 1.02rem; line-height: 1.6; }
        .cta-band-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }

        /* Main Container */
        .main-content {
            flex: 1;
        }

        /* Footer */
        .main-footer {
            background: var(--dark);
            color: #D6E0F5;
            padding: 50px 0 20px;
            margin-top: 60px;
        }
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1.7fr 1fr 1fr 1.4fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-container.footer-no-contact { grid-template-columns: 1.7fr 1fr 1fr; }
        .footer-col h2 {
            color: white;
            font-size: 1.05rem;
            margin-bottom: 16px;
            font-weight: 700;
        }
        .footer-col p { font-size: 0.92rem; line-height: 1.7; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 4px; }
        .footer-col a {
            color: #D6E0F5;
            text-decoration: none;
            transition: color 0.2s;
            font-size: 0.92rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 32px;
            border-radius: 4px;
        }
        .footer-col a:hover { color: #FFFFFF; text-decoration: underline; text-underline-offset: 3px; }
        .footer-col a:focus-visible, .footer-bottom a:focus-visible { outline-color: var(--white); }
        .footer-contact li { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; font-size: 0.92rem; line-height: 1.5; }
        .footer-contact li > i { color: #FFFFFF; width: 16px; text-align: center; margin-top: 5px; flex-shrink: 0; }
        .footer-contact li a { min-height: 0; }
        .footer-social { display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
        .footer-social a {
            width: 44px;
            height: 44px;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 10px;
            font-size: 1.1rem;
            text-decoration: none !important;
        }
        .footer-social a:hover { background: rgba(255, 255, 255, 0.14); border-color: #FFFFFF; }
        .footer-bottom {
            width: calc(100% - 40px);
            max-width: 1160px;
            margin: 0 auto;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            flex-wrap: wrap;
            gap: 10px;
        }
        .footer-bottom a { color: #D6E0F5; text-decoration: underline; text-underline-offset: 3px; }
        .footer-bottom a:hover { color: #FFFFFF; }
        .footer-version { font-weight: 700; letter-spacing: .5px; color: #D6E0F5; }

        /* Responsive */
        @media (max-width: 1180px) {
            .brand-text span:not(.brand-name) { display: none; }
        }
        @media (max-width: 1040px) {
            .header-container { flex-wrap: wrap; gap: 8px 10px; }
            .brand-link { margin-right: auto; min-width: 0; }
            .header-actions { order: 2; }
            .header-actions .btn-portal-outline { display: none; }
            .nav-menu {
                order: 4;
                flex-basis: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 2px;
                padding: 8px 0 4px;
                border-top: 1px solid var(--border);
            }
            .nav-link { white-space: normal; padding: 10px 14px; }
            .nav-contact-mobile { display: block; }
            .js .nav-toggle { display: inline-flex; order: 3; }
            .js .nav-menu { display: none; }
            .js .nav-menu.is-open { display: flex; }
        }
        @media (max-width: 900px) {
            .footer-container, .footer-container.footer-no-contact { grid-template-columns: 1fr 1fr; }
            .footer-col:first-child { grid-column: 1 / -1; }
        }
        @media (max-width: 600px) {
            .top-bar-container { justify-content: center; }
            .top-bar-tagline { display: none; }
            .header-container { padding: 10px 16px; }
            .brand-icon { width: 40px; height: 40px; font-size: 1.2rem; }
            .brand-text .brand-name { font-size: 1.05rem; }
            .header-actions .btn-user { display: none; }
            .footer-container, .footer-container.footer-no-contact { grid-template-columns: 1fr; gap: 28px; }
            .cta-band { margin-top: 36px; padding: 0 16px; }
            .cta-band-inner { padding: 28px 22px; }
            .cta-band-actions { width: 100%; flex-direction: column; align-items: stretch; }
            .cta-band-actions .btn-portal { width: 100%; }
        }
        @media (max-width: 380px) {
            .brand-text .brand-name { font-size: 0.98rem; }
            .header-actions .btn-portal { padding: 9px 12px; }
            .header-actions .btn-portal i { display: none; }
        }
        @media (max-width: 340px) {
            .header-container { gap: 6px; padding-left: 12px; padding-right: 12px; }
            .brand-link { gap: 8px; }
            .brand-icon { width: 36px; height: 36px; font-size: 1.1rem; }
            .brand-text .brand-name { font-size: 0.85rem; letter-spacing: -0.03em; }
            .header-actions .btn-portal { padding: 9px 10px; }
        }
        body { overflow-x: hidden; }

        /* Respeta a quien prefiere menos movimiento */
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
            .btn-portal:hover { transform: none; }
        }
    </style>
    @yield('styles')
</head>
<body>
    <a href="#main-content" class="skip-to-content">{{ __('layout.skip_link') }}</a>

    <!-- Barra superior: idioma -->
    <div class="top-bar">
        <div class="top-bar-container">
            <span class="top-bar-tagline"><i class="fas fa-robot" aria-hidden="true"></i>{{ __('layout.topbar_tagline') }}</span>
            <div class="top-bar-links" role="group" aria-label="{{ __('layout.language') }}">
                @foreach(['es' => 'lang_es', 'qu' => 'lang_qu'] as $langCode => $langKey)
                    <a href="{{ route('lang.set', ['locale' => $langCode]) }}" lang="{{ $langCode }}"@if(app()->getLocale() === $langCode) class="is-active" aria-current="true"@endif>@if($langCode === 'es')<i class="fas fa-globe" aria-hidden="true"></i>@endif{{ __('layout.'.$langKey) }}</a>
                @endforeach
                {{-- PENDIENTE: el enlace a Contacto solo aparece si hay datos de contacto en config/yura.php (.env: YURA_WHATSAPP, YURA_EMAIL, YURA_ADDRESS, YURA_HOURS, YURA_FACEBOOK...) --}}
                @if($yuraHasContact)
                    <a href="#contacto"><i class="fas fa-headset" aria-hidden="true"></i>{{ __('layout.topbar_contact') }}</a>
                @endif
            </div>
        </div>
    </div>
    <div class="tricolor-stripe" aria-hidden="true"></div>

    <!-- Cabecera principal -->
    <header class="main-header">
        <nav aria-label="{{ __('layout.nav_aria') }}">
            <div class="header-container">
                <a href="{{ route('public.home') }}" class="brand-link">
                    <span class="brand-icon" aria-hidden="true">Y</span>
                    <span class="brand-text">
                        <span class="brand-name">YURA PLATFORM</span>
                        <span>{{ __('layout.brand_tagline') }}</span>
                    </span>
                </a>

                <ul class="nav-menu" id="primary-nav">
                    <li><a href="{{ route('public.home') }}" class="nav-link {{ request()->routeIs('public.home', 'home') ? 'active' : '' }}" @if(request()->routeIs('public.home', 'home')) aria-current="page" @endif><i class="fas fa-home" aria-hidden="true"></i> {{ __('layout.nav_home') }}</a></li>
                    <li><a href="{{ route('public.services') }}" class="nav-link {{ request()->routeIs('public.services*') ? 'active' : '' }}" @if(request()->routeIs('public.services*')) aria-current="page" @endif><i class="fas fa-th-large" aria-hidden="true"></i> {{ __('layout.nav_services') }}</a></li>
                    <li><a href="{{ route('feria') }}" class="nav-link {{ request()->routeIs('feria') ? 'active' : '' }}" @if(request()->routeIs('feria')) aria-current="page" @endif><i class="fas fa-robot" aria-hidden="true"></i> {{ __('layout.nav_project') }}</a></li>
                    <li><a href="{{ route('public.documents') }}" class="nav-link {{ request()->routeIs('public.documents*') ? 'active' : '' }}" @if(request()->routeIs('public.documents*')) aria-current="page" @endif><i class="fas fa-file-lines" aria-hidden="true"></i> {{ __('layout.nav_documents') }}</a></li>
                    @if($yuraHasContact)
                        <li class="nav-contact-mobile"><a href="#contacto" class="nav-link"><i class="fas fa-headset" aria-hidden="true"></i> {{ __('layout.nav_contact') }}</a></li>
                    @endif
                </ul>

                <div class="header-actions">
                    @if($yuraHasContact)
                        <a href="#contacto" class="btn-portal btn-portal-outline">
                            <i class="fas fa-headset" aria-hidden="true"></i> {{ __('layout.nav_contact') }}
                        </a>
                    @endif
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-portal btn-portal-primary">
                            <i class="fas fa-gauge" aria-hidden="true"></i> {{ __('layout.panel') }}<span class="btn-user">({{ Auth::user()->name }})</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-portal btn-portal-primary">
                            <i class="fas fa-sign-in-alt" aria-hidden="true"></i> {{ __('layout.login') }}
                        </a>
                    @endauth
                </div>

                <button type="button" class="nav-toggle" id="nav-toggle" aria-controls="primary-nav" aria-expanded="false" aria-label="{{ __('layout.menu') }}">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
            </div>
        </nav>
    </header>

    <!-- Content -->
    <main id="main-content" class="main-content">
        @if(session('success'))
            <div style="max-width: 1200px; margin: 20px auto 0; padding: 14px 20px; background: #ecfdf5; border-left: 4px solid #10b981; color: #065f46; border-radius: 6px;" role="alert">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="max-width: 1200px; margin: 20px auto 0; padding: 14px 20px; background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; border-radius: 6px;" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="main-footer" id="contacto">
        <div class="footer-container {{ $yuraHasContact ? '' : 'footer-no-contact' }}">
            <div class="footer-col">
                <h2>YURA PLATFORM</h2>
                <p>{{ __('layout.footer_about') }}</p>
            </div>
            <div class="footer-col">
                <h2>{{ __('layout.footer_explore') }}</h2>
                <ul>
                    <li><a href="{{ route('public.home') }}">{{ __('layout.nav_home') }}</a></li>
                    <li><a href="{{ route('public.services') }}">{{ __('layout.nav_services') }}</a></li>
                    <li><a href="{{ route('feria') }}">{{ __('layout.nav_project') }}</a></li>
                    <li><a href="{{ route('public.documents') }}">{{ __('layout.nav_documents') }}</a></li>
                    <li><a href="{{ route('public.search') }}">{{ __('layout.footer_search') }}</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h2>{{ __('layout.footer_help') }}</h2>
                <ul>
                    <li><a href="{{ route('login') }}">{{ __('layout.footer_login') }}</a></li>
                    <li><a href="{{ route('public.privacy') }}">{{ __('layout.footer_privacy') }}</a></li>
                    {{-- PENDIENTE: el enlace de descarga de la app aparece cuando se define YURA_APP_URL --}}
                    @if($yuraAppUrl)
                        <li><a href="{{ $yuraAppUrl }}" target="_blank" rel="noopener noreferrer"><i class="fas fa-download" aria-hidden="true"></i> {{ __('layout.footer_download') }}</a></li>
                    @endif
                </ul>
            </div>
            @if($yuraHasContact)
                <div class="footer-col">
                    <h2>{{ __('layout.footer_contact') }}</h2>
                    <ul class="footer-contact">
                        @if($yuraWa)
                            <li><i class="fab fa-whatsapp" aria-hidden="true"></i>
                                <a href="https://wa.me/{{ $yuraWa }}?text={{ rawurlencode(__('layout.whatsapp_message')) }}" target="_blank" rel="noopener noreferrer">{{ __('layout.whatsapp') }}: +{{ $yuraWa }}</a></li>
                        @endif
                        @if($yuraEmail)
                            <li><i class="fas fa-envelope" aria-hidden="true"></i>
                                <a href="mailto:{{ $yuraEmail }}">{{ $yuraEmail }}</a></li>
                        @endif
                        @if($yuraAddress)
                            <li><i class="fas fa-location-dot" aria-hidden="true"></i> <span>{{ $yuraAddress }}</span></li>
                        @endif
                        @if($yuraHours)
                            <li><i class="fas fa-clock" aria-hidden="true"></i> <span>{{ $yuraHours }}</span></li>
                        @endif
                    </ul>
                    @if($yuraSocial)
                        <div class="footer-social" role="group" aria-label="{{ __('layout.footer_social') }}">
                            @isset($yuraSocial['facebook'])
                                <a href="{{ $yuraSocial['facebook'] }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                            @endisset
                            @isset($yuraSocial['tiktok'])
                                <a href="{{ $yuraSocial['tiktok'] }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fab fa-tiktok" aria-hidden="true"></i></a>
                            @endisset
                            @isset($yuraSocial['youtube'])
                                <a href="{{ $yuraSocial['youtube'] }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="fab fa-youtube" aria-hidden="true"></i></a>
                            @endisset
                        </div>
                    @endif
                </div>
            @endif
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} YURA Platform{{ $yuraOrg ? ' · '.$yuraOrg : '' }}. {{ __('layout.footer_rights') }}</span>
            <a href="{{ route('public.privacy') }}">{{ __('layout.footer_privacy') }}</a>
            <span class="footer-version" title="Versión de la plataforma">V {{ config('app.version') }}</span>
        </div>
    </footer>

    <script>
        (function () {
            var toggle = document.getElementById('nav-toggle');
            var menu = document.getElementById('primary-nav');
            if (!toggle || !menu) { return; }

            function setOpen(open) {
                menu.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                var icon = toggle.querySelector('i');
                if (icon) { icon.className = open ? 'fas fa-xmark' : 'fas fa-bars'; }
            }

            toggle.addEventListener('click', function () {
                setOpen(!menu.classList.contains('is-open'));
            });
            menu.addEventListener('click', function (e) {
                if (e.target.closest && e.target.closest('a')) { setOpen(false); }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && menu.classList.contains('is-open')) {
                    setOpen(false);
                    toggle.focus();
                }
            });
            if (window.matchMedia) {
                var wide = window.matchMedia('(min-width: 1041px)');
                var reset = function (m) { if (m.matches) { setOpen(false); } };
                if (wide.addEventListener) { wide.addEventListener('change', reset); }
            }
        })();
    </script>

    @yield('scripts')
</body>
</html>
