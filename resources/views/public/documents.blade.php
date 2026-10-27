@extends('layouts.public')

@section('title', __('portal.documents.meta_title') . ' | YURA')
@section('meta_description', __('portal.documents.meta_description'))

@section('content')
<style>
    .pp-wrap { max-width: 1200px; margin: 40px auto 0; padding: 0 20px; }
    .pp-head { margin-bottom: 22px; max-width: 760px; }
    .pp-eyebrow { font-size: 0.85rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.04em; }
    .pp-title { font-size: clamp(1.6rem, 4vw, 2.1rem); font-weight: 800; color: var(--dark); margin-top: 4px; line-height: 1.2; }
    .pp-intro { color: var(--muted); font-size: 1rem; margin-top: 8px; }

    .pp-notice {
        display: flex; gap: 12px; align-items: flex-start; margin-bottom: 26px;
        background: var(--primary-light); border-left: 4px solid var(--primary); border-radius: 8px;
        padding: 14px 18px; color: var(--primary-dark); font-size: 0.92rem; line-height: 1.55;
    }
    .pp-notice i { margin-top: 3px; }

    .pp-filters {
        background: var(--white); border: 1px solid var(--border); border-radius: var(--radius);
        padding: 22px; box-shadow: var(--shadow); margin-bottom: 20px;
    }
    .pp-filters form { display: grid; grid-template-columns: 2fr 1.2fr 1fr auto; gap: 14px; align-items: end; }
    .pp-field label { display: block; font-weight: 700; font-size: 0.88rem; color: var(--slate); margin-bottom: 6px; }
    .pp-field input, .pp-field select {
        width: 100%; min-height: 44px; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px;
        font-family: inherit; font-size: 0.95rem; color: var(--slate); background: var(--white);
    }
    .pp-field input:focus-visible, .pp-field select:focus-visible { outline: 3px solid var(--action-border); border-color: var(--action); }
    .pp-filter-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .pp-filters .btn-portal, .pp-empty .btn-portal, .pp-pager .btn-portal, .pp-doc-action .btn-portal { min-height: 44px; justify-content: center; }

    .pp-resultbar { color: var(--muted); font-size: 0.92rem; font-weight: 600; margin-bottom: 14px; }

    .pp-list { display: grid; gap: 16px; }
    .pp-doc {
        background: var(--white); border: 1px solid var(--border); border-radius: var(--radius);
        padding: 22px; box-shadow: var(--shadow);
        display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;
    }
    .pp-doc-body { flex: 1; min-width: min(100%, 280px); }
    .pp-doc-meta { display: flex; align-items: center; gap: 8px 12px; margin-bottom: 8px; flex-wrap: wrap; }
    .pp-badge {
        background: var(--primary-light); color: var(--primary); font-weight: 800; font-size: 0.78rem;
        padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.02em;
    }
    .pp-code { color: var(--primary-dark); font-family: monospace; font-size: 0.95rem; }
    .pp-date { color: var(--muted); font-size: 0.85rem; }
    .pp-doc h2 { font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 6px; line-height: 1.35; }
    .pp-doc p { color: var(--muted); font-size: 0.93rem; margin: 0; line-height: 1.55; }
    .pp-nofile { color: var(--muted); font-size: 0.85rem; max-width: 220px; display: flex; gap: 8px; align-items: flex-start; }
    .pp-nofile i { margin-top: 3px; }

    .pp-empty {
        background: var(--white); border: 1px solid var(--border); border-radius: var(--radius);
        padding: 48px 20px; text-align: center; box-shadow: var(--shadow);
    }
    .pp-empty-icon {
        width: 64px; height: 64px; border-radius: 50%; background: var(--primary-light); color: var(--primary);
        display: grid; place-items: center; font-size: 1.6rem; margin: 0 auto 16px;
    }
    .pp-empty h2 { color: var(--dark); font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; }
    .pp-empty p { color: var(--muted); max-width: 520px; margin: 0 auto 22px; }
    .pp-empty-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

    .pp-pager { display: flex; justify-content: center; align-items: center; gap: 14px; margin-top: 28px; flex-wrap: wrap; }
    .pp-pager-info { color: var(--muted); font-size: 0.9rem; font-weight: 600; }
    .pp-pager .is-disabled { opacity: 0.45; pointer-events: none; }

    @media (max-width: 900px) {
        .pp-filters form { grid-template-columns: 1fr 1fr; }
        .pp-filters .pp-field-wide, .pp-filters .pp-filter-actions { grid-column: 1 / -1; }
    }
    @media (max-width: 600px) {
        .pp-wrap { margin-top: 28px; }
        .pp-filters { padding: 18px; }
        .pp-filters form { grid-template-columns: 1fr; }
        .pp-filter-actions .btn-portal, .pp-empty-actions .btn-portal, .pp-doc-action .btn-portal { flex: 1 1 auto; width: 100%; }
        .pp-doc { padding: 18px; }
        .pp-doc-action { width: 100%; }
        .pp-nofile { max-width: none; }
    }
</style>

<div class="pp-wrap">
    <header class="pp-head">
        <span class="pp-eyebrow">{{ __('portal.documents.eyebrow') }}</span>
        <h1 class="pp-title">{{ __('portal.documents.title') }}</h1>
        <p class="pp-intro">{{ __('portal.documents.intro') }}</p>
    </header>

    {{-- Aviso honesto: estos documentos son del proyecto, no normativa oficial --}}
    <p class="pp-notice" role="note">
        <i class="fas fa-circle-info" aria-hidden="true"></i>
        <span>{{ __('portal.documents.notice') }}</span>
    </p>

    <section class="pp-filters" aria-label="{{ __('portal.documents.filter') }}">
        <form action="{{ route('public.documents') }}" method="GET">
            <div class="pp-field pp-field-wide">
                <label for="q">{{ __('portal.documents.keyword') }}</label>
                <input type="text" name="q" id="q" value="{{ $search }}" maxlength="100" placeholder="{{ __('portal.documents.keyword_placeholder') }}">
            </div>

            <div class="pp-field">
                <label for="category">{{ __('portal.documents.type') }}</label>
                <select name="category" id="category">
                    <option value="">{{ __('portal.documents.type_all') }}</option>
                    @foreach(\App\Models\OfficialDocument::CATEGORIES as $key)
                        <option value="{{ $key }}" @selected($category === $key)>{{ \App\Models\OfficialDocument::categoryLabel($key) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pp-field">
                <label for="year">{{ __('portal.documents.year') }}</label>
                <select name="year" id="year">
                    <option value="">{{ __('portal.documents.year_all') }}</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected((int) $year === (int) $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pp-filter-actions">
                <button type="submit" class="btn-portal btn-portal-primary">
                    <i class="fas fa-filter" aria-hidden="true"></i> {{ __('portal.documents.filter') }}
                </button>
                @if($hasFilters)
                    <a href="{{ route('public.documents') }}" class="btn-portal btn-portal-outline">{{ __('portal.documents.clear') }}</a>
                @endif
            </div>
        </form>
    </section>

    @if($documents->total() > 0)
        <p class="pp-resultbar" role="status">{{ trans_choice('portal.documents.count', $documents->total(), ['count' => $documents->total()]) }}</p>
    @endif

    <div class="pp-list">
        @forelse($documents as $doc)
            <article class="pp-doc">
                <div class="pp-doc-body">
                    <div class="pp-doc-meta">
                        <span class="pp-badge">{{ $doc->category_label }}</span>
                        <strong class="pp-code">{{ $doc->code }}</strong>
                        <small class="pp-date"><i class="fas fa-calendar" aria-hidden="true"></i> {{ __('portal.documents.published', ['date' => $doc->published_at->format('d/m/Y')]) }}</small>
                    </div>
                    <h2>{{ $doc->title }}</h2>
                    @if($doc->summary)
                        <p>{{ $doc->summary }}</p>
                    @endif
                </div>

                <div class="pp-doc-action">
                    @if($doc->download_url)
                        <a href="{{ $doc->download_url }}" class="btn-portal btn-portal-outline" target="_blank" rel="noopener">
                            <i class="fas fa-file-pdf" aria-hidden="true"></i> {{ __('portal.documents.download') }}
                        </a>
                    @else
                        {{-- Sin archivo real: no se promete ninguna descarga --}}
                        <span class="pp-nofile"><i class="fas fa-circle-info" aria-hidden="true"></i> {{ __('portal.documents.no_file') }}</span>
                    @endif
                </div>
            </article>
        @empty
            <section class="pp-empty" role="status">
                <div class="pp-empty-icon"><i class="fas fa-folder-open" aria-hidden="true"></i></div>
                @if($hasFilters)
                    <h2>{{ __('portal.documents.empty_filtered_title') }}</h2>
                    <p>{{ __('portal.documents.empty_filtered_text') }}</p>
                    <div class="pp-empty-actions">
                        <a href="{{ route('public.documents') }}" class="btn-portal btn-portal-primary">{{ __('portal.documents.clear') }}</a>
                    </div>
                @else
                    <h2>{{ __('portal.documents.empty_title') }}</h2>
                    <p>{{ __('portal.documents.empty_text') }}</p>
                    <div class="pp-empty-actions">
                        <a href="{{ route('feria') }}" class="btn-portal btn-portal-primary">{{ __('portal.common.about_project') }}</a>
                        <a href="{{ route('public.home') }}" class="btn-portal btn-portal-outline">{{ __('portal.common.go_home') }}</a>
                    </div>
                @endif
            </section>
        @endforelse
    </div>

    @if($documents->hasPages())
        <nav class="pp-pager" aria-label="{{ __('portal.documents.pagination_label') }}">
            <a href="{{ $documents->previousPageUrl() ?: '#' }}" class="btn-portal btn-portal-outline {{ $documents->onFirstPage() ? 'is-disabled' : '' }}" @if($documents->onFirstPage()) aria-disabled="true" tabindex="-1" @endif rel="prev">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> {{ __('portal.documents.previous') }}
            </a>
            <span class="pp-pager-info">{{ __('portal.documents.page_of', ['current' => $documents->currentPage(), 'last' => $documents->lastPage()]) }}</span>
            <a href="{{ $documents->nextPageUrl() ?: '#' }}" class="btn-portal btn-portal-outline {{ $documents->hasMorePages() ? '' : 'is-disabled' }}" @unless($documents->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless rel="next">
                {{ __('portal.documents.next') }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </nav>
    @endif
</div>

@if($documents->isNotEmpty())
    @include('public.partials.cta-band', [
        'title' => __('portal.documents.cta_title'),
        'text' => __('portal.documents.cta_text'),
    ])
@endif
@endsection
