@extends('layouts.app')

@section('title', 'Registrar estudiante')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver al Listado
        </a>
        @if(auth()->user()->isAdmin() || auth()->user()->isAcademic())
            <a href="{{ route('tutors.create') }}" class="btn btn-outline-primary ml-2">
                <i class="fas fa-user-shield mr-1"></i> Crear tutor primero
            </a>
        @endif
    </div>

    <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h4 class="mb-0 text-dark"><i class="fas fa-user-plus text-primary mr-2"></i> Datos del estudiante</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="name">Nombre completo</label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="age">Edad</label>
                                <input type="number" name="age" id="age" class="form-control @error('age') is-invalid @enderror" value="{{ old('age') }}" min="0" max="120" required>
                                @error('age') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="gender">Género</label>
                                <select name="gender" id="gender" class="form-control @error('gender') is-invalid @enderror" required>
                                    <option value="">Seleccionar</option>
                                    <option value="Masculino" {{ old('gender') === 'Masculino' ? 'selected' : '' }}>Masculino</option>
                                    <option value="Femenino" {{ old('gender') === 'Femenino' ? 'selected' : '' }}>Femenino</option>
                                </select>
                                @error('gender') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="foto">Foto del estudiante</label>
                                <input type="file" name="foto" id="foto" class="form-control-file @error('foto') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                                @error('foto') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h4 class="mb-0 text-dark"><i class="fas fa-graduation-cap text-success mr-2"></i> Asignación académica</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="course_id">Curso</label>
                                <select name="course_id" id="course_id" class="form-control @error('course_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar curso</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                                    @endforeach
                                </select>
                                @error('course_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="parallel_id">Paralelo</label>
                                <select name="parallel_id" id="parallel_id" class="form-control @error('parallel_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar paralelo</option>
                                    @foreach($parallels as $parallel)
                                        <option value="{{ $parallel->id }}" {{ old('parallel_id') == $parallel->id ? 'selected' : '' }}>{{ $parallel->name }}</option>
                                    @endforeach
                                </select>
                                @error('parallel_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="teacher_user_id">Profesor</label>
                                <select name="teacher_user_id" id="teacher_user_id" class="form-control @error('teacher_user_id') is-invalid @enderror">
                                    <option value="">Sin asignar</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" {{ old('teacher_user_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                                @error('teacher_user_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="level">Nivel</label>
                                <select name="level" id="level" class="form-control @error('level') is-invalid @enderror" required>
                                    <option value="">Seleccionar nivel</option>
                                    <option value="básico" {{ old('level') === 'básico' ? 'selected' : '' }}>Básico</option>
                                    <option value="intermedio" {{ old('level') === 'intermedio' ? 'selected' : '' }}>Intermedio</option>
                                    <option value="avanzado" {{ old('level') === 'avanzado' ? 'selected' : '' }}>Avanzado</option>
                                </select>
                                @error('level') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="registration_date">Fecha de registro (automática)</label>
                                <input type="date" name="registration_date" id="registration_date" class="form-control @error('registration_date') is-invalid @enderror" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" readonly>
                                @error('registration_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="parent_user_id">Tutor</label>
                                <select name="parent_user_id" id="parent_user_id" class="form-control @error('parent_user_id') is-invalid @enderror">
                                    <option value="">Seleccionar tutor</option>
                                    @foreach($tutors as $tutor)
                                        <option value="{{ $tutor->id }}" {{ old('parent_user_id', $newTutorTarget === 'primary' ? $newTutorId : null) == $tutor->id ? 'selected' : '' }}>{{ $tutor->name }}</option>
                                    @endforeach
                                </select>
                                @error('parent_user_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="secondary_parent_user_id">Tutor secundario <span class="text-muted">(opcional)</span></label>
                                <select name="secondary_parent_user_id" id="secondary_parent_user_id" class="form-control @error('secondary_parent_user_id') is-invalid @enderror">
                                    <option value="">Sin tutor secundario</option>
                                    @foreach($tutors as $tutor)
                                        <option value="{{ $tutor->id }}" {{ old('secondary_parent_user_id', $newTutorTarget === 'secondary' ? $newTutorId : null) == $tutor->id ? 'selected' : '' }}>{{ $tutor->name }}</option>
                                    @endforeach
                                </select>
                                @error('secondary_parent_user_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="font-weight-bold mb-3">Resumen</h5>
                        <p class="text-muted mb-3">Completa los datos del estudiante y sus tutores para registrar la información académica.</p>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save mr-2"></i> Guardar estudiante
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

