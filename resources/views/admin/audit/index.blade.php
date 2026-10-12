@extends('layouts.app')

@section('title', 'Bitácora de Auditoría del Sistema')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="font-weight-bold mb-1 text-dark"><i class="fas fa-shield-alt mr-2 text-primary"></i>Bitácora de Auditoría Inmutable</h4>
                    <p class="text-muted small mb-0">Registro de eventos críticos, cambios de estado administrativo e intervenciones en la plataforma.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card-body border-bottom bg-light px-4 py-3">
                <form action="{{ route('admin.audit.index') }}" method="GET" class="form-row align-items-end">
                    <div class="col-md-5 mb-2">
                        <label class="small font-weight-bold text-muted">Módulo / Entidad</label>
                        <select name="entity" class="form-control form-control-sm">
                            <option value="">-- Todas las entidades --</option>
                            @foreach($entities as $e)
                                <option value="{{ $e }}" {{ $entity == $e ? 'selected' : '' }}>{{ $e }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5 mb-2">
                        <label class="small font-weight-bold text-muted">Acción realizada</label>
                        <select name="action" class="form-control form-control-sm">
                            <option value="">-- Todas las acciones --</option>
                            @foreach($actions as $a)
                                <option value="{{ $a }}" {{ $action == $a ? 'selected' : '' }}>{{ $a }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter mr-1"></i> Filtrar</button>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Entidad</th>
                                <th>ID Registro</th>
                                <th>Dirección IP</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td><small class="text-muted">{{ $log->created_at->format('d/m/Y H:i:s') }}</small></td>
                                    <td>
                                        @if($log->user)
                                            <strong>{{ $log->user->name }}</strong><br>
                                            <small class="text-muted">{{ $log->user->role }}</small>
                                        @else
                                            <span class="text-muted">Sistema / Anónimo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-light border px-2 py-1 font-weight-bold">{{ $log->action }}</span>
                                    </td>
                                    <td><code>{{ $log->entity }}</code></td>
                                    <td>{{ $log->entity_id ?? '-' }}</td>
                                    <td><small class="font-monospace text-muted">{{ $log->ip_address ?? '127.0.0.1' }}</small></td>
                                    <td>
                                        @if($log->details)
                                            <pre class="bg-light p-1 rounded small mb-0" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ json_encode($log->details) }}</pre>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Aún no hay registros de auditoría almacenados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white border-0 px-4 py-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
