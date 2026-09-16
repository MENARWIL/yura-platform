@extends('layouts.app')

@section('title', 'Nueva asignación docente')

@section('content')
<div class="container py-4"><div class="card card-quechua border-0 shadow-sm">
    <div class="card-header card-header-quechua"><h3 class="mb-0">Nueva asignación docente</h3></div>
    <form action="{{ route('teaching-assignments.store') }}" method="POST">
        @csrf
        <div class="card-body bg-white">
            <div class="form-group"><label for="teacher_user_id">Profesor</label><select id="teacher_user_id" name="teacher_user_id" class="form-control @error('teacher_user_id') is-invalid @enderror" required><option value="">Seleccionar profesor</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" {{ old('teacher_user_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>@endforeach</select>@error('teacher_user_id')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
            <div class="form-group"><label for="subject_id">Asignatura</label><select id="subject_id" name="subject_id" class="form-control @error('subject_id') is-invalid @enderror" required><option value="">Seleccionar asignatura</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>@endforeach</select>@error('subject_id')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
            <div class="form-group"><label for="parallel_id">Curso y paralelo</label><select id="parallel_id" name="parallel_id" class="form-control @error('parallel_id') is-invalid @enderror" required><option value="">Seleccionar paralelo</option>@foreach($parallels as $parallel)<option value="{{ $parallel->id }}" {{ old('parallel_id') == $parallel->id ? 'selected' : '' }}>{{ $parallel->course->name }} - {{ $parallel->name }}</option>@endforeach</select>@error('parallel_id')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
        </div>
        <div class="card-footer bg-white text-right"><a href="{{ route('teaching-assignments.index') }}" class="btn btn-link">Cancelar</a><button class="btn btn-primary">Guardar asignación</button></div>
    </form>
</div></div>
@endsection