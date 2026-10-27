@php
    /*
     * Pantalla de ingreso AUTÓNOMA: no depende de AdminLTE, jQuery ni de ningún layout interno.
     * Solo usa CSS inline con los tokens de marca del portal, Outfit (Google Fonts, como el resto del
     * portal) y Font Awesome local. Textos en resources/lang/{es,qu}/auth_public.php.
     */
    $locale = app()->getLocale();

    // Mensajes del AuthController (en castellano) -> clave traducible. Lo desconocido (p. ej. validación
    // "required"/"email" de Laravel, que llega en inglés) se reemplaza por un mensaje genérico claro.
    $knownErrors = [
        'Las credenciales proporcionadas no coinciden con nuestros registros.' => 'error_credentials',
        'Tu cuenta está suspendida. Contacta al administrador.' => 'error_suspended',
    ];
    $rawError = $errors->first('email') ?: $errors->first('password');
    $errorText = $rawError ? __('auth_public.' . ($knownErrors[$rawError] ?? 'error_generic')) : null;

    // WhatsApp: solo dígitos; si no hay número configurado, no se muestra el botón.
    $whatsappDigits = preg_replace('/\D+/', '', (string) config('yura.contact.whatsapp'));

    // Recuperación de contraseña: solo se enlaza si la ruta existe.
    $canRecover = \Illuminate\Support\Facades\Route::has('password.request');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth_public.page_title') }}</title>
    <meta name="description" content="{{ __('auth_public.meta_description') }}">
    <meta name="theme-color" content="#0B3D91">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <style>
        :root {
            --primary: #0B3D91;
            --primary-dark: #082C6C;
            --primary-light: #E7EEF9;
            --accent: #C8102E;
            --accent-dark: #9E0B24;
            --accent-light: #FCE8EB;
            --action: #1E56D6;
            --action-top: #2F6FED;
            --action-hover: #1847B5;
            --action-soft: #EEF3FD;
            --action-border: #C9D6F2;
            --action-shadow: 0 1px 2px rgba(16, 24, 40, 0.08), 0 6px 16px -4px rgba(30, 86, 214, 0.45);
            --slate: #1F2937;
            --muted: #5B6474;
            --light: #F4F6FA;
            --white: #ffffff;
            --border: #DDE3EC;
            --radius: 12px;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            font-family: 'Outfit', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            color: var(--slate);
            background-color: var(--light);
            background-image: radial-gradient(900px 420px at 50% -120px, var(--primary-light), rgba(231, 238, 249, 0));
            background-repeat: no-repeat;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        a { color: var(--action); }
        a:hover { color: var(--action-hover); }

        :focus-visible {
            outline: 3px solid var(--action);
            outline-offset: 2px;
        }

        .skip-link {
            position: absolute;
            top: -48px;
            left: 0;
            z-index: 100;
            padding: 10px 16px;
            background: var(--primary-dark);
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            border-radius: 0 0 8px 0;
        }
        .skip-link:focus { top: 0; color: #fff; }

        /* Franja institucional rojo / blanco / azul */
        .tricolor-stripe {
            height: 6px;
            flex: none;
            background: linear-gradient(90deg, var(--accent) 0 33.33%, var(--white) 33.33% 66.66%, var(--primary) 66.66% 100%);
        }

        /* Barra superior: volver al inicio + idioma */
        .topbar {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 14px 20px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .topbar a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            color: var(--primary);
        }
        .topbar a:hover { background: var(--primary-light); color: var(--primary-dark); }
        .lang { display: flex; align-items: center; gap: 4px; }
        .lang a[aria-current="true"] {
            background: var(--primary-light);
            color: var(--primary-dark);
            box-shadow: inset 0 -3px 0 var(--accent);
        }

        /* Contenido */
        .login-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 16px 28px;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 32px 32px 28px;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 22px;
        }
        .brand-mark {
            width: 52px;
            height: 52px;
            flex: none;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: 3px solid var(--accent);
            border-radius: 12px;
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
            box-shadow: 0 4px 10px rgba(11, 61, 145, 0.3);
        }
        .brand-name {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
            color: var(--primary-dark);
        }
        .brand-tagline {
            display: block;
            font-size: 0.85rem;
            font-weight: 400;
            letter-spacing: 0;
            color: var(--muted);
        }

        h1 {
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.02em;
            text-align: center;
            color: var(--primary-dark);
        }
        .subtitle {
            margin: 8px 0 22px;
            text-align: center;
            color: var(--muted);
            font-size: 1rem;
        }

        /* Mensaje de error */
        .alert-error {
            margin-bottom: 18px;
            padding: 12px 14px;
            background: var(--accent-light);
            border: 1px solid var(--accent);
            border-left-width: 5px;
            border-radius: 10px;
            color: var(--accent-dark);
            font-size: 0.95rem;
        }
        .alert-error strong { display: block; font-weight: 700; margin-bottom: 2px; }
        .alert-error .alert-hint { display: block; margin-top: 6px; color: var(--slate); font-size: 0.88rem; }

        /* Campos */
        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--slate);
        }
        .field input {
            width: 100%;
            min-height: 48px;
            padding: 10px 14px;
            font: inherit;
            font-size: 1rem;
            color: var(--slate);
            background: var(--white);
            border: 1px solid var(--muted);
            border-radius: 10px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .field input:hover { border-color: var(--primary); }
        .field input:focus {
            outline: none;
            border-color: var(--action);
            box-shadow: 0 0 0 4px var(--action-border);
        }
        .field input[aria-invalid="true"] {
            border-color: var(--accent);
            border-width: 2px;
        }

        .pw-field { display: flex; align-items: stretch; }
        .pw-field input { flex: 1; min-width: 0; }
        .pw-field.has-toggle input { border-top-right-radius: 0; border-bottom-right-radius: 0; border-right-width: 0; }
        .pw-toggle {
            flex: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-width: 44px;
            min-height: 48px;
            padding: 0 14px;
            font: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--action-hover);
            background: var(--action-soft);
            border: 1px solid var(--muted);
            border-left-color: var(--action-border);
            border-radius: 0 10px 10px 0;
            cursor: pointer;
            transition: background 0.2s;
        }
        .pw-toggle:hover { background: var(--primary-light); color: var(--primary-dark); }
        .pw-toggle[hidden] { display: none; }

        /* Botones: mismos nombres y tokens que el portal */
        .btn-portal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 10px;
            border: 1px solid transparent;
            font: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.01em;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-portal-primary {
            background: linear-gradient(180deg, var(--action-top) 0%, var(--action) 100%);
            border-color: var(--action);
            color: #FFFFFF;
            box-shadow: var(--action-shadow);
        }
        .btn-portal-primary:hover {
            background: var(--action-hover);
            border-color: var(--action-hover);
            color: #FFFFFF;
            transform: translateY(-1px);
        }
        .btn-portal-primary:disabled { opacity: 0.75; cursor: wait; transform: none; }
        .btn-portal-outline {
            background: var(--white);
            border-color: var(--action-border);
            color: var(--action);
        }
        .btn-portal-outline:hover {
            background: var(--action-soft);
            border-color: var(--action);
            color: var(--action-hover);
        }
        .btn-portal-lg { min-height: 52px; padding: 12px 24px; font-size: 1.05rem; border-radius: 12px; }
        .btn-portal-block { display: flex; width: 100%; min-height: 48px; }

        /* Ayuda */
        .help {
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        .help h2 {
            margin-bottom: 8px;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary-dark);
        }
        .help p { margin-bottom: 12px; font-size: 0.95rem; color: var(--slate); }
        .help .btn-portal-block { margin-bottom: 12px; }
        .help .muted { color: var(--muted); }
        .help p:last-child { margin-bottom: 0; }

        /* Pie de la tarjeta */
        .card-foot {
            width: 100%;
            max-width: 440px;
            margin-top: 18px;
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted);
        }
        .card-foot p { margin-bottom: 6px; }
        .card-foot a { font-weight: 600; }

        @media (max-width: 480px) {
            .topbar { padding: 10px 12px 0; }
            .login-main { padding: 12px 12px 24px; }
            .login-card { padding: 24px 18px 22px; border-radius: 14px; }
            h1 { font-size: 1.45rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; animation: none !important; }
            .btn-portal-primary:hover { transform: none; }
        }
    </style>
</head>
<body>
    <a href="#login-form" class="skip-link">{{ __('auth_public.skip_to_form') }}</a>
    <div class="tricolor-stripe" aria-hidden="true"></div>

    <header class="topbar">
        <a href="{{ route('public.home') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> {{ __('auth_public.back_home') }}</a>
        <nav class="lang" aria-label="{{ __('auth_public.language') }}">
            <a href="{{ route('lang.set', ['locale' => 'es']) }}" lang="es" @if ($locale === 'es') aria-current="true" @endif>{{ __('auth_public.language_es') }}</a>
            <a href="{{ route('lang.set', ['locale' => 'qu']) }}" lang="qu" @if ($locale === 'qu') aria-current="true" @endif>{{ __('auth_public.language_qu') }}</a>
        </nav>
    </header>

    <main class="login-main">
        <div class="login-card">
            <div class="brand">
                <span class="brand-mark" aria-hidden="true">Y</span>
                <p class="brand-name">{{ __('auth_public.brand_name') }}<span class="brand-tagline">{{ __('auth_public.brand_tagline') }}</span></p>
            </div>

            <h1>{{ __('auth_public.title') }}</h1>
            <p class="subtitle">{{ __('auth_public.subtitle') }}</p>

            @if ($errorText)
                <div class="alert-error" id="login-error" role="alert">
                    <strong>{{ __('auth_public.error_title') }}</strong>
                    {{ $errorText }}
                    <span class="alert-hint">{{ __('auth_public.attempts_hint') }}</span>
                </div>
            @endif

            <form id="login-form" action="{{ route('login') }}" method="post">
                @csrf

                <div class="field">
                    <label for="email">{{ __('auth_public.email_label') }}</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           autocomplete="username" inputmode="email" autocapitalize="off" spellcheck="false"
                           @if ($errorText) aria-invalid="true" aria-describedby="login-error" @endif>
                </div>

                <div class="field">
                    <label for="password">{{ __('auth_public.password_label') }}</label>
                    <div class="pw-field">
                        <input type="password" id="password" name="password" required
                               autocomplete="current-password"
                               @if ($errorText) aria-invalid="true" aria-describedby="login-error" @endif>
                        {{-- Se muestra solo si el JavaScript funciona; sin JS el campo queda normal. --}}
                        <button type="button" class="pw-toggle" id="pw-toggle" aria-controls="password" aria-pressed="false" aria-label="{{ __('auth_public.show_password_label') }}" hidden>
                            <i class="fas fa-eye" aria-hidden="true"></i>
                            <span class="pw-toggle-text">{{ __('auth_public.show_password') }}</span>
                        </button>
                    </div>
                </div>

                <button type="submit" id="login-submit" class="btn-portal btn-portal-primary btn-portal-lg btn-portal-block">
                    <span class="btn-label">{{ __('auth_public.submit') }}</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <section class="help" aria-labelledby="help-title">
                <h2 id="help-title">{{ __('auth_public.help_title') }}</h2>
                <p>{{ __('auth_public.no_access') }}</p>

                @if ($whatsappDigits !== '')
                    <a class="btn-portal btn-portal-outline btn-portal-block"
                       href="https://wa.me/{{ $whatsappDigits }}?text={{ rawurlencode(__('auth_public.whatsapp_message')) }}"
                       target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-whatsapp" aria-hidden="true"></i> {{ __('auth_public.whatsapp_button') }}
                        <span class="sr-only">{{ __('auth_public.whatsapp_new_tab') }}</span>
                    </a>
                @endif
                {{-- PENDIENTE: el botón de WhatsApp aparece cuando se define YURA_WHATSAPP en el .env. --}}

                @if ($canRecover)
                    <p><a href="{{ route('password.request') }}">{{ __('auth_public.forgot_link') }}</a></p>
                @else
                    {{-- No existe la ruta de recuperación: se indica a quién pedir el cambio. --}}
                    <p class="muted">{{ __('auth_public.forgot_text') }}</p>
                @endif
            </section>
        </div>

        <div class="card-foot">
            <p><i class="fas fa-lock" aria-hidden="true"></i> {{ __('auth_public.security_note') }}</p>
            <p><a href="{{ route('public.privacy') }}">{{ __('auth_public.privacy_link') }}</a></p>
            <p>{{ __('auth_public.footer', ['year' => date('Y')]) }}</p>
        </div>
    </main>

    <script>
        (function () {
            var password = document.getElementById('password');
            var toggle = document.getElementById('pw-toggle');
            var form = document.getElementById('login-form');
            var submit = document.getElementById('login-submit');
            var t = {{ \Illuminate\Support\Js::from([
                'show' => __('auth_public.show_password'),
                'hide' => __('auth_public.hide_password'),
                'showLabel' => __('auth_public.show_password_label'),
                'hideLabel' => __('auth_public.hide_password_label'),
                'submitting' => __('auth_public.submitting'),
                'submit' => __('auth_public.submit'),
            ]) }};

            if (password && toggle) {
                toggle.hidden = false;
                password.parentNode.className += ' has-toggle';
                toggle.addEventListener('click', function () {
                    var show = password.type === 'password';
                    password.type = show ? 'text' : 'password';
                    toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                    toggle.setAttribute('aria-label', show ? t.hideLabel : t.showLabel);
                    toggle.querySelector('.pw-toggle-text').textContent = show ? t.hide : t.show;
                    toggle.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
                    password.focus();
                });
            }

            // Evita enviar dos veces el formulario (cada intento cuenta para el límite por minuto).
            if (form && submit) {
                form.addEventListener('submit', function () {
                    submit.disabled = true;
                    submit.querySelector('.btn-label').textContent = t.submitting;
                });
                window.addEventListener('pageshow', function (event) {
                    if (event.persisted) {
                        submit.disabled = false;
                        submit.querySelector('.btn-label').textContent = t.submit;
                    }
                });
            }
        })();
    </script>
</body>
</html>
