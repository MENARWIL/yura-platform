@extends('layouts.public')

@section('title', 'Resultados de Búsqueda | YURA Platform')

@section('content')
<div style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
    <div style="margin-bottom: 30px;">
        <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary); text-transform: uppercase;">Buscador Global Institucional</span>
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--dark); margin-top: 4px;">
            Resultados para: <span style="color: var(--primary);">"{{ $term }}"</span>
        </h1>
    </div>

    <!-- Search input -->
    <div style="margin-bottom: 35px;">
        <form action="{{ route('public.search') }}" method="GET" style="display: flex; gap: 10px; max-width: 600px;">
            <input type="text" name="q" value="{{ $term }}" placeholder="Buscar resoluciones, comunicados, servicios..." required
                style="flex: 1; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 1rem; outline: none;">
            <button type="submit" class="btn-portal btn-portal-primary">Buscar</button>
        </form>
    </div>

    <!-- Documents Results -->
    @if($documents->isNotEmpty())
        <div style="margin-bottom: 35px;">
            <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--dark); margin-bottom: 15px; border-bottom: 2px solid var(--border); padding-bottom: 8px;">
                <i class="fas fa-balance-scale text-primary mr-2"></i> Normativa y Documentos Oficiales ({{ $documents->count() }})
            </h2>
            <div style="display: grid; gap: 12px;">
                @foreach($documents as $doc)
                    <div style="background: white; border: 1px solid var(--border); border-radius: 8px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--dark); font-size: 1.05rem; display: block;">{{ $doc->title }}</strong>
                            <small style="color: var(--muted);"><i class="fas fa-barcode mr-1"></i> {{ $doc->code }} &middot; Categoría: {{ $doc->category }} &middot; Año: {{ $doc->year }}</small>
                        </div>
                        <a href="{{ route('public.documents', ['q' => $doc->code]) }}" class="btn-portal btn-portal-outline" style="padding: 6px 14px; font-size: 0.85rem;">
                            Ver Ficha
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Services Results -->
    @if($services->isNotEmpty())
        <div style="margin-bottom: 35px;">
            <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--dark); margin-bottom: 15px; border-bottom: 2px solid var(--border); padding-bottom: 8px;">
                <i class="fas fa-th-large text-primary mr-2"></i> Servicios Educativos ({{ $services->count() }})
            </h2>
            <div style="display: grid; gap: 12px;">
                @foreach($services as $srv)
                    <div style="background: white; border: 1px solid var(--border); border-radius: 8px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--dark); font-size: 1.05rem; display: block;">{{ $srv->name }}</strong>
                            <small style="color: var(--muted);">Subsistema: {{ strtoupper($srv->subsystem) }} &middot; {{ $srv->target_audience }}</small>
                        </div>
                        <a href="{{ $srv->action_url ?: '#' }}" class="btn-portal btn-portal-primary" style="padding: 6px 14px; font-size: 0.85rem;">
                            Acceder
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($documents->isEmpty() && $services->isEmpty())
        <div style="background: white; border-radius: var(--radius); padding: 50px 20px; text-align: center; border: 1px solid var(--border);">
            <i class="fas fa-search-minus" style="font-size: 3rem; color: #A3B1C9; margin-bottom: 15px;"></i>
            <h3 style="color: var(--dark);">No se encontraron coincidencias para "{{ $term }}"</h3>
            <p style="color: var(--muted); max-width: 500px; margin: 8px auto 20px;">
                Intente con términos más generales como "libreta", "asistencia", "robótica", "circular" o "comunicado".
            </p>
            <a href="{{ route('public.home') }}" class="btn-portal btn-portal-primary">Volver al Inicio</a>
        </div>
    @endif
</div>
@endsection
