@extends('layouts.app')

@section('title', 'Asignaciones docentes')

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex justify-content-between align-items-center flex-wrap">
            <div><h3 class="mb-0"><i class="fas fa-user-tie mr-2"></i> Asignaciones docentes</h3></div>
            <a href="{{ route('teaching-assignments.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Nueva asignación</a>
        </div>
        <div class="card-body bg-white">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
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
            </table></div>
        </div>
    </div>
</div>
@endsection