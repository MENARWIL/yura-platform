<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YURA Platform | Login</title>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
