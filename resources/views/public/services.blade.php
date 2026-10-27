@extends('layouts.public')

@section('title', __('portal.services.meta_title') . ' | YURA')
@section('meta_description', __('portal.services.meta_description'))

@section('content')
<style>
    .pp-wrap { max-width: 1200px; margin: 40px auto 0; padding: 0 20px; }
    .pp-head { margin-bottom: 28px; max-width: 760px; }
    .pp-eyebrow { font-size: 0.85rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.04em; }
    .pp-title { font-size: clamp(1.6rem, 4vw, 2.1rem); font-weight: 800; color: var(--dark); margin-top: 4px; line-height: 1.2; }
    .pp-intro { color: var(--muted); font-size: 1rem; margin-top: 8px; }

    .pp-tabs { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; }
    .pp-tabs .btn-portal, .pp-card .btn-portal, .pp-empty .btn-portal { min-height: 44px; justify-content: center; }

    .pp-group { margin-bottom: 40px; }
    .pp-group-title {
        font-size: 1.2rem; font-weight: 800; color: var(--dark); margin-bottom: 18px;
        border-bottom: 2px solid var(--border); padding-bottom: 8px;
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .pp-group-title i { color: var(--primary); }
    .pp-count { font-size: 0.85rem; font-weight: 600; color: var(--muted); margin-left: auto; }

    .pp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 20px; }
    .pp-card {
        background: var(--white); border: 1px solid var(--border); border-radius: var(--radius);
        padding: 24px; box-shadow: var(--shadow);
        display: flex; flex-direction: column; justify-content: space-between; gap: 16px;
    }
    .pp-icon {
        width: 44px; height: 44px; border-radius: 10px; background: var(--primary-light); color: var(--primary);
        display: grid; place-items: center; font-size: 1.25rem; margin-bottom: 14px;
    }
    .pp-card h3 { font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 8px; line-height: 1.3; }
    .pp-card p { color: var(--muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 14px; }
    .pp-audience { color: var(--slate); font-weight: 600; font-size: 0.88rem; display: flex; gap: 8px; align-items: flex-start; }
    .pp-audience i { color: var(--primary); margin-top: 4px; }
    .pp-card-foot { border-top: 1px solid var(--border); padding-top: 14px; }
    .pp-card-foot .btn-portal { width: 100%; }
    .pp-hint { display: block; color: var(--muted); font-size: 0.82rem; margin-top: 8px; text-align: center; }

    .pp-empty {
        background: var(--white); border: 1px solid var(--border); border-radius: var(--radius);
        padding: 48px 20px; text-align: center; box-shadow: var(--shadow); margin-bottom: 40px;
    }
    .pp-empty-icon {
        width: 64px; height: 64px; border-radius: 50%; background: var(--primary-light); color: var(--primary);
        display: grid; place-items: center; font-size: 1.6rem; margin: 0 auto 16px;
    }
    .pp-empty h2 { color: var(--dark); font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; }
    .pp-empty p { color: var(--muted); max-width: 520px; margin: 0 auto 22px; }
    .pp-empty-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

    @media (max-width: 600px) {
        .pp-wrap { margin-top: 28px; }
        .pp-tabs .btn-portal { flex: 1 1 auto; }
        .pp-card { padding: 20px; }
        .pp-count { margin-left: 0; width: 100%; }
        .pp-empty-actions .btn-portal { width: 100%; }
    }
</style>

<div class="pp-wrap">
    <header class="pp-head">
        <span class="pp-eyebrow">{{ __('portal.services.eyebrow') }}</span>
        <h1 class="pp-title">{{ __('portal.services.title') }}</h1>
        <p class="pp-intro">{{ __('portal.services.intro') }}</p>
    </header>

    {{-- Pestañas: solo se ofrecen subsistemas que tienen servicios publicados --}}
    @if($subsystems->count() > 1)
        <nav class="pp-tabs" aria-label="{{ __('portal.services.filter_label') }}">
            <a href="{{ route('public.services') }}" class="btn-portal {{ empty($subsystem) ? 'btn-portal-primary' : 'btn-portal-outline' }}" @if(empty($subsystem)) aria-current="page" @endif>
                {{ __('portal.services.filter_all') }}
            </a>
            @foreach($subsystems as $key)
                <a href="{{ route('public.services', ['subsystem' => $key]) }}" class="btn-portal {{ $subsystem === $key ? 'btn-portal-primary' : 'btn-portal-outline' }}" @if($subsystem === $key) aria-current="page" @endif>
                    {{ \App\Models\EducationalService::subsystemLabel($key) }}
                </a>
            @endforeach
        </nav>
    @endif

    @forelse($services as $subsysKey => $group)
        <section class="pp-group" aria-labelledby="grupo-{{ $subsysKey }}">
            <h2 class="pp-group-title" id="grupo-{{ $subsysKey }}">
                <i class="fas fa-layer-group" aria-hidden="true"></i> {{ \App\Models\EducationalService::subsystemLabel($subsysKey) }}
                <span class="pp-count">{{ trans_choice('portal.services.count', $group->count(), ['count' => $group->count()]) }}</span>
            </h2>

            <div class="pp-grid">
                @foreach($group as $item)
                    <article class="pp-card">
                        <div>
                            <div class="pp-icon"><i class="fas {{ $item->icon ?: 'fa-graduation-cap' }}" aria-hidden="true"></i></div>
                            <h3>{{ $item->name }}</h3>
                            <p>{{ $item->description }}</p>
                            @if($item->target_audience)
                                <small class="pp-audience">
                                    <i class="fas fa-user-friends" aria-hidden="true"></i>
                                    <span>{{ __('portal.services.audience', ['audience' => $item->target_audience]) }}</span>
                                </small>
                            @endif
                        </div>
                        <div class="pp-card-foot">
                            <a href="{{ $item->link_url }}" class="btn-portal btn-portal-primary">
                                {{ __('portal.services.access') }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                            @if($item->is_internal_link)
                                @guest
                                    <span class="pp-hint">{{ __('portal.services.needs_account') }}</span>
                                @endguest
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        {{-- Estado vacío: nada que mostrar todavía --}}
        <section class="pp-empty" role="status">
            <div class="pp-empty-icon"><i class="fas fa-seedling" aria-hidden="true"></i></div>
            @if($subsystem)
                <h2>{{ __('portal.services.empty_subsystem_title') }}</h2>
                <p>{{ __('portal.services.empty_subsystem_text') }}</p>
                <div class="pp-empty-actions">
                    <a href="{{ route('public.services') }}" class="btn-portal btn-portal-primary">{{ __('portal.common.all_services') }}</a>
                </div>
            @else
                <h2>{{ __('portal.services.empty_title') }}</h2>
                <p>{{ __('portal.services.empty_text') }}</p>
                <div class="pp-empty-actions">
                    <a href="{{ route('feria') }}" class="btn-portal btn-portal-primary">{{ __('portal.common.about_project') }}</a>
                    <a href="{{ route('login') }}" class="btn-portal btn-portal-outline">{{ __('portal.common.login') }}</a>
                </div>
            @endif
        </section>
    @endforelse
</div>

@if($services->isNotEmpty())
    @include('public.partials.cta-band', [
        'title' => __('portal.services.cta_title'),
        'text' => __('portal.services.cta_text'),
    ])
@endif
@endsection
