@extends('layouts.app')

@section('title', 'Asignaciones docentes')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h3 class="font-weight-bold mb-1">Asignaciones docentes</h3><p class="text-muted mb-0">Define quién registra cada asignatura en cada paralelo.</p></div>
        <a href="{{ route('teaching-assignments.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Nueva asignación</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead class="thead-light"><tr><th>Curso y paralelo</th><th>Asignatura</th><th>Profesor</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
            @forelse($assignments as $assignment)
                <tr>
                    <td>{{ $assignment->parallel->course->name }} - {{ $assignment->parallel->name }}</td>
                    <td>{{ $assignment->subject->name }}</td>
                    <td>{{ $assignment->teacher->name }}</td>
                    <td><span class="badge {{ $assignment->active ? 'badge-success' : 'badge-secondary' }}">{{ $assignment->active ? 'Activa' : 'Inactiva' }}</span></td>
                    <td class="text-right">
                        <form action="{{ route('teaching-assignments.update', $assignment) }}" method="POST" class="d-inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="active" value="{{ $assignment->active ? 0 : 1 }}">
                            <button class="btn btn-sm btn-outline-primary" title="{{ $assignment->active ? 'Desactivar' : 'Activar' }}"><i class="fas {{ $assignment->active ? 'fa-pause' : 'fa-play' }}"></i></button>
                        </form>
                        <form action="{{ route('teaching-assignments.destroy', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta asignación?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No hay asignaciones docentes.</td></tr>
            @endforelse
        </tbody>
    </table></div></div>
</div>
@endsection