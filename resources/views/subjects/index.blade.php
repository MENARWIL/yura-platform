@extends('layouts.app')

@section('title', 'Asignaturas')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="font-weight-bold mb-1">Asignaturas</h3>
            <p class="text-muted mb-0">Catálogo de materias del colegio.</p>
        </div>
        <a href="{{ route('subjects.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Nueva asignatura</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light"><tr><th>Asignatura</th><th>Código</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                <tbody>
                    @forelse($subjects as $subject)
                        <tr>
                            <td>{{ $subject->name }}</td>
                            <td>{{ $subject->code ?? 'Sin código' }}</td>
                            <td><span class="badge {{ $subject->active ? 'badge-success' : 'badge-secondary' }}">{{ $subject->active ? 'Activa' : 'Inactiva' }}</span></td>
                            <td class="text-right"><a href="{{ route('subjects.edit', $subject) }}" class="btn btn-sm btn-outline-primary" title="Editar asignatura"><i class="fas fa-edit"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No hay asignaturas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection