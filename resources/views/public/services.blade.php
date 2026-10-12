@extends('layouts.public')

@section('title', 'Servicios Educativos | YURA Platform')

@section('content')
<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <div style="margin-bottom: 30px;">
        <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary); text-transform: uppercase;">Catálogo Institucional</span>
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--dark); margin-top: 4px;">Servicios Educativos y Herramientas Digitales</h1>
        <p style="color: var(--muted); font-size: 1rem;">
            Plataformas y servicios de acompañamiento pedagógico, gestión académica e innovación tecnológica organizados por subsistema.
        </p>
    </div>

    <!-- Subsystems Filter Tabs -->
    <div style="display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap;">
        <a href="{{ route('public.services') }}" class="btn-portal {{ empty($subsystem) ? 'btn-portal-primary' : 'btn-portal-outline' }}">
            Todos los Subsistemas
        </a>
        <a href="{{ route('public.services', ['subsystem' => 'regular']) }}" class="btn-portal {{ $subsystem == 'regular' ? 'btn-portal-primary' : 'btn-portal-outline' }}">
            Educación Regular
        </a>
        <a href="{{ route('public.services', ['subsystem' => 'alternativa_especial']) }}" class="btn-portal {{ $subsystem == 'alternativa_especial' ? 'btn-portal-primary' : 'btn-portal-outline' }}">
            Alternativa y Especial
        </a>
        <a href="{{ route('public.services', ['subsystem' => 'superior']) }}" class="btn-portal {{ $subsystem == 'superior' ? 'btn-portal-primary' : 'btn-portal-outline' }}">
            Educación Superior
        </a>
        <a href="{{ route('public.services', ['subsystem' => 'estudiantes']) }}" class="btn-portal {{ $subsystem == 'estudiantes' ? 'btn-portal-primary' : 'btn-portal-outline' }}">
            Estudiantes
        </a>
    </div>

    @foreach($services as $subsysName => $group)
        <div style="margin-bottom: 40px;">
            <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--dark); margin-bottom: 18px; border-bottom: 2px solid var(--border); padding-bottom: 8px; text-transform: uppercase; letter-spacing: 0.03em;">
                <i class="fas fa-layer-group text-primary mr-2"></i> Subsistema: {{ str_replace('_', ' ', $subsysName) }}
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                @foreach($group as $item)
                    <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: #E7EEF9; color: #0B3D91; display: grid; place-items: center; font-size: 1.25rem; margin-bottom: 14px;">
                                <i class="fas {{ $item->icon ?: 'fa-graduation-cap' }}"></i>
                            </div>
                            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 8px;">{{ $item->name }}</h3>
                            <p style="color: var(--muted); font-size: 0.92rem; line-height: 1.6; margin-bottom: 14px;">{{ $item->description }}</p>
                            <small style="color: var(--slate); font-weight: 600; display: block; margin-bottom: 18px;">
                                <i class="fas fa-user-friends mr-1 text-primary"></i> Destinado a: {{ $item->target_audience }}
                            </small>
                        </div>
                        <div style="border-top: 1px solid var(--border); padding-top: 14px;">
                            <a href="{{ $item->action_url ?: '#' }}" class="btn-portal btn-portal-primary" style="padding: 8px 16px; font-size: 0.88rem; width: 100%; justify-content: center;">
                                Acceder al Servicio <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection
