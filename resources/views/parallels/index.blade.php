@extends('layouts.app')

@section('title', 'Paralelos')

@section('content')
<div class="container py-4">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Paralelos</h3>
            <a href="{{ route('parallels.create') }}" class="btn btn-primary font-weight-bold shadow-sm">
                <i class="fas fa-plus-circle mr-1"></i> Crear paralelo
            </a>
        </div>
        <div class="card-body bg-white">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Paralelo</th>
                            <th>Cupo</th>
                            <th>Estudiantes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parallels as $parallel)
                            <tr>
                                <td>{{ $parallel->course->name }}</td>
                                <td>{{ $parallel->name }}</td>
                                <td>{{ $parallel->max_students }}</td>
                                <td>{{ $parallel->students_count }}</td>
                                <td>
                                    <a href="{{ route('parallels.edit', $parallel) }}" class="btn btn-sm btn-primary">Editar</a>
                                    <form action="{{ route('parallels.destroy', $parallel) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este registro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No hay paralelos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
