@extends('layouts.public')

@section('title', 'Repositorio de Normativa Oficial | YURA Platform')

@section('content')
<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <div style="margin-bottom: 30px;">
        <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary); text-transform: uppercase;">Transparencia y Normativa</span>
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--dark); margin-top: 4px;">Repositorio Documental Institucional</h1>
        <p style="color: var(--muted); font-size: 1rem;">
            Consulte y descargue resoluciones administrativas, circulares, convocatorias públicas e instructivos oficiales.
        </p>
    </div>

    <!-- Filters Bar -->
    <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 22px; box-shadow: var(--shadow); margin-bottom: 30px;">
        <form action="{{ route('public.documents') }}" method="GET" style="display: grid; grid-template-columns: 2fr 1.2fr 1fr auto; gap: 14px; align-items: end;">
            <div>
                <label for="q" style="display: block; font-weight: 700; font-size: 0.88rem; color: var(--slate); margin-bottom: 6px;">Palabra Clave o Código</label>
                <input type="text" name="q" id="q" value="{{ $search }}" placeholder="Ej. Calendario, RA-001..." 
                    style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 0.95rem; outline: none;">
            </div>

            <div>
                <label for="category" style="display: block; font-weight: 700; font-size: 0.88rem; color: var(--slate); margin-bottom: 6px;">Tipo de Documento</label>
                <select name="category" id="category" 
                    style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 0.95rem; outline: none; background: white;">
                    <option value="">-- Todos los tipos --</option>
                    <option value="resolucion" {{ $category == 'resolucion' ? 'selected' : '' }}>Resoluciones</option>
                    <option value="circular" {{ $category == 'circular' ? 'selected' : '' }}>Circulares</option>
                    <option value="convocatoria" {{ $category == 'convocatoria' ? 'selected' : '' }}>Convocatorias</option>
                    <option value="comunicado" {{ $category == 'comunicado' ? 'selected' : '' }}>Comunicados</option>
                    <option value="instructivo" {{ $category == 'instructivo' ? 'selected' : '' }}>Instructivos</option>
                    <option value="reglamento" {{ $category == 'reglamento' ? 'selected' : '' }}>Reglamentos</option>
                </select>
            </div>

            <div>
                <label for="year" style="display: block; font-weight: 700; font-size: 0.88rem; color: var(--slate); margin-bottom: 6px;">Gestión / Año</label>
                <select name="year" id="year" 
                    style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 0.95rem; outline: none; background: white;">
                    <option value="">-- Todos --</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="btn-portal btn-portal-primary" style="padding: 10px 20px;">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Documents Grid -->
    <div style="display: grid; gap: 16px;">
        @forelse($documents as $doc)
            <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 22px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="background: #E7EEF9; color: #0B3D91; font-weight: 800; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; text-transform: uppercase;">
                            {{ $doc->category }}
                        </span>
                        <strong style="color: var(--primary-dark); font-family: monospace; font-size: 0.95rem;">{{ $doc->code }}</strong>
                        <small style="color: var(--muted);"><i class="fas fa-calendar mr-1"></i> {{ $doc->published_at->format('d/m/Y') }}</small>
                    </div>
                    <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 6px;">{{ $doc->title }}</h2>
                    <p style="color: var(--muted); font-size: 0.92rem; margin: 0; line-height: 1.5;">{{ $doc->summary }}</p>
                </div>

                <div>
                    <a href="#" onclick="alert('Documento digital registrado institucionalmente en la plataforma YURA.'); return false;" class="btn-portal btn-portal-outline" style="white-space: nowrap;">
                        <i class="fas fa-file-pdf text-danger mr-1"></i> Descargar Documento
                    </a>
                </div>
            </div>
        @empty
            <div style="background: white; border-radius: var(--radius); padding: 40px; text-align: center; border: 1px solid var(--border);">
                <i class="fas fa-folder-open" style="font-size: 2.5rem; color: #A3B1C9; margin-bottom: 12px;"></i>
                <h3 style="color: var(--dark);">No se encontraron documentos con los filtros seleccionados</h3>
                <p style="color: var(--muted); font-size: 0.95rem;">Pruebe cambiando las palabras clave o seleccionando otra gestión.</p>
            </div>
        @endforelse
    </div>

    <div style="margin-top: 30px;">
        {{ $documents->links() }}
    </div>
</div>
@endsection
