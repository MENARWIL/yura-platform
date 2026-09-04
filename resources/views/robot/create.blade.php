@extends('layouts.app')

@section('title', 'Lanzar Actividad YURA')

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0">
        <div class="card-header card-header-quechua d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-robot mr-2"></i> Lanzar Actividad YURA
            </h3>
            <a href="{{ route('grades.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>
        <form action="{{ route('robot-activities.store') }}" method="POST">
            @csrf
            <div class="card-body bg-white p-4">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="text-muted small text-uppercase font-weight-bold">Paralelo</label>
                        <select name="parallel_id" class="form-control form-control-lg rounded-pill @error('parallel_id') is-invalid @enderror" required>
                            <option value="">Selecciona un paralelo</option>
                            @foreach($parallels as $parallel)
                                <option value="{{ $parallel->id }}" {{ old('parallel_id') == $parallel->id ? 'selected' : '' }}>
                                    {{ $parallel->course->name }} - {{ $parallel->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parallel_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label class="text-muted small text-uppercase font-weight-bold">Curso / Materia</label>
                        <select name="course_id" class="form-control form-control-lg rounded-pill @error('course_id') is-invalid @enderror" required>
                            <option value="">Selecciona una materia</option>
                            @foreach(\App\Models\Course::all() as $course)
                                <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                            @endforeach
                        </select>
                        @error('course_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="text-muted small text-uppercase font-weight-bold">Categoría del robot</label>
                    <select name="category" class="form-control form-control-lg rounded-pill @error('category') is-invalid @enderror" required>
                        <option value="">Selecciona una categoría</option>
                        @foreach($categories as $label => $table)
                            <option value="{{ $label }}" {{ old('category') == $label ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="text-muted small text-uppercase font-weight-bold">Descripción de la actividad</label>
                    <textarea name="description" rows="4" class="form-control form-control-lg rounded-xl @error('description') is-invalid @enderror" placeholder="Describe brevemente el objetivo de la actividad.">{{ old('description') }}</textarea>
                    @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="alert alert-info rounded-pill">
                    <i class="fas fa-info-circle mr-2"></i>
                    La actividad se registrará en estado <strong>pending</strong> y será actualizada cuando el robot YURA complete la evaluación.
                </div>
            </div>
            <div class="card-footer bg-white text-right">
                <button type="submit" class="btn btn-quechua px-5">
                    <i class="fas fa-paper-plane mr-1"></i> Lanzar actividad
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
