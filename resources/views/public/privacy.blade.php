@extends('layouts.public')

@section('title', __('privacy.page_title'))
@section('meta_description', __('privacy.meta_description'))

@php
    /*
     * BORRADOR de privacidad. Los textos viven en resources/lang/{es,qu}/privacy.php.
     * Todo lo afirmado consta en el código o en la documentación; lo demás se marca PENDIENTE.
     *
     * PENDIENTE (REVISAR cuando cambie el pie del sitio): el punto "Google Fonts" del grupo "site"
     * es cierto mientras el layout público cargue la tipografía Outfit desde fonts.googleapis.com.
     */
    $orgName = config('yura.org_name');
    $contactEmail = config('yura.contact.email');
    $contactAddress = config('yura.contact.address');
    $contactHours = config('yura.contact.hours');
    $whatsappDigits = preg_replace('/\D+/', '', (string) config('yura.contact.whatsapp'));
    $hasContact = $contactEmail || $whatsappDigits !== '' || $contactAddress || $contactHours;

    $months = trans('privacy.months');
    $updatedText = __('privacy.date_format', [
        'day' => $updatedAt->day,
        'month' => $months[$updatedAt->month - 1] ?? $updatedAt->month,
        'year' => $updatedAt->year,
    ]);

    $tocKeys = array_keys(trans('privacy.toc'));
@endphp

@section('styles')
<style>
    .pv-hero {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        color: #fff;
        padding: 44px 20px 40px;
    }
    .pv-hero-inner { max-width: 820px; margin: 0 auto; }
    .pv-eyebrow {
        display: inline-block;
        margin-bottom: 10px;
        padding-bottom: 6px;
        border-bottom: 3px solid var(--accent);
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #D6E0F5;
    }
    .pv-hero h1 {
        font-size: clamp(1.7rem, 4.5vw, 2.5rem);
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -0.02em;
        color: #fff;
    }
    .pv-lead { margin-top: 12px; max-width: 680px; font-size: 1.1rem; color: #E7EEF9; }
    .pv-updated { margin-top: 14px; font-size: 0.9rem; color: #D6E0F5; }

    .pv-wrap { max-width: 860px; margin: 0 auto; padding: 28px 20px 0; }

    .pv-draft {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        padding: 16px 18px;
        background: var(--accent-light);
        border: 1px solid var(--accent);
        border-left-width: 6px;
        border-radius: var(--radius);
        color: var(--accent-dark);
    }
    .pv-draft i { margin-top: 4px; font-size: 1.25rem; }
    .pv-draft h2 { font-size: 1.1rem; font-weight: 800; line-height: 1.35; color: var(--accent-dark); }
    .pv-draft p { margin-top: 6px; font-size: 0.98rem; color: var(--slate); }

    .pv-toc {
        margin-top: 24px;
        padding: 18px 20px;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    .pv-toc h2 { font-size: 1rem; font-weight: 700; color: var(--primary-dark); margin-bottom: 8px; }
    .pv-toc ol {
        list-style: none;
        display: grid;
        grid-template-columns: 1fr 1fr;
        column-gap: 24px;
    }
    .pv-toc a {
        display: flex;
        align-items: center;
        min-height: 44px;
        padding: 4px 8px;
        border-radius: 8px;
        font-weight: 500;
        color: var(--primary);
        text-decoration: none;
    }
    .pv-toc a:hover { background: var(--primary-light); color: var(--primary-dark); }
    .pv-toc a:focus-visible, .pv-contact a:focus-visible, .pv-actions a:focus-visible {
        outline: 3px solid var(--action);
        outline-offset: 2px;
    }

    .pv-section { margin-top: 36px; scroll-margin-top: 90px; }
    .pv-section h2 {
        padding-left: 14px;
        border-left: 5px solid var(--accent);
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.3;
        letter-spacing: -0.01em;
        color: var(--primary-dark);
    }
    .pv-section h3 { margin: 22px 0 6px; font-size: 1.1rem; font-weight: 700; color: var(--primary); }
    .pv-section p { margin-top: 12px; font-size: 1.02rem; color: var(--slate); }
    .pv-section p.pv-muted { color: var(--muted); }

    .pv-list { list-style: none; margin-top: 10px; }
    .pv-list li {
        position: relative;
        margin-top: 8px;
        padding-left: 24px;
        font-size: 1.02rem;
        color: var(--slate);
    }
    .pv-list li::before {
        content: "";
        position: absolute;
        left: 4px;
        top: 0.7em;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--primary);
    }

    .pv-summary {
        margin-top: 12px;
        padding: 6px 20px 18px;
        background: var(--primary-light);
        border-radius: var(--radius);
    }

    .pv-pending {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        margin-top: 14px;
        padding: 10px 14px;
        background: var(--white);
        border: 1px dashed var(--accent);
        border-radius: 10px;
        font-size: 0.98rem;
        color: var(--slate);
    }
    .pv-tag {
        flex: none;
        margin-top: 2px;
        padding: 2px 10px;
        background: var(--accent-light);
        border: 1px solid var(--accent);
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        color: var(--accent-dark);
    }

    .pv-roles { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; }
    .pv-role {
        padding: 14px 16px;
        background: var(--white);
        border: 1px solid var(--border);
        border-top: 3px solid var(--primary);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    .pv-role dt { font-weight: 700; color: var(--primary-dark); }
    .pv-role dd { margin-top: 4px; font-size: 0.97rem; color: var(--slate); }

    .pv-contact {
        margin-top: 14px;
        padding: 16px 18px;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    .pv-contact h3 { margin-top: 0; }
    .pv-contact ul { list-style: none; }
    .pv-contact li { margin-top: 8px; display: flex; gap: 10px; align-items: baseline; }
    .pv-contact i { width: 18px; text-align: center; color: var(--primary); }
    .pv-contact a { color: var(--action); font-weight: 600; word-break: break-word; }
    .pv-contact a:hover { color: var(--action-hover); }

    .pv-actions { display: flex; flex-wrap: wrap; gap: 12px; margin: 40px 0 0; }
    .pv-actions .btn-portal { min-height: 44px; justify-content: center; }

    @media (max-width: 700px) {
        .pv-hero { padding: 32px 16px 30px; }
        .pv-wrap { padding: 20px 16px 0; }
        .pv-toc ol, .pv-roles { grid-template-columns: 1fr; }
        .pv-draft { padding: 14px; }
        .pv-section h2 { font-size: 1.3rem; }
        .pv-actions .btn-portal { flex: 1 1 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior: auto; }
    }
</style>
@endsection

@section('content')
<header class="pv-hero">
    <div class="pv-hero-inner">
        <span class="pv-eyebrow">{{ __('privacy.eyebrow') }}</span>
        <h1>{{ __('privacy.title') }}</h1>
        <p class="pv-lead">{{ __('privacy.lead') }}</p>
        <p class="pv-updated">{{ __('privacy.updated', ['date' => $updatedText]) }}</p>
    </div>
</header>

<div class="pv-wrap">
    {{-- Aviso visible: este texto es un borrador y no una declaración legal definitiva. --}}
    <aside class="pv-draft" role="note" aria-labelledby="pv-draft-title">
        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
        <div>
            <h2 id="pv-draft-title">{{ __('privacy.draft_title') }}</h2>
            <p>{{ __('privacy.draft_text') }}</p>
        </div>
    </aside>

    <nav class="pv-toc" aria-labelledby="pv-toc-title">
        <h2 id="pv-toc-title">{{ __('privacy.toc_title') }}</h2>
        <ol>
            @foreach ($tocKeys as $key)
                <li><a href="#{{ $key }}">{{ __('privacy.toc.' . $key) }}</a></li>
            @endforeach
        </ol>
    </nav>

    {{-- 1. En pocas palabras --}}
    <section class="pv-section" id="resumen" aria-labelledby="resumen-title">
        <h2 id="resumen-title">{{ __('privacy.summary.title') }}</h2>
        <div class="pv-summary">
            <ul class="pv-list">
                @foreach (trans('privacy.summary.items') as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 2. Responsables --}}
    <section class="pv-section" id="responsables" aria-labelledby="responsables-title">
        <h2 id="responsables-title">{{ __('privacy.owners.title') }}</h2>
        <p>{{ __('privacy.owners.p1') }}</p>
        <p>{{ __('privacy.owners.p2') }}</p>
        @if ($orgName)
            <p><strong>{{ __('privacy.owners.org_label') }}:</strong> {{ $orgName }}</p>
        @else
            {{-- PENDIENTE: definir YURA_ORG_NAME en el .env con el nombre oficial de la unidad educativa y del equipo. --}}
            <p class="pv-pending"><span class="pv-tag">{{ __('privacy.pending_tag') }}</span><span>{{ __('privacy.owners.org_pending') }}</span></p>
        @endif
    </section>

    {{-- 3. Datos que guarda --}}
    <section class="pv-section" id="datos" aria-labelledby="datos-title">
        <h2 id="datos-title">{{ __('privacy.data.title') }}</h2>
        <p>{{ __('privacy.data.intro') }}</p>
        @foreach (trans('privacy.data.groups') as $group)
            <h3>{{ $group['title'] }}</h3>
            <ul class="pv-list">
                @foreach ($group['items'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            @if (! empty($group['pending']))
                <p class="pv-pending"><span class="pv-tag">{{ __('privacy.pending_tag') }}</span><span>{{ $group['pending'] }}</span></p>
            @endif
        @endforeach
    </section>

    {{-- 4. Quién puede verlos --}}
    <section class="pv-section" id="quien" aria-labelledby="quien-title">
        <h2 id="quien-title">{{ __('privacy.access.title') }}</h2>
        <p>{{ __('privacy.access.intro') }}</p>
        <dl class="pv-roles">
            @foreach (trans('privacy.access.roles') as $role)
                <div class="pv-role">
                    <dt>{{ $role['name'] }}</dt>
                    <dd>{{ $role['text'] }}</dd>
                </div>
            @endforeach
        </dl>
        <p class="pv-pending"><span class="pv-tag">{{ __('privacy.pending_tag') }}</span><span>{{ __('privacy.access.pending') }}</span></p>
    </section>

    {{-- 5. Para qué se usan --}}
    <section class="pv-section" id="uso" aria-labelledby="uso-title">
        <h2 id="uso-title">{{ __('privacy.purpose.title') }}</h2>
        <ul class="pv-list">
            @foreach (trans('privacy.purpose.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>

    {{-- 6. Con quién se comparten --}}
    <section class="pv-section" id="compartir" aria-labelledby="compartir-title">
        <h2 id="compartir-title">{{ __('privacy.sharing.title') }}</h2>
        <ul class="pv-list">
            @foreach (trans('privacy.sharing.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
        @foreach (trans('privacy.sharing.pending') as $item)
            <p class="pv-pending"><span class="pv-tag">{{ __('privacy.pending_tag') }}</span><span>{{ $item }}</span></p>
        @endforeach
    </section>

    {{-- 7. Cuánto tiempo --}}
    <section class="pv-section" id="conservacion" aria-labelledby="conservacion-title">
        <h2 id="conservacion-title">{{ __('privacy.retention.title') }}</h2>
        <p class="pv-pending"><span class="pv-tag">{{ __('privacy.pending_tag') }}</span><span>{{ __('privacy.retention.pending') }}</span></p>
        <p>{{ __('privacy.retention.today') }}</p>
        <ul class="pv-list">
            @foreach (trans('privacy.retention.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>

    {{-- 8. Cómo pedir ver, corregir o eliminar --}}
    <section class="pv-section" id="derechos" aria-labelledby="derechos-title">
        <h2 id="derechos-title">{{ __('privacy.rights.title') }}</h2>
        <p>{{ __('privacy.rights.intro') }}</p>
        <ul class="pv-list">
            @foreach (trans('privacy.rights.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
        <p>{{ __('privacy.rights.how') }}</p>

        <div class="pv-contact">
            <h3>{{ __('privacy.rights.contact_title') }}</h3>
            @if ($hasContact)
                <ul>
                    @if ($contactEmail)
                        <li><i class="fas fa-envelope" aria-hidden="true"></i> <span>{{ __('privacy.rights.email') }}: <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></span></li>
                    @endif
                    @if ($whatsappDigits !== '')
                        <li><i class="fab fa-whatsapp" aria-hidden="true"></i> <span>{{ __('privacy.rights.whatsapp') }}: <a href="https://wa.me/{{ $whatsappDigits }}" target="_blank" rel="noopener noreferrer">+{{ $whatsappDigits }}</a></span></li>
                    @endif
                    @if ($contactAddress)
                        <li><i class="fas fa-location-dot" aria-hidden="true"></i> <span>{{ __('privacy.rights.address') }}: {{ $contactAddress }}</span></li>
                    @endif
                    @if ($contactHours)
                        <li><i class="fas fa-clock" aria-hidden="true"></i> <span>{{ __('privacy.rights.hours') }}: {{ $contactHours }}</span></li>
                    @endif
                </ul>
            @else
                {{-- PENDIENTE: definir YURA_EMAIL / YURA_WHATSAPP / YURA_ADDRESS / YURA_HOURS en el .env para mostrar los datos de contacto reales. --}}
                <p style="margin-top: 0;">{{ __('privacy.rights.no_contact') }}</p>
            @endif
        </div>

        <p class="pv-pending"><span class="pv-tag">{{ __('privacy.pending_tag') }}</span><span>{{ __('privacy.rights.pending') }}</span></p>
    </section>

    {{-- 9. Seguridad --}}
    <section class="pv-section" id="seguridad" aria-labelledby="seguridad-title">
        <h2 id="seguridad-title">{{ __('privacy.security.title') }}</h2>
        <ul class="pv-list">
            @foreach (trans('privacy.security.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
        <p>{{ __('privacy.security.minors') }}</p>
        <p class="pv-pending"><span class="pv-tag">{{ __('privacy.pending_tag') }}</span><span>{{ __('privacy.security.minors_pending') }}</span></p>
        <p class="pv-pending"><span class="pv-tag">{{ __('privacy.pending_tag') }}</span><span>{{ __('privacy.security.pending') }}</span></p>
    </section>

    {{-- 10. Cambios --}}
    <section class="pv-section" id="cambios" aria-labelledby="cambios-title">
        <h2 id="cambios-title">{{ __('privacy.changes.title') }}</h2>
        <p>{{ __('privacy.changes.text') }}</p>
    </section>

    {{-- 11. Lo que falta confirmar --}}
    <section class="pv-section" id="pendientes" aria-labelledby="pendientes-title">
        <h2 id="pendientes-title">{{ __('privacy.todo.title') }}</h2>
        <p>{{ __('privacy.todo.intro') }}</p>
        <ul class="pv-list">
            @foreach (trans('privacy.todo.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>

    <div class="pv-actions">
        <a href="{{ route('login') }}" class="btn-portal btn-portal-primary"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> {{ __('privacy.go_login') }}</a>
        <a href="{{ route('public.home') }}" class="btn-portal btn-portal-outline"><i class="fas fa-arrow-left" aria-hidden="true"></i> {{ __('privacy.back_home') }}</a>
    </div>
</div>
@endsection
