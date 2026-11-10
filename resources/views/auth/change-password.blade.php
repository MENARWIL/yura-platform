@extends('layouts.app')

@section('title', 'Cambia tu contraseña')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-lg-6 col-md-8">
        <div class="card card-quechua">
            <div class="card-header card-header-quechua">
                <h3 class="card-title"><i class="fas fa-lock mr-2" aria-hidden="true"></i> Cambia tu contraseña</h3>
            </div>
            <form action="{{ route('password.change.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body p-4">
                    <p class="mb-4">
                        Hola {{ $user->first_name ?: $user->name }}, entraste con una <strong>contraseña temporal</strong>.
                        Elige una tuya para seguir.
                        @if($user->temporary_password_expires_at)
                            La temporal vence el {{ $user->temporary_password_expires_at->format('d/m/Y \a \l\a\s H:i') }}.
                        @endif
                    </p>

                    <div class="form-group mb-3">
                        <label for="password" class="font-weight-bold small">Nueva contraseña</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8"
                               class="form-control form-control-lg @error('password') is-invalid @enderror">
                        @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        <small class="form-text text-muted">Al menos 8 caracteres, con letras y números.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="password_confirmation" class="font-weight-bold small">Repite la nueva contraseña</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required minlength="8"
                               class="form-control form-control-lg">
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check mr-1" aria-hidden="true"></i> Guardar contraseña</button>
                </div>
            </form>
            <form action="{{ route('logout') }}" method="POST" class="px-4 pb-4">
                @csrf
                <button type="submit" class="btn btn-link text-muted px-0">Salir sin cambiarla</button>
            </form>
        </div>
    </div>
</div>
@endsection
