@extends('layouts.app')

@section('title', 'Nuevo Usuario')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-8">
        <div class="card card-quechua">
            <div class="card-header card-header-quechua">
                <h3 class="card-title">Registrar Nuevo Usuario</h3>
            </div>
            <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Nombre Completo</label>
                                <input type="text" name="name" class="form-control" placeholder="Ej. Juan Perez" value="{{ old('name') }}" required>
                                @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" placeholder="usuario@yura.com" value="{{ old('email') }}" required>
                                @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Rol del Usuario</label>
                                <select name="rol" class="form-control" required>
                                    <option value="padre" {{ old('rol') == 'padre' ? 'selected' : '' }}>Padre</option>
                                    <option value="madre" {{ old('rol') == 'madre' ? 'selected' : '' }}>Madre</option>
                                    <option value="tutor" {{ old('rol') == 'tutor' ? 'selected' : '' }}>Tutor</option>
                                    <option value="profesor" {{ old('rol') == 'profesor' ? 'selected' : '' }}>Profesor</option>
                                    <option value="admin" {{ old('rol') == 'admin' ? 'selected' : '' }}>Administrador</option>
                                    <option value="estudiante" {{ old('rol') == 'estudiante' ? 'selected' : '' }}>Estudiante</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Teléfono (Opcional)</label>
                                <input type="text" name="telefono" class="form-control" placeholder="+591 ..." value="{{ old('telefono') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label>Foto del usuario</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <small class="text-muted">JPG, PNG o WEBP (máximo 2 MB).</small>
                        @error('foto') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label>Contraseña Temporal</label>
                        <input type="password" name="password" class="form-control" required>
                        <small class="text-muted">Mínimo 8 caracteres, incluir mayúsculas, números y símbolos.</small>
                        @error('password') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="card-footer bg-white text-right">
                    <a href="{{ route('users.index') }}" class="btn btn-link text-muted mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-quechua px-5">
                        <i class="fas fa-save mr-1"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
