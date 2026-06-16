@extends('layouts.app')

@section('title', 'Nuevo Estudiante')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-10">
        <div class="card card-quechua border-0">
            <div class="card-header card-header-quechua">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-user-plus mr-2"></i> Formulario de Registro de Estudiante
                </h3>
            </div>
            <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body p-5 bg-white">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6 border-right pr-md-4">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-primary-light rounded-circle p-2 mr-3" style="background: rgba(249, 212, 35, 0.2);">
                                    <i class="fas fa-id-card text-warning"></i>
                                </div>
                                <h5 class="mb-0 text-dark font-weight-bold">Información Personal</h5>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">Nombre Completo</label>
                                <input type="text" name="nombre" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" placeholder="Ej. Tupac Amaru" required style="font-size: 1rem;">
                                @error('nombre') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">Edad</label>
                                        <input type="number" name="edad" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('edad') is-invalid @enderror" value="{{ old('edad') }}" placeholder="0" required style="font-size: 1rem;">
                                        @error('edad') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">Género</label>
                                        <select name="genero" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('genero') is-invalid @enderror" required style="font-size: 1rem; appearance: none;">
                                            <option value="Masculino" {{ old('genero') == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                                            <option value="Femenino" {{ old('genero') == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                                        </select>
                                        @error('genero') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">Nivel Educativo</label>
                                <select name="nivel" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('nivel') is-invalid @enderror" required style="font-size: 1rem; appearance: none;">
                                    <option value="básico" {{ old('nivel') == 'básico' ? 'selected' : '' }}>QALLARIY (Básico)</option>
                                    <option value="intermedio" {{ old('nivel') == 'intermedio' ? 'selected' : '' }}>CHAWPI (Intermedio)</option>
                                    <option value="avanzado" {{ old('nivel') == 'avanzado' ? 'selected' : '' }}>SUMAQ (Avanzado)</option>
                                </select>
                                @error('nivel') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">Foto del estudiante</label>
                                <input type="file" name="foto" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('foto') is-invalid @enderror" accept="image/*">
                                <small class="text-muted">JPG, PNG o WEBP (máximo 2 MB).</small>
                                @error('foto') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6 pl-md-4 mt-4 mt-md-0">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-info-light rounded-circle p-2 mr-3" style="background: rgba(23, 162, 184, 0.15);">
                                    <i class="fas fa-university text-info"></i>
                                </div>
                                <h5 class="mb-0 text-dark font-weight-bold">Asignación Académica</h5>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">Responsable Principal</label>
                                <select name="usuario_id" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('usuario_id') is-invalid @enderror" required style="font-size: 1rem;">
                                    <option value="">Seleccione al responsable principal</option>
                                    @foreach($padres as $padre)
                                        <option value="{{ $padre->id }}" {{ old('usuario_id') == $padre->id ? 'selected' : '' }}>
                                            {{ $padre->name }} ({{ ucfirst($padre->rol) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Puede ser padre, madre o tutor.</small>
                                @error('usuario_id') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">Profesor Tutor</label>
                                <select name="profesor_id" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('profesor_id') is-invalid @enderror" style="font-size: 1rem;">
                                    <option value="">Sin asignar (libre)</option>
                                    @foreach($profesores as $profesor)
                                        <option value="{{ $profesor->id }}" {{ old('profesor_id') == $profesor->id ? 'selected' : '' }}>
                                            {{ $profesor->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('profesor_id') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label class="text-muted small text-uppercase font-weight-bold">Fecha de Registro</label>
                                        <input type="date" name="fecha_registro" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('fecha_registro') is-invalid @enderror" value="{{ old('fecha_registro', date('Y-m-d')) }}" required style="font-size: 1rem;">
                                        @error('fecha_registro') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label class="text-muted small text-uppercase font-weight-bold text-warning">Puntaje Robot</label>
                                        <input type="number" name="puntaje" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('puntaje') is-invalid @enderror" value="{{ old('puntaje', 0) }}" min="0" max="100" style="font-size: 1rem;">
                                        @error('puntaje') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-4 rounded-xl border-0 shadow-none text-center" style="background: #fff8e1; border-radius: 20px;">
                        <p class="mb-0 text-warning-dark small font-weight-bold">
                            <i class="fas fa-robot mr-2"></i> Los datos biométricos deberán ser sincronizados con el robot YURA tras completar el registro.
                        </p>
                    </div>
                </div>

                <div class="card-footer bg-light p-4 text-center border-0" style="border-radius: 0 0 20px 20px;">
                    <a href="{{ route('students.index') }}" class="btn btn-link text-muted mr-3 font-weight-bold">Cancelar</a>
                    <button type="submit" class="btn btn-quechua px-5 py-3 shadow-lg">
                        Finalizar Registro <i class="fas fa-check-circle ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
