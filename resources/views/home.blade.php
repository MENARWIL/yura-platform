<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YURA Platform | Gestión educativa para la feria</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --primary: #f9d423;
            --primary-dark: #d8a900;
            --secondary: #a90329;
            --secondary-dark: #7f0221;
            --ink: #1f1f2b;
            --muted: #5e6372;
            --bg: #f8f9fb;
            --surface: rgba(255, 255, 255, 0.92);
            --stroke: rgba(31, 31, 43, 0.08);
            --shadow: 0 20px 60px rgba(31, 31, 43, 0.12);
            --radius: 28px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            color: var(--ink);
            background:
                linear-gradient(135deg, rgba(249, 212, 35, 0.14), rgba(169, 3, 41, 0.08)),
                radial-gradient(circle at top left, rgba(249, 212, 35, 0.18), transparent 35%),
                var(--bg);
            min-height: 100vh;
        }

        .page-shell {
            max-width: 1180px;
            margin: 0 auto;
            padding: 28px 20px 56px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: var(--ink);
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), #ffe98f);
            box-shadow: var(--shadow);
        }

        .brand-title {
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .brand-subtitle {
            display: block;
            font-size: 0.88rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .nav-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 13px 18px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
            border: 1px solid transparent;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #ffe07a);
            color: #1d1400;
            box-shadow: 0 14px 34px rgba(249, 212, 35, 0.28);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: var(--ink);
            border-color: var(--stroke);
        }

        .hero {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 26px;
            align-items: center;
            margin-bottom: 30px;
        }

        .hero-card,
        .feature-card,
        .stat-card,
        .detail-card {
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            backdrop-filter: blur(8px);
        }

        .hero-card {
            padding: 34px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(249, 212, 35, 0.18);
            color: #704d00;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .hero h1 {
            font-size: clamp(2rem, 2.3vw, 2.5rem);
            line-height: 1.08;
            margin: 0 0 14px;
        }

        .hero p {
            color: var(--muted);
            font-size: 1.05rem;
            margin: 0 0 24px;
            line-height: 1.7;
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(31, 31, 43, 0.05);
            font-size: 0.93rem;
            font-weight: 600;
        }

        .hero-cta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .hero-visual {
            padding: 26px;
            display: grid;
            gap: 18px;
            background:
                linear-gradient(160deg, rgba(169, 3, 41, 0.08), rgba(249, 212, 35, 0.12)),
                var(--surface);
        }

        .visual-figure {
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(31, 31, 43, 0.96), rgba(169, 3, 41, 0.9));
            color: white;
            padding: 24px;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 12px;
        }

        .visual-figure .mini-title {
            font-size: 0.86rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.85;
        }

        .visual-figure h2 {
            margin: 0;
            font-size: 1.55rem;
        }

        .visual-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .mini-panel {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 18px;
            padding: 14px;
            border: 1px solid rgba(31, 31, 43, 0.08);
        }

        .mini-panel strong {
            display: block;
            margin-bottom: 4px;
            font-size: 1.1rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            padding: 22px;
        }

        .stat-card span {
            display: block;
            color: var(--muted);
            margin-bottom: 10px;
            font-size: 0.92rem;
        }

        .stat-card strong {
            font-size: 1.8rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 20px;
        }

        .feature-card,
        .detail-card {
            padding: 24px;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 1.3rem;
        }

        .section-copy {
            color: var(--muted);
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 14px;
        }

        .feature-list li {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            color: var(--ink);
        }

        .feature-list i {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(249, 212, 35, 0.18);
            color: #7d5900;
            flex-shrink: 0;
        }

        .detail-card h3 {
            margin: 0 0 12px;
            font-size: 1.1rem;
        }

        .detail-card ul {
            padding-left: 18px;
            color: var(--muted);
            line-height: 1.75;
        }

        .footer-note {
            margin-top: 28px;
            text-align: center;
            color: var(--muted);
            font-size: 0.97rem;
        }

        @media (max-width: 980px) {
            .hero,
            .content-grid,
            .stats {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .page-shell { padding: 20px 14px 40px; }
            .topbar { flex-direction: column; align-items: flex-start; }
            .hero-card,
            .feature-card,
            .detail-card,
            .stat-card,
            .hero-visual { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <header class="topbar">
            <a href="{{ route('home') }}" class="brand">
                <div class="brand-mark">Y</div>
                <div>
                    <span class="brand-title">YURA Platform</span>
                    <span class="brand-subtitle">Gestión educativa para docentes, estudiantes y familias</span>
                </div>
            </a>

            <nav class="nav-actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">
                        <i class="fa-solid fa-gauge"></i>
                        Ir al dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Iniciar sesión
                    </a>
                    <a href="#funcionalidades" class="btn btn-secondary">
                        <i class="fa-solid fa-circle-info"></i>
                        Ver funcionalidades
                    </a>
                @endauth
            </nav>
        </header>

        <section class="hero">
            <div class="hero-card">
                <div class="eyebrow">
                    <i class="fa-solid fa-bullseye"></i>
                    Plataforma real para feria y seguimiento académico
                </div>
                <h1>Un sistema completo para organizar, visualizar y acompañar el proceso educativo.</h1>
                <p>
                    YURA Platform centraliza estudiantes, docentes, padres y reportes en un entorno claro y profesional.
                    La interfaz está pensada para una presentación confiable en feria, con flujos de gestión y evidencias visuales listas para usar.
                </p>

                <div class="hero-badges">
                    <span class="badge"><i class="fa-solid fa-user-graduate"></i> Estudiantes</span>
                    <span class="badge"><i class="fa-solid fa-chalkboard-user"></i> Docentes</span>
                    <span class="badge"><i class="fa-solid fa-users"></i> Familias</span>
                    <span class="badge"><i class="fa-solid fa-file-lines"></i> Reportes</span>
                </div>

                <div class="hero-cta">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">
                            <i class="fa-solid fa-arrow-right"></i>
                            Entrar al panel
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            <i class="fa-solid fa-arrow-right"></i>
                            Acceder al sistema
                        </a>
                    @endauth
                    <a href="#funcionalidades" class="btn btn-secondary">
                        <i class="fa-solid fa-list-check"></i>
                        Explorar módulos
                    </a>
                </div>
            </div>

            <div class="hero-visual">
                <div class="visual-figure">
                    <div class="mini-title">Vista de presentación</div>
                    <h2>Seguimiento pedagógico con identidad visual propia.</h2>
                    <p style="margin: 0; color: rgba(255,255,255,0.88); line-height: 1.7;">
                        La portada ya refleja el proyecto real, con mensajes y estructura orientados a la feria y a la app en desarrollo.
                    </p>
                </div>

                <div class="visual-grid">
                    <div class="mini-panel">
                        <strong>+12</strong>
                        <span>Procesos visibles para el equipo académico.</span>
                    </div>
                    <div class="mini-panel">
                        <strong>100%</strong>
                        <span>Enfoque en flujo de trabajo y evidencia.</span>
                    </div>
                    <div class="mini-panel">
                        <strong>2</strong>
                        <span>Canales de administración principales.</span>
                    </div>
                    <div class="mini-panel">
                        <strong>API</strong>
                        <span>Preparado para sincronización futura.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats">
            <div class="stat-card">
                <span>Control pedagógico</span>
                <strong>Estudiantes</strong>
            </div>
            <div class="stat-card">
                <span>Gestión operativa</span>
                <strong>Usuarios</strong>
            </div>
            <div class="stat-card">
                <span>Información visual</span>
                <strong>Fotos</strong>
            </div>
            <div class="stat-card">
                <span>Presentación final</span>
                <strong>Feria</strong>
            </div>
        </section>

        <section id="funcionalidades" class="content-grid">
            <div class="feature-card">
                <h2 class="section-title">Funciones destacadas</h2>
                <p class="section-copy">
                    El proyecto ya está alineado con el flujo real del negocio: administración, personas, reportes y soporte de medios.
                </p>
                <ul class="feature-list">
                    <li>
                        <i class="fa-solid fa-id-card"></i>
                        <div>
                            <strong>Gestión de estudiantes</strong><br>
                            Alta, edición, eliminación lógica, restauración y exportación de reportes.
                        </div>
                    </li>
                    <li>
                        <i class="fa-solid fa-users-gear"></i>
                        <div>
                            <strong>Administración de usuarios</strong><br>
                            Control administrativo con roles, activación y carga de fotos.
                        </div>
                    </li>
                    <li>
                        <i class="fa-solid fa-camera"></i>
                        <div>
                            <strong>Archivos multimedia</strong><br>
                            Imágenes para estudiantes, padres y docentes con soporte visual en las vistas.
                        </div>
                    </li>
                    <li>
                        <i class="fa-solid fa-file-export"></i>
                        <div>
                            <strong>Exportación y reportes</strong><br>
                            Salidas listas para documentación y demostración del proyecto.
                        </div>
                    </li>
                </ul>
            </div>

            <div class="detail-card">
                <h3>Estado del proyecto para la feria</h3>
                <ul>
                    <li>Portada alineada al proyecto real y lista para presentación.</li>
                    <li>Rutas principales funcionando sin redirecciones innecesarias.</li>
                    <li>Diseño coherente con la identidad visual del sistema.</li>
                    <li>Preparado para sincronizar con la app móvil en la siguiente etapa.</li>
                </ul>

                <h3 style="margin-top: 24px;">Próximo paso recomendado</h3>
                <p class="section-copy" style="margin-bottom: 0;">
                    Validar el flujo de autenticación y la sincronización de datos con la app móvil, para que la feria muestre una experiencia consistente entre web y móvil.
                </p>
            </div>
        </section>

        <p class="footer-note">
            YURA Platform · proyecto listo para feria · preparado para la siguiente etapa de sincronización.
        </p>
    </div>
</body>
</html>
