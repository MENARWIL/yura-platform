@extends('layouts.app')

@section('title', 'Familiares y Tutores')

@section('content')
<div class="animate-fade-in">
    <div class="row">
        <div class="col-md-5">
            <div class="card card-quechua border-0">
                <div class="card-header card-header-quechua">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-link mr-2"></i> Asignar familiar o tutor</h3>
                </div>
                <form action="{{ route('family-members.store') }}" method="POST">
                    @csrf
                    <div class="card-body bg-white p-4">
                        <div class="form-group mb-3">
                            <label>Estudiante</label>
                            <select name="student_id" class="form-control" required>
                                <option value="">Seleccione un estudiante</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                        {{ $student->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label>Usuario familiar</label>
                            <select name="user_id" class="form-control" required>
                                <option value="">Seleccione un responsable</option>
                                @foreach($responsables as $responsable)
                                    <option value="{{ $responsable->id }}" {{ old('user_id') == $responsable->id ? 'selected' : '' }}>
                                        {{ $responsable->name }} ({{ ucfirst($responsable->rol) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Tipo de relación</label>
                                    <select name="relation_type" class="form-control" required>
                                        <option value="padre" {{ old('relation_type') == 'padre' ? 'selected' : '' }}>Padre</option>
                                        <option value="madre" {{ old('relation_type') == 'madre' ? 'selected' : '' }}>Madre</option>
                                        <option value="tutor" {{ old('relation_type') == 'tutor' ? 'selected' : '' }}>Tutor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Estado</label>
                                    <select name="active" class="form-control">
                                        <option value="1" {{ old('active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('active') == '0' ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="is_primary" value="1" class="form-check-input" {{ old('is_primary') ? 'checked' : '' }}>
                                    <label class="form-check-label">Responsable principal</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="can_view" value="1" class="form-check-input" checked>
                                    <label class="form-check-label">Puede ver</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="can_edit" value="1" class="form-check-input">
                                    <label class="form-check-label">Puede editar</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="can_receive_reports" value="1" class="form-check-input" checked>
                                    <label class="form-check-label">Recibe reportes</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-right">
                        <button type="submit" class="btn btn-quechua px-4">
                            <i class="fas fa-save mr-1"></i> Guardar relación
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card card-quechua border-0">
                <div class="card-header card-header-quechua">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-users mr-2"></i> Relaciones activas</h3>
                </div>
                <div class="card-body bg-white p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Familiar</th>
                                    <th>Relación</th>
                                    <th>Permisos</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($familyMembers as $familyMember)
                                    <tr>
                                        <td>
                                            <span class="font-weight-bold">{{ $familyMember->student->nombre ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span>{{ $familyMember->user->name ?? 'N/A' }}</span><br>
                                            <small class="text-muted">{{ $familyMember->user->email ?? '' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-light border text-uppercase">{{ $familyMember->relation_type }}</span>
                                            @if($familyMember->is_primary)
                                                <span class="badge badge-success ml-1">Principal</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>
                                                @if($familyMember->can_view) Ver @endif
                                                @if($familyMember->can_edit) · Editar @endif
                                                @if($familyMember->can_receive_reports) · Reportes @endif
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill px-3 py-1 {{ $familyMember->active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $familyMember->active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <div class="btn-group">
                                                <form action="{{ route('family-members.destroy', $familyMember) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Quieres eliminar esta relación?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-users-slash fa-2x mb-2"></i><br>
                                            No hay familiares asignados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 border-top pt-4">
                        <h5 class="font-weight-bold mb-3"><i class="fas fa-pencil-alt mr-2"></i> Editar relaciones</h5>
                        @forelse($familyMembers as $familyMember)
                            <div class="border rounded p-3 mb-3 bg-light">
                                <div class="row align-items-center mb-2">
                                    <div class="col-md-6">
                                        <strong>{{ $familyMember->student->nombre ?? 'N/A' }}</strong><br>
                                        <small class="text-muted">{{ $familyMember->user->name ?? 'N/A' }}</small>
                                    </div>
                                    <div class="col-md-6 text-md-right">
                                        <span class="badge badge-light border text-uppercase">{{ $familyMember->relation_type }}</span>
                                        @if($familyMember->is_primary)
                                            <span class="badge badge-success">Principal</span>
                                        @endif
                                    </div>
                                </div>

                                <form action="{{ route('family-members.update', $familyMember) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="small mb-1">Tipo de relación</label>
                                            <select name="relation_type" class="form-control" required>
                                                <option value="padre" {{ $familyMember->relation_type == 'padre' ? 'selected' : '' }}>Padre</option>
                                                <option value="madre" {{ $familyMember->relation_type == 'madre' ? 'selected' : '' }}>Madre</option>
                                                <option value="tutor" {{ $familyMember->relation_type == 'tutor' ? 'selected' : '' }}>Tutor</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small mb-1">Estado</label>
                                            <select name="active" class="form-control">
                                                <option value="1" {{ $familyMember->active ? 'selected' : '' }}>Activo</option>
                                                <option value="0" {{ ! $familyMember->active ? 'selected' : '' }}>Inactivo</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small mb-1">Principal</label>
                                            <select name="is_primary" class="form-control">
                                                <option value="1" {{ $familyMember->is_primary ? 'selected' : '' }}>Sí</option>
                                                <option value="0" {{ ! $familyMember->is_primary ? 'selected' : '' }}>No</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" name="can_view" value="1" class="form-check-input" {{ $familyMember->can_view ? 'checked' : '' }}>
                                                <label class="form-check-label">Puede ver</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" name="can_edit" value="1" class="form-check-input" {{ $familyMember->can_edit ? 'checked' : '' }}>
                                                <label class="form-check-label">Puede editar</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" name="can_receive_reports" value="1" class="form-check-input" {{ $familyMember->can_receive_reports ? 'checked' : '' }}>
                                                <label class="form-check-label">Recibe reportes</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <span class="text-muted small">Edita los permisos y estado de esta relación.</span>
                                        <button type="submit" class="btn btn-quechua">
                                            <i class="fas fa-save mr-1"></i> Guardar cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No hay relaciones para editar.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
