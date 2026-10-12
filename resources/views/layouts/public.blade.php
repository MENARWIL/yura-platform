<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'YURA Platform | Portal Educativo Institucional')</title>

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

        body {
            font-family: 'Outfit', sans-serif;
            color: var(--slate);
            background-color: var(--light);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Skip link for accessibility */
        .skip-to-content {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--primary-dark);
            color: white;
            padding: 8px 16px;
            z-index: 100;
            text-decoration: none;
            font-weight: 600;
            border-radius: 0 0 4px 0;
            transition: top 0.2s;
        }
        .skip-to-content:focus { top: 0; }

        /* Top Government Bar */
        .gov-bar {
            background-color: var(--dark);
            color: #D6E0F5;
            font-size: 0.85rem;
            padding: 6px 20px;
        }
        .gov-bar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .gov-bar a {
            color: #A3B1C9;
            text-decoration: none;
            margin-left: 15px;
            transition: color 0.2s;
        }
        .gov-bar a:hover { color: white; }

        /* Main Header */
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
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--dark);
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
        }
        .brand-text h1 {
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

        /* Nav menu */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
        }
        .nav-link {
            white-space: nowrap;
            color: var(--slate);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nav-link:hover, .nav-link:focus {
            color: var(--primary);
            background: var(--primary-light);
        }
        .nav-link.active {
            color: var(--primary-dark);
            background: var(--primary-light);
            box-shadow: inset 0 -3px 0 var(--accent);
        }

        /* Franja institucional rojo / blanco / azul */
        .tricolor-stripe {
            height: 5px;
            background: linear-gradient(90deg, var(--accent) 0 33.33%, var(--white) 33.33% 66.66%, var(--primary) 66.66% 100%);
        }

        .main-footer {
            border-top: 4px solid var(--accent);
        }

        .btn-portal {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.92rem;
            letter-spacing: 0.01em;
            text-decoration: none;
            transition: all 0.2s;
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
            color: var(--dark);
        }

        /* Main Container */
        .main-content {
            flex: 1;
        }

        /* Footer */
        .main-footer {
            background: var(--dark);
            color: #A3B1C9;
            padding: 50px 20px 20px;
            margin-top: 60px;
        }
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-col h3 {
            color: white;
            font-size: 1.05rem;
            margin-bottom: 16px;
            font-weight: 700;
        }
        .footer-col ul {
            list-style: none;
        }
        .footer-col ul li {
            margin-bottom: 10px;
        }
        .footer-col a {
            color: #A3B1C9;
            text-decoration: none;
            transition: color 0.2s;
            font-size: 0.92rem;
        }
        .footer-col a:hover {
            color: white;
        }
        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .header-container { flex-wrap: wrap; }
            .nav-menu { order: 3; width: 100%; overflow-x: auto; padding-top: 10px; }
            .footer-container { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 600px) {
            .footer-container { grid-template-columns: 1fr; }
            .gov-bar-container { flex-direction: column; gap: 4px; text-align: center; }
            .gov-bar a { margin: 0 6px; }
            .header-container > div:last-child { width: 100%; }
            .header-container > div:last-child .btn-portal { flex: 1; justify-content: center; }
        }
        body { overflow-x: hidden; }
    </style>
    @yield('styles')
</head>
<body>
    <a href="#main-content" class="skip-to-content">Saltar al contenido principal</a>

    <!-- Top Gov Bar -->
    <header class="gov-bar" role="banner">
        <div class="gov-bar-container">
            <span><i class="fas fa-shield-alt mr-1"></i> Portal Educativo Institucional &middot; Plataforma YURA</span>
            <div>
                <a href="{{ route('lang.set', ['locale' => 'es']) }}"><i class="fas fa-globe mr-1"></i> Castellano</a>
                <a href="{{ route('lang.set', ['locale' => 'qu']) }}">Quechua</a>
                <a href="#contacto"><i class="fas fa-headset mr-1"></i> Contacto</a>
            </div>
        </div>
    </header>
    <div class="tricolor-stripe" aria-hidden="true"></div>

    <!-- Main Navigation Header -->
    <nav class="main-header" aria-label="Navegación principal">
        <div class="header-container">
            <a href="{{ route('public.home') }}" class="brand-link">
                <div class="brand-icon">Y</div>
                <div class="brand-text">
                    <h1>YURA PLATFORM</h1>
                    <span>Gestión Educativa e Innovación Pedagógica</span>
                </div>
            </a>

            <ul class="nav-menu">
                <li><a href="{{ route('public.home') }}" class="nav-link {{ request()->routeIs('public.home') ? 'active' : '' }}"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="{{ route('public.services') }}" class="nav-link {{ request()->routeIs('public.services*') ? 'active' : '' }}"><i class="fas fa-th-large"></i> Servicios</a></li>
                <li><a href="{{ route('feria') }}" class="nav-link {{ request()->routeIs('feria') ? 'active' : '' }}"><i class="fas fa-robot"></i> Proyecto YURA</a></li>
                <li><a href="{{ route('public.documents') }}" class="nav-link {{ request()->routeIs('public.documents*') ? 'active' : '' }}"><i class="fas fa-balance-scale"></i> Normativa</a></li>
            </ul>

            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="{{ route('public.documents') }}" class="btn-portal btn-portal-outline">
                    <i class="fas fa-bullhorn"></i> Comunicados
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-portal btn-portal-primary">
                        <i class="fas fa-gauge"></i> Panel ({{ Auth::user()->name }})
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-portal btn-portal-primary">
                        <i class="fas fa-sign-in-alt"></i> Ingresar
                    </a>
                @endauth
            </div>
        </div>
    </nav>

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
        <div class="footer-container">
            <div class="footer-col">
                <h3>YURA PLATFORM</h3>
                <p style="margin-bottom: 15px; font-size: 0.92rem; line-height: 1.7;">
                    Plataforma institucional de gestión académica, acompañamiento pedagógico e innovación educativa para docentes, estudiantes y familias.
                </p>
                <p style="font-size: 0.85rem; color: #5B6474;">
                    Inspirada en el modelo de atención, transparencia y servicios educativos de Bolivia.
                </p>
            </div>
            <div class="footer-col">
                <h3>Servicios</h3>
                <ul>
                    <li><a href="{{ route('public.services', ['subsystem' => 'regular']) }}">Educación Regular</a></li>
                    <li><a href="{{ route('public.services', ['subsystem' => 'alternativa_especial']) }}">Alternativa y Especial</a></li>
                    <li><a href="{{ route('public.services', ['subsystem' => 'superior']) }}">Educación Superior</a></li>
                    <li><a href="{{ route('public.services', ['subsystem' => 'docentes']) }}">Portal Docente</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Enlaces</h3>
                <ul>
                    <li><a href="{{ route('feria') }}">Proyecto YURA</a></li>
                    <li><a href="{{ route('public.documents') }}">Resoluciones y Circulares</a></li>
                    <li><a href="{{ route('login') }}">Acceso al Sistema</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Contacto Institucional</h3>
                <p style="font-size: 0.9rem; margin-bottom: 8px;"><i class="fas fa-map-marker-alt mr-2 text-primary"></i> Distrito Educativo &middot; La Paz, Bolivia</p>
                <p style="font-size: 0.9rem; margin-bottom: 8px;"><i class="fas fa-phone mr-2 text-primary"></i> Ventanilla Única: +591 (2) 244-0000</p>
                <p style="font-size: 0.9rem; margin-bottom: 8px;"><i class="fas fa-envelope mr-2 text-primary"></i> contacto@yura-platform.edu.bo</p>
                <p style="font-size: 0.85rem; color: #5B6474; margin-top: 10px;">Horario de atención: Lunes a Viernes 08:30 - 16:30</p>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} YURA Platform. Todos los derechos reservados.</span>
            <span>Plataforma Segura &middot; Laravel 12 &middot; MySQL 8.0</span>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
