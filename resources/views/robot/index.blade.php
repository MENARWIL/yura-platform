@extends('layouts.app')

@section('title', 'Actividades Yura')

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-robot mr-2"></i> Actividades Yura
            </h3>
            <a href="{{ route('robot-activities.create') }}" class="btn btn-light btn-sm">
                <i class="fas fa-plus mr-1"></i> Crear actividad
            </a>
        </div>
        <div class="card-body bg-white">
            <p class="text-muted">Crea actividades de práctica y consulta su estado e historial de ejecución.</p>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('robot-activities.index') }}" class="filter-bar" aria-label="Filtros de actividades">
                <div class="fb-field fb-fixed {{ $status ? 'is-set' : '' }}">
                    <label for="activityStatus"><i class="fas fa-tasks" aria-hidden="true"></i> Estado</label>
                    <select id="activityStatus" name="status">
                        <option value="">Todos los estados</option>
                        @foreach($allowedStatuses as $activityStatus)
                            <option value="{{ $activityStatus }}" {{ $status === $activityStatus ? 'selected' : '' }}>
                                {{ [
                                    'pending' => 'Pendiente',
                                    'in_progress' => 'En ejecución',
                                    'completed' => 'Completada',
                                    'partial' => 'Parcial',
                                    'failed' => 'Fallida',
                                    'cancelled' => 'Cancelada',
                                ][$activityStatus] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-field fb-narrow">
                    <label for="activityPerPage"><i class="fas fa-list-ol" aria-hidden="true"></i> Mostrar</label>
                    <select id="activityPerPage" name="per_page" onchange="this.form.submit()">
                        @foreach([5, 10, 15, 20] as $size)
                            <option value="{{ $size }}" {{ $activities->perPage() === $size ? 'selected' : '' }}>{{ $size }} por página</option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-actions" style="margin-left: 0;">
                    <button type="submit" class="fb-btn fb-btn-primary">
                        <i class="fas fa-filter" aria-hidden="true"></i> Filtrar
                    </button>
                    @if($status)
                        <a href="{{ route('robot-activities.index') }}" class="fb-btn fb-btn-ghost" title="Quitar el filtro">
                            <i class="fas fa-undo" aria-hidden="true"></i> Limpiar
                        </a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Actividad</th>
                            <th>Curso y paralelo</th>
                            <th>Asignatura</th>
                            <th>Evaluación</th>
                            <th>Estado</th>
                            <th>Creada</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            @php
                                $statusLabels = [
                                    'pending' => ['Pendiente', 'secondary'],
                                    'in_progress' => ['En ejecución', 'primary'],
                                    'completed' => ['Completada', 'success'],
                                    'partial' => ['Parcial', 'warning'],
                                    'failed' => ['Fallida', 'danger'],
                                    'cancelled' => ['Cancelada', 'dark'],
                                ];
                                [$statusLabel, $statusColor] = $statusLabels[$activity->status] ?? [$activity->status, 'secondary'];
                                $category = data_get($activity->payload, 'activity.content.category', '');
                                $categoryLabel = str($category)->after('_')->replace('_', ' ')->title();
                            @endphp
                            <tr>
                                <td>
                                    <strong>Actividad de {{ $categoryLabel }}</strong>
                                    <div class="small text-muted">{{ $category }}</div>
                                </td>
                                <td>{{ $activity->parallel->course->name }} - {{ $activity->parallel->name }}</td>
                                <td>{{ $activity->subject->name }}</td>
                                <td>Trimestre {{ $activity->quarter }} · HACER {{ $activity->activity_number }}</td>
                                <td>
                                    <span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span>
                                    <div class="small text-muted">
                                        {{ $activity->attempts_count }} {{ $activity->attempts_count === 1 ? 'intento' : 'intentos' }}
                                    </div>
                                </td>
                                <td>{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('robot-activities.show', $activity->id) }}" class="btn btn-sm btn-outline-primary">
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    Todavía no hay actividades. Crea la primera para enviarla a Yura.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($activities->total() > 0)
                <div class="list-footer">
                    <span class="list-count">Mostrando {{ $activities->firstItem() }}–{{ $activities->lastItem() }} de {{ $activities->total() }} actividades</span>
                    {{ $activities->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
