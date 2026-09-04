@extends('layouts.app')

@section('title', __('messages.update_profile'))

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-md-10">
        <div class="card card-quechua border-0">
                <div class="card-header card-header-quechua">
                    <h3 class="card-title font-weight-bold">
                    <i class="fas fa-user-edit mr-2"></i> {{ __('messages.update_profile') }} {{ $student->name }}
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
                                <h5 class="mb-0 text-dark font-weight-bold">{{ __('messages.update_profile') }}</h5>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.full_name') }}</label>
                                <input type="text" name="name" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('name') is-invalid @enderror" value="{{ old('name', $student->name) }}" required style="font-size: 1rem;">
                                @error('name') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.age') }}</label>
                                        <input type="number" name="age" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('age') is-invalid @enderror" value="{{ old('age', $student->age) }}" required style="font-size: 1rem;">
                                        @error('age') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.gender') }}</label>
                                        <select name="gender" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('gender') is-invalid @enderror" required style="font-size: 1rem; appearance: none;">
                                            <option value="Masculino" {{ old('gender', $student->gender) == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                                            <option value="Femenino" {{ old('gender', $student->gender) == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                                        </select>
                                        @error('gender') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.educational_level') }}</label>
                                <select name="level" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('level') is-invalid @enderror" required style="font-size: 1rem; appearance: none;">
                                    <option value="básico" {{ old('level', $student->level) == 'básico' ? 'selected' : '' }}>QALLARIY (Básico)</option>
                                    <option value="intermedio" {{ old('level', $student->level) == 'intermedio' ? 'selected' : '' }}>CHAWPI (Intermedio)</option>
                                    <option value="avanzado" {{ old('level', $student->level) == 'avanzado' ? 'selected' : '' }}>SUMAQ (Avanzado)</option>
                                </select>
                                @error('level') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror

                                <label class="text-muted small text-uppercase font-weight-bold">Fecha de registro</label>
                                <input type="date" name="registration_date" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('registration_date') is-invalid @enderror" value="{{ old('registration_date', optional($student->registration_date)->format('Y-m-d')) }}" required>
                                @error('registration_date') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.current_photo') }}</label>
                                <div class="mb-3">
                                    @if($student->foto_path)
                                            <img src="{{ asset('storage/' . $student->foto_path) }}" alt="{{ __('messages.photo_of', ['name' => $student->name]) }}" class="img-fluid rounded shadow-sm" style="max-height: 180px;">
                                    @else
                                        <div class="rounded bg-light p-4 text-center text-muted">
                                            <i class="fas fa-user-circle fa-3x mb-2"></i><br>
                                            {{ __('messages.no_photo_uploaded') }}
                                        </div>
                                    @endif
                                </div>
                                <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.update_photo') }}</label>
                                <input type="file" name="foto" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('foto') is-invalid @enderror" accept="image/*">
                                <small class="text-muted">JPG, PNG o WEBP (máximo 2 MB).</small>
                                @error('foto') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group mb-0">
                                <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.status') }}</label>
                                <select name="status" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('status') is-invalid @enderror" required style="font-size: 1rem; appearance: none;">
                                    <option value="activo" {{ old('status', $student->status) == 'activo' ? 'selected' : '' }}>{{ __('messages.status_active') }}</option>
                                    <option value="inactivo" {{ old('status', $student->status) == 'inactivo' ? 'selected' : '' }}>{{ __('messages.status_inactive') }}</option>
                                </select>
                                @error('status') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
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
                                <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.primary_responsible') }}</label>
                                <select name="parent_user_id" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('parent_user_id') is-invalid @enderror" style="font-size: 1rem;">
                                    @foreach($padres as $padre)
                                        <option value="{{ $padre->id }}" {{ old('parent_user_id', $student->parent_user_id) == $padre->id ? 'selected' : '' }}>
                                            {{ $padre->name }} ({{ ucfirst($padre->rol) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('messages.can_be_parent_or_tutor') ?? 'Puede ser padre, madre o tutor.' }}</small>
                                @error('parent_user_id') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.parallel') }}</label>
                                <select name="parallel_id" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('parallel_id') is-invalid @enderror" required style="font-size: 1rem;">
                                    <option value="">Seleccione un paralelo</option>
                                    @foreach($parallels as $parallel)
                                        <option value="{{ $parallel->id }}" {{ old('parallel_id', $student->parallel_id) == $parallel->id ? 'selected' : '' }}>
                                            {{ $parallel->course->name }} - {{ $parallel->name }} ({{ $parallel->students()->count() }}/{{ $parallel->max_students }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('parallel_id') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.tutor_teacher') }}</label>
                                <select name="teacher_user_id" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('teacher_user_id') is-invalid @enderror" style="font-size: 1rem;">
                                        <option value="">{{ __('messages.not_assigned') }} (libre)</option>
                                    @foreach($profesores as $profesor)
                                        <option value="{{ $profesor->id }}" {{ old('teacher_user_id', $student->teacher_user_id) == $profesor->id ? 'selected' : '' }}>
                                            {{ $profesor->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('teacher_user_id') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.writing') }}</label>
                                        <input type="number" name="writing_score" class="form-control border-0 bg-light rounded-pill px-3" value="{{ old('writing_score', $student->writing_score) }}" min="0" max="100">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.exam') ?? 'Examen' }}</label>
                                        <input type="number" name="exam_score" class="form-control border-0 bg-light rounded-pill px-3" value="{{ old('exam_score', $student->exam_score) }}" min="0" max="100">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-4">
                                        <label class="text-muted small text-uppercase font-weight-bold">{{ __('messages.attendance_percent') }}</label>
                                        <input type="number" name="attendance" class="form-control border-0 bg-light rounded-pill px-3" value="{{ old('attendance', $student->attendance) }}" min="0" max="100">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="text-muted small text-uppercase font-weight-bold text-warning">{{ __('messages.robot_score') }}</label>
                                <input type="number" name="score" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 @error('score') is-invalid @enderror" value="{{ old('score', $student->score) }}" min="0" max="100" style="font-size: 1rem;">
                                @error('score') <span class="invalid-feedback ml-3">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                    <div class="card-footer bg-light p-4 text-center border-0" style="border-radius: 0 0 20px 20px;">
                    <a href="{{ route('students.index') }}" class="btn btn-link text-muted mr-3 font-weight-bold">{{ __('messages.cancel_changes') }}</a>
                    <button type="submit" class="btn btn-quechua px-5 py-3 shadow-lg">
                        {{ __('messages.save_changes') }} <i class="fas fa-save ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
