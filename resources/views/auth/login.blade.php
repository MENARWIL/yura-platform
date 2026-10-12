<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YURA Platform | Login</title>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        /* Institutional palette scoped to the login page (blue dominant, red accent, white surface). */
        body.auth-page-bg {
            --yura-blue: #0B3D91;
            --yura-blue-dark: #082C6C;
            --yura-blue-light: #E7EEF9;
            --yura-red: #C8102E;
            --yura-red-dark: #9E0B24;
            --yura-red-light: #FCE8EB;
            --yura-white: #FFFFFF;
            --yura-border: #DDE3EC;
            /* Capa ligera: la imagen de fondo debe seguir viéndose */
            background: linear-gradient(160deg, rgba(8, 44, 108, 0.55), rgba(11, 61, 145, 0.30) 55%, rgba(200, 16, 46, 0.25)), url('{{ asset('img/yura_bg.png') }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        body.auth-page-bg::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            z-index: 10;
            background: linear-gradient(90deg, #C8102E 0 33.33%, #FFFFFF 33.33% 66.66%, #0B3D91 66.66% 100%);
        }

        body.auth-page-bg .auth-card {
            /* Tarjeta de cristal translúcida, como el diseño original */
            background: rgba(255, 255, 255, 0.10) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
            border-top: 4px solid var(--yura-red) !important;
            color: var(--yura-white);
        }

        body.auth-page-bg .auth-card h1 {
            color: var(--yura-white) !important;
        }

        body.auth-page-bg .auth-card .form-control:focus {
            border-color: var(--yura-blue);
            box-shadow: 0 0 0 0.2rem rgba(231, 238, 249, 0.45);
        }

        body.auth-page-bg .auth-card .input-group-text {
            background: var(--yura-blue-light);
            color: var(--yura-blue);
            border-color: var(--yura-border);
        }

        body.auth-page-bg .auth-card .text-warning {
            color: var(--yura-red-light) !important;
        }

        body.auth-page-bg .btn-quechua {
            background: #FFFFFF;
            color: #0B3D91;
            border: 1px solid #FFFFFF;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 10px 24px -8px rgba(0, 0, 0, 0.45);
        }

        body.auth-page-bg .btn-quechua:hover,
        body.auth-page-bg .btn-quechua:focus {
            background: #EEF3FD;
            color: #082C6C;
            box-shadow: 0 12px 28px -8px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body class="auth-page-bg">
<div class="animate-fade-in" style="width: 100%; display: flex; justify-content: center;">
    <div class="card auth-card">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h1 class="display-4 font-weight-bold" style="color: var(--primary-main);">YURA</h1>
                <p class="lead">Plataforma Educativa de Quechua</p>
            </div>
            
            <p class="text-center mb-4 text-light opacity-75">Bienvenido, por favor ingresa tus datos.</p>

            <form action="{{ route('login') }}" method="post">
                @csrf
                <div class="form-group mb-4">
                    <label class="text-light small">Correo Electrónico</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="nombre@ejemplo.com" value="{{ old('email') }}" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    @error('email')
                        <span class="text-warning small">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group mb-4">
                    <label class="text-light small">Contraseña</label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-quechua btn-block py-3 mb-4">
                    Iniciar Sesión <i class="fas fa-arrow-right ml-2"></i>
                </button>

                <div class="text-center mb-3">
                    <a href="#" class="small text-light opacity-50">¿Olvidaste tu contraseña?</a>
                </div>
            </form>

                <div class="text-center">
                    <p class="mb-0 text-light opacity-75 small">
                        YURA Platform &copy; 2026 | Tecnología para la Educación
                    </p>
                </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/js/adminlte.min.js') }}"></script>
</body>
</html>
