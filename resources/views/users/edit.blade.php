@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-8">
        <div class="card card-quechua">
            <div class="card-header card-header-quechua">
                <h3 class="card-title">Editar Usuario: {{ $user->name }}</h3>
            </div>
            <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Nombre Completo</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                                @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                                @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Rol del Usuario</label>
                                <select name="rol" class="form-control" required>
                                    <option value="padre" {{ old('rol', $user->rol) == 'padre' ? 'selected' : '' }}>Padre</option>
                                    <option value="madre" {{ old('rol', $user->rol) == 'madre' ? 'selected' : '' }}>Madre</option>
                                    <option value="tutor" {{ old('rol', $user->rol) == 'tutor' ? 'selected' : '' }}>Tutor</option>
                                    <option value="profesor" {{ old('rol', $user->rol) == 'profesor' ? 'selected' : '' }}>Profesor</option>
                                    <option value="admin" {{ old('rol', $user->rol) == 'admin' ? 'selected' : '' }}>Administrador</option>
                                    <option value="estudiante" {{ old('rol', $user->rol) == 'estudiante' ? 'selected' : '' }}>Estudiante</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Teléfono (Opcional)</label>
                                <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $user->telefono) }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label>Foto actual</label>
                        @if($user->foto_path)
                            <div class="mb-3">
                                <img src="{{ asset('storage/' . $user->foto_path) }}" alt="Foto de {{ $user->name }}" class="img-fluid rounded shadow-sm" style="max-height: 160px;">
                            </div>
                        @else
                            <div class="mb-3 text-muted">
                                <i class="fas fa-user-circle fa-3x"></i>
                                <div>Sin foto cargada</div>
                            </div>
                        @endif
                        <label>Actualizar foto</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <small class="text-muted">JPG, PNG o WEBP (máximo 2 MB).</small>
                        @error('foto') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3 mt-3 border-top pt-3">
                        <label>Cambiar Contraseña (Dejar en blanco para mantener la actual)</label>
                        <input type="password" name="password" class="form-control">
                        <small class="text-muted">Solo si desea actualizar la contraseña del usuario.</small>
                        @error('password') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="card-footer bg-white text-right">
                    <a href="{{ route('users.index') }}" class="btn btn-link text-muted mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-quechua px-5">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
