@extends('layouts.app')

@section('title', 'Registrar tutor')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-8">
        <div class="card card-quechua">
            <div class="card-header card-header-quechua">
                <h3 class="card-title mb-0">Registrar tutor</h3>
            </div>

            <form action="{{ route('tutors.store') }}" method="POST" autocomplete="off">
                @csrf

                <div class="card-body">
                    <div id="primary-tutor-fields">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="ci">C.I.</label>
                                <input
                                    type="text"
                                    id="ci"
                                    name="ci"
                                    class="form-control js-person-field @error('ci') is-invalid @enderror"
                                    data-rule="ci"
                                    value="{{ old('ci') }}"
                                    required
                                    maxlength="50"
                                >
                                <small class="invalid-feedback"></small>
                                @error('ci')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="phone">Teléfono</label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    class="form-control js-person-field @error('phone') is-invalid @enderror"
                                    data-rule="phone"
                                    value="{{ old('phone') }}"
                                    required
                                    maxlength="20"
                                >
                                <small class="invalid-feedback"></small>
                                @error('phone')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="name">Nombre</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control js-person-field @error('name') is-invalid @enderror"
                                    data-rule="name"
                                    value="{{ old('name') }}"
                                    autocomplete="off"
                                    required
                                    maxlength="100"
                                >
                                <small class="invalid-feedback"></small>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="lastname">Apellido</label>
                                <input
                                    type="text"
                                    id="lastname"
                                    name="lastname"
                                    class="form-control js-person-field @error('lastname') is-invalid @enderror"
                                    data-rule="name"
                                    value="{{ old('lastname') }}"
                                    autocomplete="off"
                                    required
                                    maxlength="100"
                                >
                                <small class="invalid-feedback"></small>
                                @error('lastname')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="email">Correo electrónico</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control js-person-field @error('email') is-invalid @enderror"
                            data-rule="email"
                            value="{{ old('email') }}"
                            autocomplete="new-username"
                            required
                            maxlength="255"
                        >
                        <small class="invalid-feedback"></small>
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-0">
                        <label for="password">Contraseña</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control js-person-field @error('password') is-invalid @enderror"
                            data-rule="password"
                            autocomplete="new-password"
                            required
                            minlength="8"
                        >
                        <small class="form-text text-muted">Debe tener al menos 8 caracteres.</small>
                        <small class="invalid-feedback"></small>
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    </div>

                </div>

                <div class="card-footer bg-white text-right">
                    <a href="{{ route('students.create', ['target' => $target]) }}" class="btn btn-link text-muted mr-2">Volver al registro de estudiante</a>
                    <button type="submit" class="btn btn-quechua px-5">
                        <i class="fas fa-save mr-1"></i> Guardar tutor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/user-form-validation.js') }}"></script>
@endpush
