@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Crear paralelo</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('parallels.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Curso</label>
                            <select name="course_id" id="course_id" class="form-control" required>
                                <option value="">Seleccione un curso</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                                @endforeach
                            </select>
                            @error('course_id')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre del paralelo</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                            @error('name')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="max_students" class="form-label">Capacidad máxima</label>
                            <input type="number" name="max_students" id="max_students" class="form-control" value="{{ old('max_students', 30) }}" min="1" required>
                            @error('max_students')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <button type="submit" class="btn btn-success">Guardar paralelo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
