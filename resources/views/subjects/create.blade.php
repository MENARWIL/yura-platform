@extends('layouts.app')

@section('title', 'Nueva asignatura')

@section('content')
<div class="container py-4"><div class="card card-quechua border-0 shadow-sm">
    <div class="card-header card-header-quechua"><h3 class="mb-0">Nueva asignatura</h3></div>
    <form action="{{ route('subjects.store') }}" method="POST">
        @csrf
        <div class="card-body bg-white">
            <div class="form-group"><label for="name">Nombre</label><input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>@error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
            <div class="form-row"><div class="form-group col-md-6"><label for="code">Código</label><input id="code" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" maxlength="30">@error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror</div><div class="form-group col-md-6"><label for="sort_order">Posición de presentación</label><input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control" min="0" step="1" inputmode="numeric"><small class="form-text text-muted">Define el orden en listas y selectores.</small></div></div>
        </div>
        <div class="card-footer bg-white text-right"><a href="{{ route('subjects.index') }}" class="btn btn-link">Cancelar</a><button class="btn btn-primary">Guardar asignatura</button></div>
    </form>
</div></div>
@endsection