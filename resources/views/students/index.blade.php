@extends('layouts.app')

@section('title', 'Gestión de Estudiantes')

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0">
        <div class="card-header card-header-quechua d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-users mr-2"></i> Listado de Estudiantes
            </h3>
            <div class="card-tools d-flex align-items-center">
                <div class="mr-3">
                    <a href="{{ route('students.export.pdf') }}" class="btn btn-outline-danger btn-sm px-3 rounded-pill mr-2 shadow-sm">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </a>
                    <a href="{{ route('students.export.excel') }}" class="btn btn-outline-success btn-sm px-3 rounded-pill shadow-sm">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </a>
                </div>
                @if(Auth::user()->isAdmin() || Auth::user()->isProfesor())
                    <a href="{{ route('students.create') }}" class="btn btn-quechua px-4 shadow-sm">
                        <i class="fas fa-plus-circle mr-2"></i> Registrar Estudiante
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="px-4">Estudiante</th>
                            <th>Nivel</th>
                            <th>Edad / Género</th>
                            <th>Profesor Asignado</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Promedio</th>
                            <th class="text-right px-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                        <tr>
                            <td class="px-4">
                                <div class="d-flex align-items-center">
                                    @if($student->foto_path)
                                        <img src="{{ asset('storage/' . $student->foto_path) }}" alt="Foto de {{ $student->nombre }}" class="rounded-circle mr-3 shadow-sm" style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-3 shadow-sm" style="width:40px;height:40px;">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <span class="font-weight-bold d-block">{{ $student->nombre }}</span>
                                        <small class="text-muted"><i class="fas fa-user-friends mr-1"></i> Responsable: {{ $student->padre->name ?? 'N/A' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-quechua-gold px-3 py-2 rounded-pill font-weight-bold">
                                    {{ strtoupper($student->nivel) }}
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="d-block text-dark font-weight-bold">{{ $student->edad }} años</span>
                                    <span class="text-muted">
                                        <i class="fas fa-{{ $student->genero == 'Masculino' ? 'mars text-primary' : 'venus text-danger' }} mr-1"></i>
                                        {{ $student->genero }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if($student->profesor)
                                    <div class="d-flex align-items-center">
                                        <div class="bg-info rounded-circle mr-2" style="width: 8px; height: 8px;"></div>
                                        <span class="small font-weight-bold">{{ $student->profesor->name }}</span>
                                    </div>
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1 small italic">Sin asignar</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill px-3 py-1 {{ $student->estado == 'activo' ? 'bg-success' : 'bg-secondary' }}" style="font-size: 0.7rem;">
                                    {{ strtoupper($student->estado) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-block text-center">
                                    <span class="font-weight-bold d-block {{ $student->promedio >= 70 ? 'text-success' : ($student->promedio >= 51 ? 'text-warning' : 'text-danger') }}">
                                        {{ $student->promedio }}%
                                    </span>
                                    <div class="progress progress-xxs mt-1" style="height: 4px; width: 60px; border-radius: 2px;">
                                        <div class="progress-bar {{ $student->promedio >= 70 ? 'bg-success' : ($student->promedio >= 51 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $student->promedio }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 text-right">
                                <div class="btn-group shadow-sm rounded-pill bg-light p-1">
                                    @if(Auth::user()->isAdmin() || (Auth::user()->isProfesor() && $student->profesor_id == Auth::id()))
                                        <a href="{{ route('students.edit', $student->id) }}" class="btn btn-white btn-sm border-0 rounded-circle mr-1" title="Editar">
                                            <i class="fas fa-edit text-primary"></i>
                                        </a>
                                        <form action="{{ route('students.destroy', $student->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este registro?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-white btn-sm border-0 rounded-circle" title="Eliminar">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="px-3 small text-muted italic">Solo lectura</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-user-friends fa-4x mb-3 opacity-25"></i>
                                    <h4 class="font-weight-light">No hay estudiantes registrados</h4>
                                    <p class="small">Comienza registrando un nuevo alumno en la plataforma.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($deletedStudents->isNotEmpty())
                <div class="mt-4 border-top pt-4">
                    <h5 class="font-weight-bold mb-3"><i class="fas fa-trash-alt mr-2"></i> Registros eliminados</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Padre / Responsable</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deletedStudents as $student)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($student->foto_path)
                                                    <img src="{{ asset('storage/' . $student->foto_path) }}" alt="Foto de {{ $student->nombre }}" class="rounded-circle mr-3 shadow-sm" style="width:32px;height:32px;object-fit:cover;">
                                                @else
                                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-3 shadow-sm" style="width:32px;height:32px;">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </div>
                                                @endif
                                                <span class="font-weight-bold">{{ $student->nombre }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $student->padre->name ?? 'N/A' }}</td>
                                        <td class="text-right">
                                            <div class="btn-group">
                                                @if(Auth::user()->isAdmin() || Auth::user()->isProfesor())
                                                    <form action="{{ route('students.restore', $student) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-success mr-2">
                                                            <i class="fas fa-undo mr-1"></i> Restaurar
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(Auth::user()->isAdmin())
                                                    <form action="{{ route('students.forceDestroy', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Deseas eliminar este estudiante definitivamente?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-skull-crossbones mr-1"></i> Eliminar definitivamente
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
