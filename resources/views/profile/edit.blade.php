@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-8">
        <div class="card card-quechua">
            <div class="card-header card-header-quechua">
                <h3 class="card-title">Configuración de Perfil</h3>
            </div>
            {{-- novalidate: avisa la web (texto rojo debajo del campo), no el globo del navegador. --}}
            <form action="{{ route('profile.update') }}" method="POST" novalidate>
                @csrf
                @method('PATCH')
                <div class="card-body p-4">
                    <div class="form-group mb-4">
                        <label for="name" class="font-weight-bold small">Nombre Completo (se muestra en el menú lateral)</label>
                        <input type="text" id="name" name="name" class="form-control form-control-lg js-user-field @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        <span class="invalid-feedback">@error('name'){{ $message }}@enderror</span>
                    </div>

                    <div class="form-group mb-4">
                        <label for="email" class="font-weight-bold small">Correo Electrónico</label>
                        <input type="email" id="email" name="email" class="form-control form-control-lg js-user-field @error('email') is-invalid @enderror" data-rule="email" value="{{ old('email', $user->email) }}" required>
                        <span class="invalid-feedback">@error('email'){{ $message }}@enderror</span>
                    </div>

                    <hr class="my-4">
                    <h5 class="text-muted mb-3 small font-weight-bold uppercase">Cambiar Contraseña (Opcional)</h5>

                    <div class="form-group mb-4">
                        <label for="password" class="font-weight-bold small">Nueva Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control js-user-field @error('password') is-invalid @enderror" data-rule="password"
                               autocomplete="new-password" placeholder="Dejar en blanco para no cambiar">
                        <span class="invalid-feedback">@error('password'){{ $message }}@enderror</span>
                        <small class="form-text text-muted">Al menos 8 caracteres, con una mayúscula, una minúscula, un número y un símbolo (por ejemplo ! ? # @).</small>
                    </div>

                    <div class="form-group mb-4">
                        <label for="password_confirmation" class="font-weight-bold small">Confirmar Nueva Contraseña</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control js-user-field" data-rule="confirm" data-match="password"
                               autocomplete="new-password">
                        <span class="invalid-feedback"></span>
                    </div>
                </div>

                <div class="card-footer bg-light text-right p-4">
                    <button type="submit" class="btn btn-quechua px-5">
                        Guardar Cambios <i class="fas fa-save ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="{{ asset('js/user-form-validation.js') }}?v={{ @filemtime(public_path('js/user-form-validation.js')) }}"></script>
@endsection
