@extends('layouts.app')

@section('title', 'Editar profesor')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-8">
        <div class="card card-quechua">
            <div class="card-header card-header-quechua">
                <h3 class="card-title mb-0">Editar profesor: {{ $user->name }}</h3>
            </div>
            <form action="{{ route('teachers.update', $user) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nombre completo</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control js-person-field @error('name') is-invalid @enderror" data-rule="name" required>
                        <small class="invalid-feedback"></small>
                        @error('name') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="email">Correo electrónico de acceso</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control js-person-field @error('email') is-invalid @enderror" data-rule="email" required>
                        <small class="invalid-feedback"></small>
                        @error('email') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="telefono">Teléfono</label>
                            <input id="telefono" type="tel" name="telefono" value="{{ old('telefono', $user->telefono ?? $user->phone) }}" class="form-control js-person-field @error('telefono') is-invalid @enderror" data-rule="phone" inputmode="tel" required>
                            <small class="invalid-feedback"></small>
                            @error('telefono') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="main_subject_id">Asignatura principal</label>
                            <select id="main_subject_id" name="main_subject_id" class="form-control js-person-field @error('main_subject_id') is-invalid @enderror" required>
                                <option value="" selected disabled>Seleccione una asignatura</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ old('main_subject_id', $user->main_subject_id) == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                @endforeach
                            </select>
                            <small class="invalid-feedback"></small>
                            @error('main_subject_id') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Nueva contraseña (opcional)</label>
                        <input id="password" type="password" name="password" class="form-control js-person-field @error('password') is-invalid @enderror" data-rule="password">
                        <small class="form-text text-muted">Déjalo vacío para conservar la actual. Si lo completas, debe tener mayúsculas, minúsculas, números y símbolos.</small>
                        <small class="invalid-feedback"></small>
                        @error('password') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="foto">Actualizar foto (opcional)</label>
                        <input id="foto" type="file" name="foto" class="form-control-file @error('foto') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                        @error('foto') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="card-footer bg-white text-right">
                    <a href="{{ route('teachers.index') }}" class="btn btn-link text-muted mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-quechua px-5"><i class="fas fa-sync-alt mr-1"></i> Actualizar profesor</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/user-form-validation.js') }}"></script>
@endpush
@endsection
