@extends('layouts.public')

@section('title', __('portal.search.meta_title') . ': ' . $term . ' | YURA')

@section('content')
@php
    // Resalta el término buscado. Se parte el texto SIN escapar y luego se escapa cada
    // trozo, así nunca se inserta HTML ni se rompen entidades como &amp;.
    $mark = function (?string $text) use ($term): \Illuminate\Support\HtmlString {
        $text = (string) $text;
        $parts = preg_split('/(' . preg_quote($term, '/') . ')/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return new \Illuminate\Support\HtmlString(e($text));
        }
        $html = '';
        foreach ($parts as $i => $part) {
            $html .= $i % 2 === 1 ? '<mark>' . e($part) . '</mark>' : e($part);
        }
        return new \Illuminate\Support\HtmlString($html);
    };
    $total = $documents->count() + $services->count();
@endphp
<style>
    .pp-wrap { max-width: 1100px; margin: 40px auto 0; padding: 0 20px; }
    .pp-head { margin-bottom: 22px; }
    .pp-eyebrow { font-size: 0.85rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.04em; }
    .pp-title { font-size: clamp(1.5rem, 4vw, 2.1rem); font-weight: 800; color: var(--dark); margin-top: 4px; line-height: 1.25; overflow-wrap: anywhere; }
    .pp-title .pp-term { color: var(--primary); }
    .pp-total { color: var(--muted); font-weight: 600; margin-top: 6px; }

    .pp-searchform { display: flex; gap: 10px; max-width: 640px; margin-bottom: 32px; }
    .pp-searchform input {
        flex: 1; min-width: 0; min-height: 44px; padding: 10px 16px; border: 1px solid var(--border); border-radius: 10px;
        font-family: inherit; font-size: 1rem; color: var(--slate); background: var(--white);
    }
    .pp-searchform input:focus-visible { outline: 3px solid var(--action-border); border-color: var(--action); }
    .pp-searchform .btn-portal, .pp-empty .btn-portal, .pp-result .btn-portal { min-height: 44px; justify-content: center; }

    .pp-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

    .pp-section { margin-bottom: 34px; }
    .pp-section h2 {
        font-size: 1.2rem; font-weight: 800; color: var(--dark); margin-bottom: 14px;
        border-bottom: 2px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 10px;
    }
    .pp-section h2 i { color: var(--primary); }
    .pp-list { display: grid; gap: 12px; }
    .pp-result {
        background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 16px 20px;
        display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
    }
    .pp-result-body { flex: 1; min-width: min(100%, 240px); }
    .pp-result-title { color: var(--dark); font-size: 1.05rem; font-weight: 700; display: block; line-height: 1.35; }
    .pp-result-meta { color: var(--muted); font-size: 0.88rem; display: block; margin-top: 2px; }
    .pp-result-desc { color: var(--muted); font-size: 0.9rem; margin-top: 4px; line-height: 1.5; }
    mark { background: var(--primary-light); color: var(--primary-dark); font-weight: 700; border-radius: 3px; padding: 0 2px; }

    .pp-empty {
        background: var(--white); border: 1px solid var(--border); border-radius: var(--radius);
        padding: 48px 20px; text-align: center; box-shadow: var(--shadow);
    }
    .pp-empty-icon {
        width: 64px; height: 64px; border-radius: 50%; background: var(--primary-light); color: var(--primary);
        display: grid; place-items: center; font-size: 1.6rem; margin: 0 auto 16px;
    }
    .pp-empty h2 { color: var(--dark); font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; overflow-wrap: anywhere; }
    .pp-empty p { color: var(--muted); max-width: 520px; margin: 0 auto 18px; }
    .pp-try { display: flex; gap: 8px; justify-content: center; align-items: center; flex-wrap: wrap; margin-bottom: 24px; color: var(--muted); font-weight: 600; }
    .pp-chip {
        display: inline-flex; align-items: center; min-height: 36px; padding: 4px 14px; border-radius: 999px;
        background: var(--action-soft); border: 1px solid var(--action-border); color: var(--action);
        font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: all 0.2s;
    }
    .pp-chip:hover { background: var(--white); border-color: var(--action); color: var(--action-hover); }
    .pp-chip:focus-visible { outline: 3px solid var(--action-border); }
    .pp-empty-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

    @media (max-width: 600px) {
        .pp-wrap { margin-top: 28px; }
        .pp-searchform { flex-wrap: wrap; }
        .pp-searchform input, .pp-searchform .btn-portal { flex: 1 1 100%; }
        .pp-result .btn-portal, .pp-empty-actions .btn-portal { width: 100%; }
    }
</style>

<div class="pp-wrap">
    <header class="pp-head">
        <span class="pp-eyebrow">{{ __('portal.search.eyebrow') }}</span>
        <h1 class="pp-title">{{ __('portal.search.results_for') }} <span class="pp-term">&ldquo;{{ $term }}&rdquo;</span></h1>
        <p class="pp-total" role="status">{{ trans_choice('portal.search.count', $total, ['count' => $total]) }}</p>
    </header>

    <form action="{{ route('public.search') }}" method="GET" class="pp-searchform" role="search">
        <label for="search-q" class="pp-sr">{{ __('portal.search.label') }}</label>
        <input type="search" id="search-q" name="q" value="{{ $term }}" maxlength="100" placeholder="{{ __('portal.search.placeholder') }}" required>
        <button type="submit" class="btn-portal btn-portal-primary"><i class="fas fa-magnifying-glass" aria-hidden="true"></i> {{ __('portal.search.submit') }}</button>
    </form>

    @if($documents->isNotEmpty())
        <section class="pp-section" aria-labelledby="res-docs">
            <h2 id="res-docs"><i class="fas fa-file-lines" aria-hidden="true"></i> {{ __('portal.search.documents') }} ({{ $documents->count() }})</h2>
            <div class="pp-list">
                @foreach($documents as $doc)
                    <article class="pp-result">
                        <div class="pp-result-body">
                            <strong class="pp-result-title">{{ $mark($doc->title) }}</strong>
                            <small class="pp-result-meta">
                                {{ $mark($doc->code) }} &middot; {{ __('portal.search.type_year', ['type' => $doc->category_label, 'year' => $doc->year]) }}
                            </small>
                            @if($doc->summary)
                                <p class="pp-result-desc">{{ $mark($doc->summary) }}</p>
                            @endif
                        </div>
                        <a href="{{ route('public.documents', ['q' => $doc->code]) }}" class="btn-portal btn-portal-outline">
                            {{ __('portal.search.view_document') }}
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($services->isNotEmpty())
        <section class="pp-section" aria-labelledby="res-services">
            <h2 id="res-services"><i class="fas fa-th-large" aria-hidden="true"></i> {{ __('portal.search.services') }} ({{ $services->count() }})</h2>
            <div class="pp-list">
                @foreach($services as $srv)
                    <article class="pp-result">
                        <div class="pp-result-body">
                            <strong class="pp-result-title">{{ $mark($srv->name) }}</strong>
                            <small class="pp-result-meta">
                                {{ $srv->subsystem_label }}@if($srv->target_audience) &middot; {{ $srv->target_audience }}@endif
                            </small>
                            @if($srv->description)
                                <p class="pp-result-desc">{{ $mark($srv->description) }}</p>
                            @endif
                        </div>
                        <a href="{{ $srv->link_url }}" class="btn-portal btn-portal-outline">
                            {{ __('portal.search.access') }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($total === 0)
        {{-- Estado vacío: sugerencias y siguientes pasos en vez de una pantalla en blanco --}}
        <section class="pp-empty" role="status">
            <div class="pp-empty-icon"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></div>
            <h2>{{ __('portal.search.empty_title', ['term' => $term]) }}</h2>
            <p>{{ __('portal.search.empty_text') }}</p>
            <div class="pp-try">
                <span>{{ __('portal.search.try_label') }}</span>
                @foreach(__('portal.search.suggestions') as $suggestion)
                    <a href="{{ route('public.search', ['q' => $suggestion]) }}" class="pp-chip">{{ $suggestion }}</a>
                @endforeach
            </div>
            <div class="pp-empty-actions">
                <a href="{{ route('public.services') }}" class="btn-portal btn-portal-primary">{{ __('portal.common.all_services') }}</a>
                <a href="{{ route('public.documents') }}" class="btn-portal btn-portal-outline">{{ __('portal.search.see_documents') }}</a>
            </div>
        </section>
    @endif
</div>
@endsection
