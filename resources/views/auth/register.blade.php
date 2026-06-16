<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YURA Platform | Registro</title>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body class="auth-page-bg">
<div class="animate-fade-in py-5" style="width: 100%; display: flex; justify-content: center;">
    <div class="card auth-card mt-5 mb-5">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h1 class="h2 font-weight-bold" style="color: var(--primary-main);">Únete a YURA</h1>
                <p>Crea tu cuenta para empezar a enseñar</p>
            </div>

            <form action="{{ route('register') }}" method="post">
                @csrf
                <div class="form-group mb-3">
                    <label class="text-light small">Nombre Completo</label>
                    <input type="text" name="name" class="form-control" placeholder="Ej. Juan Perez" value="{{ old('name') }}" required>
                    @error('name') <span class="text-warning small">{{ $message }}</span> @enderror
                </div>

                <div class="form-group mb-3">
                    <label class="text-light small">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control" placeholder="nombre@ejemplo.com" value="{{ old('email') }}" required>
                    @error('email') <span class="text-warning small">{{ $message }}</span> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-light small">Rol</label>
                            <select name="rol" class="form-control" required>
                                <option value="padre">Padre</option>
                                <option value="profesor">Profesor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-light small">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" placeholder="Opcional" value="{{ old('telefono') }}">
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="text-light small">Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                    <small class="text-light opacity-50 d-block mt-1">Debe incluir: mayúscula, número y símbolo.</small>
                    @error('password') <span class="text-warning small d-block mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-group mb-4">
                    <label class="text-light small">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-quechua btn-block py-3 mb-4">
                    Comenzar Ahora <i class="fas fa-rocket ml-2"></i>
                </button>
            </form>

            <div class="text-center">
                <p class="mb-0 text-light opacity-75">
                    ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-weight-bold" style="color: var(--primary-main);">Inicia sesión</a>
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
