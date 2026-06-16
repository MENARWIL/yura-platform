@extends('layouts.app')

@section('title', 'Panel de Control')

@section('content')
<div class="animate-fade-in">
    @if(Auth::user()->isAdmin())
        <!-- DASHBOARD ADMINISTRADOR -->
        <div class="row">
            <div class="col-lg-4 col-12">
                <div class="small-box card-quechua p-3 text-center mb-4 bg-white">
                    <div class="inner">
                        <h3 class="text-primary display-4 mb-0">{{ $data['total_estudiantes'] }}</h3>
                        <p class="text-muted font-weight-bold">Estudiantes Totales</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-graduate opacity-25" style="font-size: 5rem;"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="small-box card-quechua p-3 text-center mb-4 bg-white">
                    <div class="inner">
                        <h3 class="text-danger display-4 mb-0">{{ $data['total_profesores'] }}</h3>
                        <p class="text-muted font-weight-bold">Profesores Activos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chalkboard-teacher opacity-25" style="font-size: 5rem;"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="small-box card-quechua p-3 text-center mb-4 bg-white">
                    <div class="inner">
                        <h3 class="text-warning display-4 mb-0">{{ $data['total_padres'] }}</h3>
                        <p class="text-muted font-weight-bold">Responsables</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users opacity-25" style="font-size: 5rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card card-quechua border-0">
                    <div class="card-header card-header-quechua">
                        <h3 class="card-title font-weight-bold"><i class="fas fa-history mr-2"></i> Estudiantes Registrados Recientemente</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="px-4">Nombre</th>
                                        <th>Nivel</th>
                                        <th>Responsable</th>
                                        <th class="text-center">Promedio</th>
                                        <th class="text-right px-4">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['recientes'] as $est)
                                    <tr>
                                        <td class="px-4 font-weight-bold">{{ $est->nombre }}</td>
                                        <td><span class="badge badge-quechua-gold px-3">{{ strtoupper($est->nivel) }}</span></td>
                                        <td class="text-muted small">{{ $est->padre->name ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $est->promedio >= 70 ? 'bg-success' : 'bg-warning' }} rounded-pill px-3">
                                                {{ $est->promedio }}%
                                            </span>
                                        </td>
                                        <td class="text-right text-muted px-4 small">{{ $est->created_at->diffForHumans() }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @elseif(Auth::user()->isProfesor())
        <!-- DASHBOARD PROFESOR -->
        <div class="row">
            <div class="col-md-4">
                <div class="small-box card-quechua p-4 mb-4 bg-white">
                    <div class="inner">
                        <p class="text-muted mb-1 font-weight-bold">Mis Estudiantes</p>
                        <h3 class="display-4 text-info">{{ $data['mis_estudiantes_count'] }}</h3>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users text-info opacity-25"></i>
                    </div>
                </div>

                <div class="card card-quechua mt-4">
                    <div class="card-header card-header-quechua">
                        <h3 class="card-title font-weight-bold"><i class="fas fa-chart-pie mr-2"></i> Distribución por Niveles</h3>
                    </div>
                    <div class="card-body bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Básico</span>
                            <span class="badge bg-primary rounded-pill px-3">{{ $data['distribucion']['basico'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Intermedio</span>
                            <span class="badge bg-warning rounded-pill px-3">{{ $data['distribucion']['intermedio'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Avanzado</span>
                            <span class="badge bg-success rounded-pill px-3">{{ $data['distribucion']['avanzado'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-quechua h-100">
                    <div class="card-header card-header-quechua">
                        <h3 class="card-title font-weight-bold"><i class="fas fa-trophy mr-2"></i> Ranking de Notas (Top 10)</h3>
                    </div>
                    <div class="card-body p-0 bg-white">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="px-4">Estudiante</th>
                                        <th class="text-center">Promedio</th>
                                        <th>Progreso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['ranking'] as $est)
                                    <tr>
                                        <td class="px-4 font-weight-bold">{{ $est->nombre }}</td>
                                        <td class="text-center">
                                            <span class="font-weight-bold text-{{ $est->promedio >= 70 ? 'success' : 'warning' }}">
                                                {{ $est->promedio }}%
                                            </span>
                                        </td>
                                        <td style="min-width: 150px;">
                                            <div class="progress progress-xs mt-2" style="height: 8px; border-radius: 4px;">
                                                <div class="progress-bar {{ $est->promedio >= 70 ? 'bg-success' : 'bg-warning' }}" 
                                                     style="width: {{ $est->promedio }}%; border-radius: 4px;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @elseif(Auth::user()->isEstudiante())
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card card-quechua p-4 bg-white border-0 shadow-sm">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="rounded-circle bg-primary p-4 d-inline-flex align-items-center justify-content-center">
                                <i class="fas fa-graduation-cap fa-2x text-white"></i>
                            </div>
                        </div>
                        <div class="col-md-10">
                            <h2 class="font-weight-bold mb-1">¡Bienvenido(a), {{ Auth::user()->name }}!</h2>
                            <p class="text-muted mb-2">Tu cuenta ha sido habilitada como estudiante y podrás acceder a tu progreso personal.</p>
                            @if($data['mi_estudiante'])
                                <p class="mb-1"><b>Estudiante:</b> {{ $data['mi_estudiante']->nombre }}</p>
                                <p class="mb-1"><b>Nivel:</b> {{ strtoupper($data['mi_estudiante']->nivel) }}</p>
                                <p class="mb-1"><b>Profesor:</b> {{ $data['mi_profesor'] ?? 'No asignado' }}</p>
                            @else
                                <p class="text-warning mb-0">Tu cuenta de estudiante aún no tiene un perfil vinculado. Contacta al administrador para completar la información.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($data['mi_estudiante'])
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="small-box card-quechua p-4 bg-white">
                        <p class="text-muted mb-1 font-weight-bold">Promedio</p>
                        <h3 class="display-4 text-success">{{ $data['mi_promedio'] }}%</h3>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="small-box card-quechua p-4 bg-white">
                        <p class="text-muted mb-1 font-weight-bold">Asistencia</p>
                        <h3 class="display-4 text-info">{{ $data['mi_asistencia'] }}%</h3>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="small-box card-quechua p-4 bg-white">
                        <p class="text-muted mb-1 font-weight-bold">Responsable</p>
                        <h4 class="font-weight-bold text-primary">{{ $data['mi_estudiante']->padre->name ?? 'Sin asignar' }}</h4>
                    </div>
                </div>
            </div>
        @endif

    @else
        <!-- DASHBOARD RESPONSABLE -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card card-quechua p-4 bg-white border-0 shadow-sm d-flex flex-row align-items-center">
                    <div class="rounded-circle bg-warning p-3 mr-4">
                        <i class="fas fa-child fa-2x text-white"></i>
                    </div>
                    <div>
                        <h2 class="font-weight-bold mb-0">¡Bienvenido(a), {{ Auth::user()->name }}!</h2>
                        <p class="text-muted mb-0">Tienes <b>{{ $data['hijos_count'] }}</b> hijos registrados en la plataforma.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            @foreach($data['mis_hijos'] as $hijo)
            <div class="col-md-6 mb-4">
                <div class="card card-quechua h-100">
                    <div class="card-header card-header-quechua d-flex justify-content-between align-items-center">
                        <h3 class="card-title font-weight-bold mb-0">{{ $hijo->nombre }}</h3>
                        <span class="badge badge-quechua-gold px-3">{{ strtoupper($hijo->nivel) }}</span>
                    </div>
                    <div class="card-body bg-white">
                        <div class="row text-center">
                            <div class="col-4">
                                <p class="text-muted small mb-1">Promedio</p>
                                <h4 class="font-weight-bold {{ $hijo->promedio >= 70 ? 'text-success' : 'text-warning' }}">
                                    {{ $hijo->promedio }}%
                                </h4>
                            </div>
                            <div class="col-4 border-left border-right">
                                <p class="text-muted small mb-1">Asistencia</p>
                                <h4 class="font-weight-bold text-info">{{ $hijo->asistencia }}%</h4>
                            </div>
                            <div class="col-4">
                                <p class="text-muted small mb-1">Examen</p>
                                <h4 class="font-weight-bold text-primary">{{ $hijo->nota_examen }}</h4>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-muted small mb-2"><b>Profesor:</b> {{ $hijo->profesor->name ?? 'No asignado' }}</p>
                            <div class="progress" style="height: 10px; border-radius: 5px;">
                                <div class="progress-bar {{ $hijo->promedio >= 70 ? 'bg-success' : 'bg-warning' }}" 
                                     style="width: {{ $hijo->promedio }}%; border-radius: 5px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

    <!-- Nota del Integrante (Siempre visible) -->
    <div class="card card-quechua mt-4 border-left border-warning bg-white" style="border-left: 5px solid var(--primary-main) !important;">
        <div class="card-body p-3">
            <p class="mb-0 text-muted font-weight-bold"><i class="fas fa-info-circle mr-2 text-primary"></i> Integración tecnológica YURA Platform | Wilson Mendieta Arnez</p>
        </div>
    </div>
</div>
@endsection
