@extends('layouts.app')

@section('title', 'Portal del Tutor')

@section('content')
<div class="animate-fade-in">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card card-quechua border-0">
                <div class="card-header card-header-quechua d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0 font-weight-bold"><i class="fas fa-user-check mr-2"></i> Portal del Tutor</h3>
                </div>
                <div class="card-body bg-white p-4">
                    <p class="text-muted">Bienvenido al portal de seguimiento académico. Aquí puedes revisar asistencia, notas del profesor y el estado de las actividades de YURA de tu hijo.</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-quechua border-0">
                <div class="card-header card-header-quechua">
                    <h4 class="card-title mb-0 font-weight-bold"><i class="fas fa-calendar-check mr-2"></i> Asistencia diaria</h4>
                </div>
                <div class="card-body bg-white p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Estudiante</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendance as $row)
                                <tr>
                                    <td>{{ $row->date->format('d/m/Y') }}</td>
                                    <td>{{ $row->student->nombre }}</td>
                                    <td>{{ ucfirst($row->status) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No hay registros de asistencia.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-quechua border-0">
                <div class="card-header card-header-quechua">
                    <h4 class="card-title mb-0 font-weight-bold"><i class="fas fa-clipboard-list mr-2"></i> Notas manuales del profesor</h4>
                </div>
                <div class="card-body bg-white p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Materia</th>
                                    <th>Nota</th>
                                    <th>Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grades as $grade)
                                <tr>
                                    <td>{{ $grade->student->nombre }}</td>
                                    <td>{{ $grade->subject->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($grade->score, 2) }}%</td>
                                    <td>{{ $grade->type }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No hay calificaciones registradas.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card card-quechua border-0">
                <div class="card-header card-header-quechua">
                    <h4 class="card-title mb-0 font-weight-bold"><i class="fas fa-robot mr-2"></i> Actividades completadas con Robot YURA</h4>
                </div>
                <div class="card-body bg-white p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Categoría</th>
                                    <th>Nota YURA</th>
                                    <th>Estado</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($robotActivities as $activity)
                                <tr>
                                    <td>{{ $activity->student->nombre }}</td>
                                    <td>{{ $activity->activity_category }}</td>
                                    <td>
                                        @if($activity->status === 'completed')
                                            {{ number_format($activity->score, 2) }}%
                                        @else
                                            Pendiente
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($activity->status) }}</td>
                                    <td>{{ $activity->observations }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No hay actividades de Robot YURA.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
