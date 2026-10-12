@extends('layouts.public')

@section('title', 'YURA Platform | Portal Educativo Institucional')

@section('content')
<style>
    /* Hero Section */
    .hero-section {
        background: linear-gradient(135deg, #082C6C 0%, #0B3D91 65%, #1450B4 100%);
        color: white;
        padding: 60px 20px 70px;
        position: relative;
    }
    .hero-container {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 40px;
        align-items: center;
    }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #C8102E;
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: #FFFFFF;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 20px;
    }
    .hero-title {
        font-size: clamp(2rem, 3.5vw, 2.8rem);
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: 18px;
        letter-spacing: -0.02em;
    }
    .hero-subtitle {
        font-size: 1.05rem;
        color: #D6E0F5;
        line-height: 1.7;
        margin-bottom: 28px;
    }

    /* Hero Search Bar */
    .search-box {
        background: white;
        border-radius: 12px;
        padding: 6px;
        display: flex;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        max-width: 550px;
    }
    .search-box input {
        flex: 1;
        border: none;
        padding: 12px 18px;
        font-size: 1rem;
        font-family: inherit;
        outline: none;
        border-radius: 8px;
        color: #1F2937;
    }
    .search-box button {
        background: linear-gradient(180deg, var(--action-top) 0%, var(--action) 100%);
        color: #FFFFFF;
        border: 1px solid var(--action);
        padding: 0 24px;
        border-radius: 8px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s;
    }
    .search-box button:hover {
        background: var(--action-hover);
    }

    /* Quick Action Cards */
    .quick-actions-bar {
        max-width: 1200px;
        margin: -35px auto 40px;
        padding: 0 20px;
        position: relative;
        z-index: 10;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }
    .quick-card {
        background: white;
        border-radius: var(--radius);
        padding: 22px 20px;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border);
        text-decoration: none;
        color: var(--slate);
        display: flex;
        align-items: flex-start;
        gap: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .quick-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 28px -4px rgba(0,0,0,0.1);
    }
    .quick-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .quick-icon-blue { background: #E7EEF9; color: #0B3D91; }
    .quick-icon-gold { background: #FCE8EB; color: #C8102E; }
    .quick-icon-green { background: #E7EEF9; color: #082C6C; }
    .quick-icon-purple { background: #FCE8EB; color: #9E0B24; }
    .quick-info strong { display: block; font-size: 1rem; color: var(--dark); margin-bottom: 2px; }
    .quick-info span { font-size: 0.82rem; color: var(--muted); line-height: 1.3; }

    /* Sections */
    .section-container {
        max-width: 1200px;
        margin: 50px auto 0;
        padding: 0 20px;
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 26px;
        border-bottom: 2px solid var(--border);
        padding-bottom: 12px;
    }
    .section-header h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--dark);
    }
    .section-header a {
        color: var(--primary);
        font-weight: 700;
        font-size: 0.92rem;
        text-decoration: none;
    }
    .section-header a:hover { text-decoration: underline; }

    /* Services Grid */
    .services-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px;
    }
    .service-card {
        background: white;
        border-radius: var(--radius);
        padding: 24px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        transition: transform 0.2s;
    }
    .service-card:hover { transform: translateY(-2px); }
    .service-tag {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 0.05em;
        color: var(--primary);
        margin-bottom: 10px;
        display: block;
    }
    .service-card h3 {
        font-size: 1.15rem;
        color: var(--dark);
        margin-bottom: 8px;
    }
    .service-card p {
        font-size: 0.9rem;
        color: var(--muted);
        line-height: 1.6;
        margin-bottom: 18px;
    }
    .service-card a {
        color: var(--primary);
        font-weight: 700;
        text-decoration: none;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* Documents List */
    .docs-list {
        background: white;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    .doc-item {
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border);
        text-decoration: none;
        color: inherit;
        transition: background 0.15s;
    }
    .doc-item:last-child { border-bottom: none; }
    .doc-item:hover { background: #F4F6FA; }
    .doc-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-right: 12px;
    }
    .doc-badge-resolucion { background: #E7EEF9; color: #082C6C; }
    .doc-badge-convocatoria { background: #FCE8EB; color: #9E0B24; }
    .doc-badge-circular { background: #F4F6FA; color: #1F2937; border: 1px solid #DDE3EC; }
    .doc-badge-comunicado { background: #FFFFFF; color: #C8102E; border: 1px solid #C8102E; }

    @media (max-width: 900px) {
        .hero-container { grid-template-columns: 1fr; }
        .quick-actions-bar { grid-template-columns: 1fr 1fr; }
        .services-grid { grid-template-columns: 1fr; }
    }
    .hero-container > * { min-width: 0; }
    .hero-title { overflow-wrap: break-word; }
    .search-box input { min-width: 0; }
    @media (max-width: 600px) {
        .quick-actions-bar { grid-template-columns: 1fr; }
        .hero-title { font-size: 1.7rem; }
        .search-box { max-width: 100%; }
        .search-box button { padding-left: 14px; padding-right: 14px; }
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-container">
        <div>
            <div class="hero-badge">
                <i class="fas fa-landmark"></i> Sistema Educativo Integrado
            </div>
            <h1 class="hero-title">Gestión Educativa, Seguimiento Académico y Servicios Institucionales</h1>
            <p class="hero-subtitle">
                Plataforma institucional de acompañamiento pedagógico, consultas familiares en línea y comunicación institucional con la comunidad educativa.
            </p>

            <!-- Search Bar -->
            <form action="{{ route('public.search') }}" method="GET" class="search-box">
                <input type="text" name="q" placeholder="Buscar normativas, servicios, comunicados o resoluciones..." aria-label="Búsqueda institucional" required>
                <button type="submit"><i class="fas fa-search"></i> Buscar</button>
            </form>
        </div>

        <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 16px; padding: 30px; backdrop-filter: blur(8px);">
            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 15px; color: white;">
                <i class="fas fa-bullseye text-primary mr-2"></i> Proyecto YURA en Aula
            </h2>
            <p style="font-size: 0.95rem; color: #D6E0F5; line-height: 1.7; margin-bottom: 20px;">
                Integración de tecnología interactiva y ASR para el fortalecimiento lingüístico del Quechua en estudiantes de sexto de primaria (U.E. Boliviano Holandés), con registro directo en el cuaderno pedagógico digital.
            </p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <span style="background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 6px; font-size: 0.85rem;"><i class="fas fa-check mr-1 text-success"></i> Calificaciones</span>
                <span style="background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 6px; font-size: 0.85rem;"><i class="fas fa-check mr-1 text-success"></i> Asistencia</span>
                <span style="background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 6px; font-size: 0.85rem;"><i class="fas fa-check mr-1 text-success"></i> Trazabilidad</span>
            </div>
        </div>
    </div>
</section>

<!-- Quick Actions -->
<section class="quick-actions-bar" aria-label="Accesos Rápidos">
    <a href="{{ route('feria') }}" class="quick-card">
        <div class="quick-icon quick-icon-blue"><i class="fas fa-robot"></i></div>
        <div class="quick-info">
            <strong>Proyecto YURA</strong>
            <span>Robot ASR para la práctica del quechua</span>
        </div>
    </a>
    <a href="{{ route('login') }}" class="quick-card">
        <div class="quick-icon quick-icon-gold"><i class="fas fa-user-graduate"></i></div>
        <div class="quick-info">
            <strong>Familias y Docentes</strong>
            <span>Notas, asistencia y cuaderno pedagógico</span>
        </div>
    </a>
    <a href="{{ route('public.documents') }}" class="quick-card">
        <div class="quick-icon quick-icon-green"><i class="fas fa-balance-scale"></i></div>
        <div class="quick-info">
            <strong>Normativa Oficial</strong>
            <span>Resoluciones, circulares y convocatorias</span>
        </div>
    </a>
    <a href="{{ route('login') }}" class="quick-card">
        <div class="quick-icon quick-icon-purple"><i class="fas fa-graduation-cap"></i></div>
        <div class="quick-info">
            <strong>Portal Académico</strong>
            <span>Acceso para docentes, directivos y tutores</span>
        </div>
    </a>
</section>

<!-- Educational Services Classified by Subsystem -->
<section class="section-container">
    <div class="section-header">
        <div>
            <h2>Servicios Educativos Institucionales</h2>
            <span style="color: var(--muted); font-size: 0.95rem;">Organizados por subsistemas y destinatarios de la comunidad</span>
        </div>
        <a href="{{ route('public.services') }}">Ver todos los servicios &rarr;</a>
    </div>

    <div class="services-grid">
        @foreach($services->flatten()->take(6) as $service)
            <div class="service-card">
                <span class="service-tag">{{ strtoupper($service->subsystem) }}</span>
                <h3>{{ $service->name }}</h3>
                <p>{{ $service->description }}</p>
                <a href="{{ $service->action_url }}">
                    Acceder al servicio <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i>
                </a>
            </div>
        @endforeach
    </div>
</section>

<!-- Recent Official Documents -->
<section class="section-container" style="margin-bottom: 50px;">
    <div class="section-header">
        <div>
            <h2>Documentación Oficial y Convocatorias</h2>
            <span style="color: var(--muted); font-size: 0.95rem;">Resoluciones administrativas, instructivos y avisos públicos</span>
        </div>
        <a href="{{ route('public.documents') }}">Ver repositorio completo &rarr;</a>
    </div>

    <div class="docs-list">
        @foreach($recentDocuments as $doc)
            <div class="doc-item">
                <div style="display: flex; align-items: center;">
                    <span class="doc-badge doc-badge-{{ $doc->category }}">{{ $doc->category }}</span>
                    <div>
                        <strong style="color: var(--dark); font-size: 0.98rem; display: block;">{{ $doc->title }}</strong>
                        <small style="color: var(--muted);"><i class="fas fa-barcode mr-1"></i> {{ $doc->code }} &middot; <i class="fas fa-calendar-alt mr-1"></i> Publicado: {{ $doc->published_at->format('d/m/Y') }}</small>
                    </div>
                </div>
                <a href="{{ route('public.documents', ['q' => $doc->code]) }}" class="btn-portal btn-portal-outline" style="padding: 6px 14px; font-size: 0.85rem;">
                    <i class="fas fa-eye"></i> Consultar
                </a>
            </div>
        @endforeach
    </div>
</section>
@endsection
