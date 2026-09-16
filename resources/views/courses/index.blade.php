@extends('layouts.app')

@section('title', 'Cursos')

@section('content')
<div class="container-fluid py-4">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Cursos</h3>
            <a href="{{ route('courses.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Crear curso</a>
        </div>
        <div class="card-body bg-white">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Curso</th><th>Paralelos</th><th class="text-right">Acciones</th></tr></thead>
                    <tbody>
                        @forelse($courses as $course)
                            <tr>
                                <td>{{ $course->name }}</td>
                                <td>{{ $course->parallels_count }}</td>
                                <td class="text-right">
                                    <a href="{{ route('courses.edit', $course) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <form action="{{ route('courses.destroy', $course) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este registro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No hay cursos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
