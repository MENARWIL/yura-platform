@extends('layouts.app')

@section('title', 'Actualizar tutor')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-8">
        <div class="card card-quechua">
            <div class="card-header card-header-quechua">
                <h3 class="card-title mb-0">Actualizar información del tutor</h3>
            </div>
            <form action="{{ route('tutors.update', $tutor) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Desactivar un tutor conserva su historial y evita que pueda ser asignado nuevamente.
                    </div>
                    <div class="form-group">
                        <label for="name">Nombre completo</label>
                        <input id="name" name="name" value="{{ old('name', $tutor->name) }}" class="form-control js-person-field @error('name') is-invalid @enderror" data-rule="name" required>
                        <small class="invalid-feedback"></small>
                        @error('name') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="phone">Teléfono</label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone', $tutor->phone) }}" class="form-control js-person-field @error('phone') is-invalid @enderror" data-rule="phone" inputmode="tel" required>
                            <small class="invalid-feedback"></small>
                            @error('phone') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="status">Estado</label>
                            <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $tutor->status) === 'active' || old('status', $tutor->status) === 'activo' ? 'selected' : '' }}>Activo</option>
                                <option value="inactive" {{ old('status', $tutor->status) === 'inactive' || old('status', $tutor->status) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('status') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $tutor->email) }}" class="form-control js-person-field @error('email') is-invalid @enderror" data-rule="email" required>
                        <small class="invalid-feedback"></small>
                        @error('email') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="password">Nueva contraseña (opcional)</label>
                        <input id="password" type="password" name="password" class="form-control js-person-field @error('password') is-invalid @enderror" data-rule="password">
                        <small class="form-text text-muted">Déjalo vacío para conservar la contraseña actual.</small>
                        <small class="invalid-feedback"></small>
                        @error('password') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="card-footer bg-white text-right">
                    <a href="{{ route('family-members.index') }}" class="btn btn-link text-muted mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-quechua px-5"><i class="fas fa-save mr-1"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/user-form-validation.js') }}"></script>
@endpush
@endsection
