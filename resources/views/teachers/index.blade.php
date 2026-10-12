@extends('layouts.app')

@section('title', 'Profesores')

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="mb-0"><i class="fas fa-chalkboard-teacher mr-2"></i> Profesores</h3>
            <a href="{{ route('teachers.create') }}" class="btn btn-primary">
                <i class="fas fa-user-plus mr-1"></i> Nuevo profesor
            </a>
        </div>
        <div class="card-body bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Correo de acceso</th>
                            <th>Teléfono</th>
                            <th>Asignatura principal</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                <td>{{ $teacher->name }}</td>
                                <td>{{ $teacher->email }}</td>
                                <td>{{ $teacher->phone ?? 'Sin teléfono' }}</td>
                                <td>{{ $teacher->mainSubject?->name ?? 'Ninguna' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-sm btn-outline-primary" title="Editar profesor">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay profesores registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection