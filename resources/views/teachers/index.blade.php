@extends('layouts.app')

@section('title', 'Profesores')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="font-weight-bold mb-1">Profesores</h3>
            <p class="text-muted mb-0">Administra las cuentas de acceso y asignaciones docentes.</p>
        </div>
        <a href="{{ route('teachers.create') }}" class="btn btn-quechua">
            <i class="fas fa-user-plus mr-1"></i> Nuevo profesor
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
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
@endsection