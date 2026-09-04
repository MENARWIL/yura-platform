@extends('layouts.app')

@section('title', 'Registrar tutor')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-8">
        <div class="card card-quechua">
            <div class="card-header card-header-quechua">
                <h3 class="card-title mb-0">Registrar tutor</h3>
            </div>

            <form action="{{ route('tutors.store') }}" method="POST">
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
                                    class="form-control @error('ci') is-invalid @enderror"
                                    value="{{ old('ci') }}"
                                    required
                                    maxlength="50"
                                >
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
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone') }}"
                                    required
                                    maxlength="20"
                                >
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
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}"
                                    required
                                    maxlength="100"
                                >
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
                                    class="form-control @error('lastname') is-invalid @enderror"
                                    value="{{ old('lastname') }}"
                                    required
                                    maxlength="100"
                                >
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
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            required
                            maxlength="255"
                        >
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
                            class="form-control @error('password') is-invalid @enderror"
                            required
                            minlength="8"
                        >
                        <small class="form-text text-muted">Debe tener al menos 8 caracteres.</small>
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    </div>

                    <div class="mt-4">
                        <button type="button" id="add-second-tutor" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-plus mr-1"></i> Agregar segundo tutor
                        </button>
                    </div>

                    <div id="second-tutor-block" class="border rounded p-3 mt-3 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Segundo tutor</h5>
                            <button type="button" id="remove-second-tutor" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash-alt mr-1"></i> Eliminar
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="second_ci">C.I.</label>
                                    <input type="text" id="second_ci" name="second_tutor[ci]" class="form-control" maxlength="50" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="second_phone">Teléfono</label>
                                    <input type="tel" id="second_phone" name="second_tutor[phone]" class="form-control" maxlength="20" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="second_name">Nombre</label>
                                    <input type="text" id="second_name" name="second_tutor[name]" class="form-control" maxlength="100" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="second_lastname">Apellido</label>
                                    <input type="text" id="second_lastname" name="second_tutor[lastname]" class="form-control" maxlength="100" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="second_email">Correo electrónico</label>
                            <input type="email" id="second_email" name="second_tutor[email]" class="form-control" maxlength="255" disabled>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white text-right">
                    <a href="{{ route('students.create') }}" class="btn btn-link text-muted mr-2">Volver al registro de estudiante</a>
                    <button type="submit" class="btn btn-quechua px-5">
                        <i class="fas fa-save mr-1"></i> Guardar tutor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addButton = document.getElementById('add-second-tutor');
        const removeButton = document.getElementById('remove-second-tutor');
        const secondTutorBlock = document.getElementById('second-tutor-block');

        if (!addButton || !removeButton || !secondTutorBlock) return;

        const secondTutorFields = secondTutorBlock.querySelectorAll('input');

        addButton.addEventListener('click', function () {
            secondTutorBlock.classList.remove('d-none');
            addButton.classList.add('d-none');
            secondTutorFields.forEach(function (field) {
                field.disabled = false;
            });
        });

        removeButton.addEventListener('click', function () {
            secondTutorFields.forEach(function (field) {
                field.value = '';
                field.disabled = true;
            });
            secondTutorBlock.classList.add('d-none');
            addButton.classList.remove('d-none');
        });
    });
</script>
@endsection
