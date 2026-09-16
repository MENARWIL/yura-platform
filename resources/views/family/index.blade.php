@extends('layouts.app')

@section('title', 'Gestión Académica')

@section('content')
<div class="animate-fade-in">
    @if(!in_array(auth()->user()->role, ['admin', 'academic']))
        <div class="alert alert-info mb-4" role="alert">
            <i class="fas fa-info-circle mr-2"></i>
            Modo solo lectura: no puedes modificar las relaciones familiares.
        </div>
    @endif

    <div class="card card-quechua border-0">
        <div class="card-header card-header-quechua">
            <h3 class="card-title font-weight-bold mb-0">
                <i class="fas fa-users mr-2"></i> Relaciones activas
            </h3>
        </div>

        <div class="card-body bg-white p-4">
            <form action="{{ route('family-members.index') }}" method="GET" class="mb-4">
                <div class="form-row align-items-end">
                    <div class="col-md-4 form-group mb-md-0">
                        <label for="student_name">Nombre del estudiante</label>
                        <input type="search" id="student_name" name="student_name" class="form-control" value="{{ request('student_name') }}" placeholder="Buscar estudiante">
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <label for="course_id">Curso</label>
                        <select id="course_id" name="course_id" class="form-control">
                            <option value="">Todos los cursos</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <label for="parallel_id">Paralelo</label>
                        <select id="parallel_id" name="parallel_id" class="form-control">
                            <option value="">Todos los paralelos</option>
                            @foreach($parallels as $parallel)
                                <option value="{{ $parallel->id }}" {{ request('parallel_id') == $parallel->id ? 'selected' : '' }}>{{ $parallel->course?->name }} - {{ $parallel->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group mb-md-0">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Buscar</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Estado estudiante</th>
                            <th>Tutor principal</th>
                            <th>Estado tutor</th>
                            <th>Relación</th>
                            @if(in_array(auth()->user()->role, ['admin', 'academic']))
                                <th class="text-right">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($familyMembers as $familyMember)
                            <tr>
                                <td class="font-weight-bold">{{ $familyMember->student->name ?? 'N/A' }}</td>
                                <td>
                                    @php($studentActive = ($familyMember->student->status ?? $familyMember->student->estado ?? 'activo') === 'activo' || ($familyMember->student->status ?? $familyMember->student->estado ?? 'activo') === 'active')
                                    <span class="badge {{ $studentActive ? 'badge-success' : 'badge-danger' }}">
                                        {{ $studentActive ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td>{{ $familyMember->user->name ?? 'N/A' }}</td>
                                <td>
                                    @php($tutorActive = in_array($familyMember->user->status, ['active', 'activo'], true))
                                    <span class="badge {{ $tutorActive ? 'badge-success' : 'badge-danger' }}">
                                        {{ $tutorActive ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td><span class="badge badge-light border text-uppercase">{{ $familyMember->relation_type }}</span></td>
                                @if(in_array(auth()->user()->role, ['admin', 'academic']))
                                    <td class="text-right">
                                        <a href="{{ route('students.edit', $familyMember->student) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit mr-1"></i> Editar</a>
                                        <a href="{{ route('tutors.edit', $familyMember->user) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-user-edit mr-1"></i> Tutor</a>
                                        <form action="{{ route('family-members.destroy', $familyMember) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Quieres eliminar esta relación?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash mr-1"></i> Eliminar</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ in_array(auth()->user()->role, ['admin', 'academic']) ? '6' : '5' }}" class="text-center text-muted py-5">
                                    <i class="fas fa-users-slash fa-2x mb-2"></i><br>
                                    No hay relaciones activas para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(in_array(auth()->user()->role, ['admin', 'academic']))
                @foreach($familyMembers as $familyMember)
                    <div class="modal fade" id="editFamilyMember{{ $familyMember->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Cambiar tutor principal</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <form action="{{ route('family-members.update', $familyMember) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <p class="mb-3"><strong>Estudiante:</strong> {{ $familyMember->student->name ?? 'N/A' }}</p>
                                        <div class="form-group">
                                            <label for="user_id_{{ $familyMember->id }}">Tutor principal</label>
                                            <select id="user_id_{{ $familyMember->id }}" name="user_id" class="form-control" required>
                                                @foreach($responsables as $responsable)
                                                    <option value="{{ $responsable->id }}" {{ $familyMember->user_id == $responsable->id ? 'selected' : '' }}>{{ $responsable->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="relation_type_{{ $familyMember->id }}">Relación</label>
                                            <select id="relation_type_{{ $familyMember->id }}" name="relation_type" class="form-control" required>
                                                <option value="padre" {{ $familyMember->relation_type === 'padre' ? 'selected' : '' }}>Padre</option>
                                                <option value="madre" {{ $familyMember->relation_type === 'madre' ? 'selected' : '' }}>Madre</option>
                                                <option value="tutor" {{ $familyMember->relation_type === 'tutor' ? 'selected' : '' }}>Tutor</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link" data-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
