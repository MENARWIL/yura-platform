{{--
    Franja de contacto reutilizable.
    Uso: @include('public.partials.cta-band', ['title' => '...', 'text' => '...'])
    Los estilos (.cta-band*) y los botones (.btn-portal-light, .btn-portal-ghost-light) están en layouts/public.blade.php.

    PENDIENTE: los botones de WhatsApp y correo solo aparecen si se configuran
    YURA_WHATSAPP y YURA_EMAIL en el .env (config/yura.php). Sin ellos solo se muestra "Ingresar".
--}}
@php
    $ctaWa = preg_replace('/\D+/', '', (string) config('yura.contact.whatsapp'));
    $ctaWa = (strlen($ctaWa) >= 8 && strlen($ctaWa) <= 15) ? $ctaWa : null;
    $ctaEmail = filter_var(trim((string) config('yura.contact.email')), FILTER_VALIDATE_EMAIL) ?: null;
    $ctaTitle = $title ?? __('layout.cta_default_title');
    $ctaText = $text ?? __('layout.cta_default_text');
    // Un solo botón principal (relleno blanco); el resto van con borde.
    $ctaPrimaryTaken = false;
@endphp
<section class="cta-band" aria-labelledby="cta-band-title">
    <div class="cta-band-inner">
        <div class="cta-band-copy">
            <h2 id="cta-band-title">{{ $ctaTitle }}</h2>
            @if(filled($ctaText))
                <p>{{ $ctaText }}</p>
            @endif
        </div>
        <div class="cta-band-actions">
            @if($ctaWa)
                <a href="https://wa.me/{{ $ctaWa }}?text={{ rawurlencode(__('layout.whatsapp_message')) }}"
                   class="btn-portal btn-portal-lg btn-portal-light" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i> {{ __('layout.cta_whatsapp') }}
                </a>
                @php $ctaPrimaryTaken = true; @endphp
            @endif
            @if($ctaEmail)
                <a href="mailto:{{ $ctaEmail }}"
                   class="btn-portal btn-portal-lg {{ $ctaPrimaryTaken ? 'btn-portal-ghost-light' : 'btn-portal-light' }}">
                    <i class="fas fa-envelope" aria-hidden="true"></i> {{ __('layout.cta_email') }}
                </a>
                @php $ctaPrimaryTaken = true; @endphp
            @endif
            <a href="{{ route('login') }}"
               class="btn-portal btn-portal-lg {{ $ctaPrimaryTaken ? 'btn-portal-ghost-light' : 'btn-portal-light' }}">
                <i class="fas fa-sign-in-alt" aria-hidden="true"></i> {{ __('layout.cta_login') }}
            </a>
        </div>
    </div>
</section>
