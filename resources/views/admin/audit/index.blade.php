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
            <div class="card-body border-bottom bg-white px-4 pt-4 pb-0">
                <form action="{{ route('admin.audit.index') }}" method="GET" class="filter-bar" aria-label="Filtros de auditoría">
                    <div class="fb-field {{ $entity ? 'is-set' : '' }}">
                        <label for="audit-entity"><i class="fas fa-cube" aria-hidden="true"></i> Módulo</label>
                        <select id="audit-entity" name="entity">
                            <option value="">Todos los módulos</option>
                            @foreach($entities as $value => $label)
                                <option value="{{ $value }}" {{ mb_strtolower((string) $entity) === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fb-field {{ $action ? 'is-set' : '' }}">
                        <label for="audit-action"><i class="fas fa-bolt" aria-hidden="true"></i> Acción realizada</label>
                        <select id="audit-action" name="action">
                            <option value="">Todas las acciones</option>
                            @foreach($actions as $value => $label)
                                <option value="{{ $value }}" {{ $action === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fb-actions">
                        @if($entity || $action)
                            <a href="{{ route('admin.audit.index') }}" class="fb-btn fb-btn-ghost" title="Quitar los filtros">
                                <i class="fas fa-undo" aria-hidden="true"></i> Limpiar
                            </a>
                        @endif
                        <button type="submit" class="fb-btn fb-btn-primary">
                            <i class="fas fa-filter" aria-hidden="true"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 60px;" title="Posición en la lista, de lo más reciente a lo más antiguo">Nro</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Módulo</th>
                                <th>Detalle</th>
                                <th>Dirección IP</th>
                                <th class="text-right text-nowrap">Fecha y hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                @php
                                    $actionLabel = \App\Support\AuditPresenter::action($log->action);
                                    $danger = \App\Support\AuditPresenter::tone($log->action) === 'danger';
                                @endphp
                                <tr>
                                    <td class="font-weight-bold text-muted">{{ $logs->firstItem() + $loop->index }}</td>
                                    <td>
                                        @if($log->user)
                                            <strong>{{ $log->user->name }}</strong><br>
                                            <small class="text-muted">{{ \App\Support\AuditPresenter::role($log->user->role) }}</small>
                                        @else
                                            <span class="text-muted">Sistema</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge border px-2 py-1 font-weight-bold {{ $danger ? 'badge-danger-soft' : 'badge-light' }}" title="Código: {{ $log->action }}">{{ $actionLabel }}</span>
                                    </td>
                                    <td>{{ \App\Support\AuditPresenter::entity($log->entity) }}</td>
                                    <td style="min-width: 260px; max-width: 420px;">
                                        <span>{{ \App\Support\AuditPresenter::detail($log, $names) }}</span>
                                    </td>
                                    <td><small class="font-monospace text-muted">{{ $log->ip_address ?? '127.0.0.1' }}</small></td>
                                    <td class="text-right text-nowrap">
                                        <span class="d-block font-weight-bold">{{ $log->created_at->format('d/m/Y') }}</span>
                                        <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        @if($entity || $action)
                                            Ningún registro coincide con los filtros elegidos.
                                        @else
                                            Aún no hay registros de auditoría almacenados.
                                        @endif
                                    </td>
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
