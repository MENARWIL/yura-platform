@extends('layouts.app')

@section('title', 'Crear paralelo')

@section('content')
<div class="container py-4">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua"><h3 class="mb-0">Crear paralelo</h3></div>
        <form action="{{ route('parallels.store') }}" method="POST">
            @csrf
            <div class="card-body bg-white">
                <div class="form-group">
                    <label for="course_id">Curso</label>
                    <select name="course_id" id="course_id" class="form-control @error('course_id') is-invalid @enderror" required>
                        <option value="">Seleccione un curso</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                    </select>
                    @error('course_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="name">Nombre del paralelo</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group mb-0">
                    <label for="max_students">Capacidad máxima</label>
                    <input type="number" name="max_students" id="max_students" class="form-control @error('max_students') is-invalid @enderror" value="{{ old('max_students', 30) }}" min="1" required>
                    @error('max_students')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="card-footer bg-white text-right"><a href="{{ route('parallels.index') }}" class="btn btn-link">Cancelar</a><button type="submit" class="btn btn-primary">Guardar paralelo</button></div>
        </form>
    </div>
</div>
@endsection
