@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-8">
        <div class="card card-quechua">
            <div class="card-header card-header-quechua">
                <h3 class="card-title">Configuración de Perfil</h3>
            </div>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="card-body p-4">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold small">Nombre Completo (Se mostrará como Integrante)</label>
                        <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold small">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control form-control-lg @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                        @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <hr class="my-4">
                    <h5 class="text-muted mb-3 small font-weight-bold uppercase">Cambiar Contraseña (Opcional)</h5>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold small">Nueva Contraseña</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Dejar en blanco para no cambiar">
                        @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold small">Confirmar Nueva Contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control">
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
@endsection
