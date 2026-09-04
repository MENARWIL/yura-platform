@extends('layouts.app')

@section('title', 'Editar curso')

@section('content')
<div class="container py-4">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua"><h3 class="mb-0">Editar curso</h3></div>
        <form action="{{ route('courses.update', $course) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body bg-white">
                <label for="name">Nombre del curso</label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $course->name) }}" required maxlength="255">
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="card-footer bg-white text-right"><a href="{{ route('courses.index') }}" class="btn btn-link">Cancelar</a><button class="btn btn-primary">Guardar cambios</button></div>
        </form>
    </div>
</div>
@endsection
