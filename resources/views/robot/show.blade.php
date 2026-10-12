@extends('layouts.app')

@section('title', 'Detalle de actividad Yura')

@section('content')
@php
    $latestAttempt = $activity->attempts->last();
    $latestResult = $latestAttempt?->result_payload ?? $activity->result_payload;
    $groupResults = collect(data_get($latestResult, 'group_results', []))->keyBy('group_id');
    $category = data_get($activity->payload, 'activity.content.category', '');
    $categoryLabel = str($category)->after('_')->replace('_', ' ')->title();
    $statusLabels = [
        'pending' => 'Pendiente',
        'in_progress' => 'En ejecución',
        'completed' => 'Completada',
        'partial' => 'Parcial',
        'failed' => 'Fallida',
        'cancelled' => 'Cancelada',
    ];
@endphp
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-robot mr-2"></i> Actividad de {{ $categoryLabel }}
            </h3>
            <a href="{{ route('robot-activities.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Volver al historial
            </a>
        </div>
        <div class="card-body bg-white">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-warning">{{ session('error') }}</div>
            @endif

            <div class="row mb-4">
                <div class="col-md-3"><strong>Estado:</strong> {{ $statusLabels[$activity->status] ?? $activity->status }}</div>
                <div class="col-md-3"><strong>Curso:</strong> {{ $activity->parallel->course->name }} - {{ $activity->parallel->name }}</div>
                <div class="col-md-3"><strong>Asignatura:</strong> {{ $activity->subject->name }}</div>
                <div class="col-md-3"><strong>Casilla:</strong> Trimestre {{ $activity->quarter }} · HACER {{ $activity->activity_number }}</div>
            </div>

            <h5>Configuración</h5>
            <dl class="row">
                <dt class="col-sm-3">Categoría</dt>
                <dd class="col-sm-9">{{ $categoryLabel }}</dd>
                <dt class="col-sm-3">Participantes</dt>
                <dd class="col-sm-9">{{ collect(data_get($activity->payload, 'activity.groups', []))->sum(fn ($group) => count($group['members'])) }}</dd>
                <dt class="col-sm-3">Preguntas por grupo</dt>
                <dd class="col-sm-9">
                    {{ data_get($activity->payload, 'activity.execution.respondents_per_group') }}
                    respondientes ×
                    {{ data_get($activity->payload, 'activity.execution.questions_per_respondent') }}
                    pregunta(s) por respondiente
                </dd>
                <dt class="col-sm-3">Creada</dt>
                <dd class="col-sm-9">{{ $activity->created_at->format('d/m/Y H:i') }}</dd>
                <dt class="col-sm-3">Iniciada</dt>
                <dd class="col-sm-9">{{ $activity->started_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                <dt class="col-sm-3">{{ $activity->status === 'cancelled' ? 'Cancelada' : 'Finalizada' }}</dt>
                <dd class="col-sm-9">{{ $activity->completed_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </dl>

            @if(in_array($activity->status, ['pending', 'in_progress'], true))
                <form method="POST" action="{{ route('robot-activities.cancel', $activity->id) }}" class="mb-4"
                      onsubmit="return confirm('¿Cancelar esta actividad? Yura recibirá la señal y se detendrá. No se guardarán notas parciales.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-times mr-1"></i> Cancelar actividad
                    </button>
                    @if($activity->status === 'in_progress')
                        <span class="text-muted small ml-2">La detención puede tardar hasta que el cerebro reciba la señal de cancelación.</span>
                    @endif
                </form>
            @endif

            @if($activity->status === 'failed')
                <form method="POST" action="{{ route('robot-activities.retry', $activity->id) }}" class="mb-4"
                      onsubmit="return confirm('¿Reintentar esta actividad con los mismos participantes y la misma casilla HACER?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-redo mr-1"></i> Reintentar actividad
                    </button>
                    <span class="text-muted small ml-2">Se conserva el historial y no se cambia la configuración.</span>
                </form>
            @endif

            <h5 class="mt-4">Intentos registrados</h5>
            @forelse($activity->attempts as $attempt)
                <div class="alert alert-{{ $attempt->status === 'failed' ? 'danger' : ($attempt->status === 'completed' ? 'success' : 'info') }}">
                    <strong>Intento {{ $attempt->attempt_number }} · {{ $statusLabels[$attempt->status] ?? $attempt->status }}</strong>
                    <span class="ml-2">{{ $attempt->started_at?->format('d/m/Y H:i') ?? '—' }}</span>
                    @if(data_get($attempt->result_payload, 'failure_reason'))
                        <div class="mt-1">{{ data_get($attempt->result_payload, 'failure_reason') }}</div>
                    @endif
                </div>
            @empty
                <p class="text-muted">Yura todavía no ha iniciado la actividad.</p>
            @endforelse

            <h5 class="mt-4">Grupos y resultados</h5>
            @foreach(data_get($activity->payload, 'activity.groups', []) as $group)
                @php
                    $result = $groupResults->get($group['group_id']);
                @endphp
                <div class="card mb-3">
                    <div class="card-header">
                        Grupo {{ $loop->iteration }}
                        <span class="small text-muted ml-2">
                            {{ count($group['members']) }} alumnos ·
                            {{ count($group['respondent_student_ids']) }} responderán
                        </span>
                        @if($result)
                            <span class="float-right font-weight-bold">{{ $result['score_percentage'] }}%</span>
                        @else
                            <span class="float-right text-muted">Sin resultado</span>
                        @endif
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach($group['members'] as $member)
                            @php
                                $isRespondent = in_array($member['student_id'], $group['respondent_student_ids'], true);
                                $hasResponded = $result && collect($result['responses'] ?? [])
                                    ->contains(fn ($response) => (int) $response['student_id'] === (int) $member['student_id']);
                            @endphp
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $member['display_name'] }}</span>
                                @if($isRespondent)
                                    <span class="badge badge-info">
                                        {{ $hasResponded ? 'Respondió' : 'Sorteado para responder' }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if($result)
                        <div class="card-body">
                            <p class="mb-2">
                                Puntaje del grupo: {{ $result['points_earned'] }} / {{ $result['points_possible'] }}
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Participante</th>
                                            <th>Pregunta</th>
                                            <th>Resultado</th>
                                            <th>Puntaje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($result['responses'] as $response)
                                            @php
                                                $respondent = collect($group['members'])->firstWhere('student_id', $response['student_id']);
                                            @endphp
                                            <tr>
                                                <td>{{ $respondent['display_name'] ?? 'Participante' }}</td>
                                                <td>{{ $response['question_id'] }}</td>
                                                <td>{{ $response['correct'] ? 'Correcta' : 'Incorrecta' }}</td>
                                                <td>{{ $response['points_earned'] }} / {{ $response['points_possible'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if($activity->status === 'failed' && data_get($latestResult, 'failure_reason'))
                <div class="alert alert-danger mt-3">
                    <strong>Motivo del fallo:</strong> {{ data_get($latestResult, 'failure_reason') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
