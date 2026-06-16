@extends('layouts.app')

@section('title', 'Editar Estudiante')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-10">
        <div class="card card-quechua border-0">
            <div class="card-header card-header-quechua">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-user-edit mr-2"></i> Modificar Datos de {{ $student->nombre }}
                </h3>
            </div>
            <form action="{{ route('students.update', $student->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body p-5 bg-white">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6 border-right pr-md-4">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-warning-light rounded-circle p-2 mr-3" style="background: rgba(249, 212, 35, 0.2);">
                                    <i class="fas fa-id-badge text-warning"></i>
                                </div>
                                <h5 class="mb-0 text-dark font-weight-bold">Actualizar Perfil</h5>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">Nombre Completo</label>
                                <input type="text" name="nombre" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('nombre') is-invalid @enderror" value="{{ old('nombre', $student->nombre) }}" required style="font-size: 1rem;">
                                @error('nombre') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">Edad</label>
                                        <input type="number" name="edad" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('edad') is-invalid @enderror" value="{{ old('edad', $student->edad) }}" required style="font-size: 1rem;">
                                        @error('edad') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">Género</label>
                                        <select name="genero" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('genero') is-invalid @enderror" required style="font-size: 1rem; appearance: none;">
                                            <option value="Masculino" {{ old('genero', $student->genero) == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                                            <option value="Femenino" {{ old('genero', $student->genero) == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                                        </select>
                                        @error('genero') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">Nivel Educativo</label>
                                <select name="nivel" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('nivel') is-invalid @enderror" required style="font-size: 1rem; appearance: none;">
                                    <option value="básico" {{ old('nivel', $student->nivel) == 'básico' ? 'selected' : '' }}>QALLARIY (Básico)</option>
                                    <option value="intermedio" {{ old('nivel', $student->nivel) == 'intermedio' ? 'selected' : '' }}>CHAWPI (Intermedio)</option>
                                    <option value="avanzado" {{ old('nivel', $student->nivel) == 'avanzado' ? 'selected' : '' }}>SUMAQ (Avanzado)</option>
                                </select>
                                @error('nivel') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">Foto actual</label>
                                <div class="mb-3">
                                    @if($student->foto_path)
                                        <img src="{{ asset('storage/' . $student->foto_path) }}" alt="Foto de {{ $student->nombre }}" class="img-fluid rounded shadow-sm" style="max-height: 180px;">
                                    @else
                                        <div class="rounded bg-light p-4 text-center text-muted">
                                            <i class="fas fa-user-circle fa-3x mb-2"></i><br>
                                            Sin foto cargada
                                        </div>
                                    @endif
                                </div>
                                <label class="text-muted small text-uppercase font-weight-bold">Actualizar foto</label>
                                <input type="file" name="foto" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('foto') is-invalid @enderror" accept="image/*">
                                <small class="text-muted">JPG, PNG o WEBP (máximo 2 MB).</small>
                                @error('foto') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group mb-0">
                                <label class="text-muted small text-uppercase font-weight-bold">Estado de Cuenta</label>
                                <select name="estado" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('estado') is-invalid @enderror" required style="font-size: 1rem; appearance: none;">
                                    <option value="activo" {{ old('estado', $student->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                                    <option value="inactivo" {{ old('estado', $student->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @error('estado') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6 pl-md-4 mt-4 mt-md-0">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-success-light rounded-circle p-2 mr-3" style="background: rgba(40, 167, 69, 0.15);">
                                    <i class="fas fa-sliders-h text-success"></i>
                                </div>
                                <h5 class="mb-0 text-dark font-weight-bold">Métricas y Asignación</h5>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">Responsable Principal</label>
                                <select name="usuario_id" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('usuario_id') is-invalid @enderror" required style="font-size: 1rem;">
                                    @foreach($padres as $padre)
                                        <option value="{{ $padre->id }}" {{ old('usuario_id', $student->usuario_id) == $padre->id ? 'selected' : '' }}>
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
                                        <option value="{{ $profesor->id }}" {{ old('profesor_id', $student->profesor_id) == $profesor->id ? 'selected' : '' }}>
                                            {{ $profesor->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('profesor_id') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">Escritura</label>
                                        <input type="number" name="nota_escritura" class="form-control border-0 bg-light rounded-pill px-3" value="{{ old('nota_escritura', $student->nota_escritura) }}" min="0" max="100">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">Examen</label>
                                        <input type="number" name="nota_examen" class="form-control border-0 bg-light rounded-pill px-3" value="{{ old('nota_examen', $student->nota_examen) }}" min="0" max="100">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">Asistencia %</label>
                                        <input type="number" name="asistencia" class="form-control border-0 bg-light rounded-pill px-3" value="{{ old('asistencia', $student->asistencia) }}" min="0" max="100">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="text-muted small text-uppercase font-weight-bold text-warning">Puntaje acumulado Robot</label>
                                <input type="number" name="puntaje" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('puntaje') is-invalid @enderror" value="{{ old('puntaje', $student->puntaje) }}" min="0" max="100" style="font-size: 1rem;">
                                @error('puntaje') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light p-4 text-center border-0" style="border-radius: 0 0 20px 20px;">
                    <a href="{{ route('students.index') }}" class="btn btn-link text-muted mr-3 font-weight-bold">Cancelar cambios</a>
                    <button type="submit" class="btn btn-quechua px-5 py-3 shadow-lg">
                        Guardar Cambios <i class="fas fa-save ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
