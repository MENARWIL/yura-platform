@extends('layouts.app')

@section('title', 'Editar asignatura')

@section('content')
<div class="container py-4"><div class="card card-quechua border-0 shadow-sm">
    <div class="card-header card-header-quechua"><h3 class="mb-0">Editar asignatura</h3></div>
    <form action="{{ route('subjects.update', $subject) }}" method="POST">
        @csrf @method('PUT')
        <div class="card-body bg-white">
            <div class="form-group"><label for="name">Nombre</label><input id="name" name="name" value="{{ old('name', $subject->name) }}" class="form-control @error('name') is-invalid @enderror" required>@error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
            <div class="form-row"><div class="form-group col-md-6"><label for="code">Código</label><input id="code" name="code" value="{{ old('code', $subject->code) }}" class="form-control @error('code') is-invalid @enderror" maxlength="30">@error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror</div><div class="form-group col-md-6"><label for="sort_order">Posición de presentación</label><input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $subject->sort_order) }}" class="form-control" min="0" step="1" inputmode="numeric"><small class="form-text text-muted">Define el orden en listas y selectores.</small></div></div>
            <div class="form-check"><input id="active" type="checkbox" name="active" value="1" class="form-check-input" {{ old('active', $subject->active) ? 'checked' : '' }}><label for="active" class="form-check-label">Asignatura activa</label></div>
        </div>
        <div class="card-footer bg-white text-right"><a href="{{ route('subjects.index') }}" class="btn btn-link">Cancelar</a><button class="btn btn-primary">Guardar cambios</button></div>
    </form>
</div></div>
@endsection