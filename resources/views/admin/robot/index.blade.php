@extends('layouts.app')

@section('title', 'Robot YURA')

@section('content')
@php($canManage = auth()->user()->canManageRobot())
<div class="row">
    <div class="col-12">

        @if(session('robot_key'))
        <div class="alert alert-warning shadow-sm border-0" role="alert">
            <h6 class="font-weight-bold mb-2"><i class="fas fa-key mr-2" aria-hidden="true"></i>Llave de «{{ session('robot_key_name') }}»</h6>
            <p class="small mb-2">Cópiala ahora y pégala en <code>YURA_LARAVEL_TOKEN</code> del archivo <code>.env.local</code> del robot.
                <strong>No se vuelve a mostrar</strong>: en la base solo queda su huella y nadie puede leerla después.</p>
            <div class="input-group">
                <input type="text" id="robotKey" class="form-control font-monospace" value="{{ session('robot_key') }}" readonly onfocus="this.select()">
                <div class="input-group-append">
                    <button type="button" class="btn btn-dark" id="copyRobotKey"><i class="fas fa-copy mr-1" aria-hidden="true"></i>Copiar</button>
                </div>
            </div>
        </div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h4 class="font-weight-bold mb-1 text-dark"><i class="fas fa-robot mr-2 text-primary"></i>Robot YURA</h4>
                <p class="text-muted small mb-0">
                    El robot no es un usuario: no tiene correo ni contraseña y no puede entrar a la web ni a la app.
                    Se conecta con una <strong>llave</strong> que solo permite <strong>recibir actividades</strong> y <strong>enviar notas</strong>
                    de su materia. Si se pierde o roban el equipo, revoca la llave y deja de funcionar al instante.
                </p>
            </div>

            <div class="card-body px-4">
                @if($devices->isEmpty())
                    <p class="text-muted mb-0">Todavía no hay ningún robot registrado.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Robot</th>
                                <th>Materia</th>
                                <th>Estado</th>
                                <th>Último uso</th>
                                <th>Llave</th>
                                @if($canManage)<th class="text-right">Acciones</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($devices as $device)
                            <tr>
                                <td>
                                    <span class="font-weight-bold">{{ $device->name }}</span>
                                    @if($device->teacher)<br><span class="text-muted small">Docente: {{ $device->teacher->name }}</span>@endif
                                </td>
                                <td>{{ $device->subject?->name }}</td>
                                <td>
                                    @if($device->isActive())
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-secondary">Revocado {{ $device->revoked_at?->format('d/m/Y H:i') }}</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($device->last_used_at)
                                        {{ $device->last_used_at->format('d/m/Y H:i') }}<br><span class="text-muted">IP {{ $device->last_used_ip }}</span>
                                    @else
                                        <span class="text-muted">Nunca</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($device->isActive() && $device->key_hint)
                                        <code>yura_rbt_…{{ $device->key_hint }}</code><br>
                                        <span class="text-muted">creada {{ $device->key_created_at?->format('d/m/Y H:i') }}@if($device->creator) por {{ $device->creator->name }}@endif</span>
                                    @else
                                        <span class="text-muted">Sin llave</span>
                                    @endif
                                </td>
                                @if($canManage)
                                <td class="text-right text-nowrap">
                                    <form method="POST" action="{{ route('admin.robot.rotate', $device) }}" class="d-inline"
                                          data-confirm="Se generará una llave nueva y la actual dejará de funcionar: habrá que pegar la nueva en el robot. ¿Continuar?">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-sync-alt mr-1" aria-hidden="true"></i>{{ $device->isActive() ? 'Renovar' : 'Generar llave' }}</button>
                                    </form>
                                    @if($device->isActive())
                                    <form method="POST" action="{{ route('admin.robot.revoke', $device) }}" class="d-inline"
                                          data-confirm="El robot «{{ $device->name }}» dejará de conectarse al instante. ¿Revocar la llave?">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-ban mr-1" aria-hidden="true"></i>Revocar</button>
                                    </form>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        @if($canManage)
        <div class="card shadow-sm border-0">
            <div class="card-body px-4">
                <h6 class="font-weight-bold">Registrar un robot</h6>
                <form method="POST" action="{{ route('admin.robot.store') }}">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label class="small text-muted text-uppercase font-weight-bold" for="robot_name">Nombre</label>
                            <input type="text" id="robot_name" name="name" value="{{ old('name') }}" maxlength="120"
                                   placeholder="YURA – Raspberry aula Quechua" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-muted text-uppercase font-weight-bold" for="robot_subject">Materia</label>
                            <select id="robot_subject" name="subject_id" class="form-control @error('subject_id') is-invalid @enderror" required>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" @selected(old('subject_id', $subject->code === 'QUE' ? $subject->id : null) == $subject->id)>{{ $subject->name }}</option>
                                @endforeach
                            </select>
                            @error('subject_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small text-muted text-uppercase font-weight-bold" for="robot_teacher">Docente responsable</label>
                            <select id="robot_teacher" name="teacher_user_id" class="form-control @error('teacher_user_id') is-invalid @enderror">
                                <option value="">Sin indicar</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected(old('teacher_user_id') == $teacher->id)>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                            @error('teacher_user_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <button class="btn btn-primary"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Registrar y generar llave</button>
                </form>
            </div>
        </div>
        @else
        <p class="text-muted small"><i class="fas fa-eye mr-1" aria-hidden="true"></i> Solo consulta: registrar, renovar o revocar la llave lo hacen el administrador o el director.</p>
        @endif
    </div>
</div>

@if(session('robot_key'))
<script>
document.getElementById('copyRobotKey').addEventListener('click', function () {
    var input = document.getElementById('robotKey');
    input.select();
    if (navigator.clipboard) { navigator.clipboard.writeText(input.value); } else { document.execCommand('copy'); }
    this.innerHTML = '<i class="fas fa-check mr-1" aria-hidden="true"></i>Copiada';
});
</script>
@endif
@endsection
