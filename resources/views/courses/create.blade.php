@extends('layouts.app')

@section('title', 'Crear curso')

@section('content')
<div class="container py-4">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua"><h3 class="mb-0">Crear curso</h3></div>
        <form action="{{ route('courses.store') }}" method="POST">
            @csrf
            <div class="card-body bg-white">
                <div class="form-group">
                    <label for="name">Nombre del curso</label>
                    <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255">
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group mb-0">
                    <label for="nivel_educativo">Nivel educativo</label>
                    <select id="nivel_educativo" name="nivel_educativo" class="form-control @error('nivel_educativo') is-invalid @enderror" required>
                        <option value="">Seleccione un nivel...</option>
                        @foreach(\App\Models\Course::NIVELES as $value => $label)
                            <option value="{{ $value }}" {{ old('nivel_educativo') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('nivel_educativo')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="card-footer bg-white text-right"><a href="{{ route('courses.index') }}" class="btn btn-link">Cancelar</a><button class="btn btn-primary">Guardar curso</button></div>
        </form>
    </div>
</div>
@endsection
