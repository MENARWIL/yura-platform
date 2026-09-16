@extends('layouts.app')

@section('title', __('messages.dashboard') ?? 'Panel de Control')

@section('content')
<div class="container-fluid p-4 animate-fade-in">
    <!-- BIENVENIDA -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="font-weight-bold text-dark">
                <i class="fas fa-th-large mr-2 text-primary"></i>{{ __('messages.dashboard') ?? 'Panel de Control' }}
            </h2>
            <p class="text-muted">Bienvenido al ecosistema del Proyecto Yura. Aquí tienes el resumen académico actual.</p>
        </div>
    </div>

    @if(!Auth::user()->isTutor())
        <div class="row mb-3">
            <div class="col-12">
                <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center">
                    <label class="mr-3 text-muted small mb-0">Curso:</label>
                    <select name="course_id" class="form-control custom-select w-auto" onchange="this.form.submit()">
                        <option value="">Todos los cursos</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ (isset($selectedCourseId) && $selectedCourseId == $course->id) ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    @endif

    @if(Auth::user()->isTutor())
        <div class="row mb-4">
            @php($tutorReport = $data['tutor_report'] ?? [])
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-primary text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div><h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Mis hijos</h6><h2 class="font-weight-bold mb-0">{{ $data['hijos_count'] ?? 0 }}</h2></div>
                        <div class="display-4 text-white-50"><i class="fas fa-child"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-success text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div><h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Promedio familiar</h6><h2 class="font-weight-bold mb-0">{{ $tutorReport['average'] !== null ? $tutorReport['average'] . '%' : 'N/A' }}</h2></div>
                        <div class="display-4 text-white-50"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-warning text-dark h-100 rounded-lg" style="background-color: #9bd7ad !important;">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div><h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Asistencia familiar</h6><h2 class="font-weight-bold mb-0">{{ $tutorReport['attendance_percent'] !== null ? $tutorReport['attendance_percent'] . '%' : 'N/A' }}</h2></div>
                        <div class="display-4 text-white-50"><i class="fas fa-calendar-check"></i></div>
                    </div>
                </div>
            </div>
        </div>

        @php($children = $data['mis_hijos'] ?? [])
        @if(count($children) > 0)
            <div class="card border-0 shadow-sm rounded-lg mb-4">
                <div class="card-body px-4 py-3">
                    <label for="child-selector" class="font-weight-bold text-dark mb-2">
                        <i class="fas fa-users mr-2 text-primary"></i>Seleccionar hijo
                    </label>
                    <select id="child-selector" class="form-control custom-select">
                        @foreach($children as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} · {{ $student->course?->name ?? $student->getRawOriginal('course') ?? 'Sin curso' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        @if(!empty($children))
            @foreach($children as $student)
                @php($report = $student->dashboard_report ?? [])
                @php($hideChild = !$loop->first)
                <div class="card border-0 shadow-sm rounded-lg mb-4 tutor-child-report"
                    data-child-id="{{ $student->id }}"
                    style="{{ $hideChild ? 'display: none;' : '' }}">
                    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h4 class="font-weight-bold mb-1 text-dark"><i class="fas fa-user-graduate mr-2 text-primary"></i>{{ $student->name }}</h4>
                            <p class="text-muted mb-0">{{ $student->course?->name ?? $student->getRawOriginal('course') ?? 'Sin curso' }} · {{ $student->parallel?->name ?? 'Sin paralelo' }}</p>
                        </div>
                        <span class="badge badge-light px-3 py-2 mt-2 mt-md-0">Reporte académico</span>
                    </div>
                    <div class="card-body px-4">
                        <div class="row mb-4">
                            <div class="col-lg-3 col-6 mb-3 mb-lg-0"><div class="border-left border-primary pl-3"><small class="text-muted d-block">Promedio general</small><strong class="h4 text-primary">{{ $report['average'] !== null ? $report['average'] . '%' : 'N/A' }}</strong></div></div>
                            <div class="col-lg-3 col-6 mb-3 mb-lg-0"><div class="border-left border-success pl-3"><small class="text-muted d-block">Asistencia</small><strong class="h4 text-success">{{ $report['attendance_percent'] !== null ? $report['attendance_percent'] . '%' : 'N/A' }}</strong><small class="d-block text-muted">{{ $report['attendance_present'] }}/{{ $report['attendance_total'] }} registros</small></div></div>
                            <div class="col-lg-3 col-6"><div class="border-left border-warning pl-3"><small class="text-muted d-block">Retos Robot</small><strong class="h4 text-warning">{{ $report['robot_completed'] }}/{{ $report['robot_total'] }}</strong><small class="d-block text-muted">completados</small></div></div>
                            <div class="col-lg-3 col-6"><div class="border-left border-info pl-3"><small class="text-muted d-block">Trimestres</small><strong class="h4 text-info">{{ collect($report['quarter_averages'])->filter()->count() }}/3</strong><small class="d-block text-muted">con calificaciones</small></div></div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 mb-3 mb-md-0"><div class="bg-light rounded p-3 h-100"><small class="text-muted d-block">Actividades</small><strong class="h4 text-primary">{{ $report['activity_average'] !== null ? $report['activity_average'] . '%' : 'N/A' }}</strong><small class="d-block text-muted">{{ $report['activity_count'] }} evaluaciones</small></div></div>
                            <div class="col-md-4 mb-3 mb-md-0"><div class="bg-light rounded p-3 h-100"><small class="text-muted d-block">Exámenes</small><strong class="h4 text-danger">{{ $report['exam_average'] !== null ? $report['exam_average'] . '%' : 'N/A' }}</strong><small class="d-block text-muted">{{ $report['exam_count'] }} evaluaciones</small></div></div>
                            <div class="col-md-4"><div class="bg-light rounded p-3 h-100"><small class="text-muted d-block">Plataforma Yura</small><strong class="h4 text-warning">{{ $report['robot_average'] !== null ? $report['robot_average'] . '%' : 'N/A' }}</strong><small class="d-block text-muted">{{ $report['robot_count'] }} evaluaciones</small></div></div>
                        </div>

                        <div class="row">
                            <div class="col-lg-5 mb-4 mb-lg-0">
                                <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-chart-bar mr-2 text-success"></i>Promedio por trimestre</h6>
                                <div class="table-responsive"><table class="table table-sm mb-0"><tbody>
                                    @foreach([1 => 'Primer trimestre', 2 => 'Segundo trimestre', 3 => 'Tercer trimestre'] as $quarter => $label)
                                        <tr><td>{{ $label }}</td><td class="text-right font-weight-bold">{{ $report['quarter_averages'][$quarter] !== null ? $report['quarter_averages'][$quarter] . '%' : 'Sin notas' }}</td></tr>
                                    @endforeach
                                </tbody></table></div>
                            </div>
                            <div class="col-lg-7">
                                <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-book mr-2 text-primary"></i>Rendimiento por materia</h6>
                                <div class="table-responsive"><table class="table table-sm mb-0"><thead class="thead-light"><tr><th>Materia</th><th>Evaluaciones</th><th class="text-right">Promedio</th></tr></thead><tbody>
                                    @if(!empty($report['subjects'] ?? []))
                                        @foreach($report['subjects'] as $subject)
                                            <tr><td>{{ $subject['name'] }}</td><td>{{ $subject['count'] }}</td><td class="text-right font-weight-bold">{{ $subject['average'] }}%</td></tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="3" class="text-center text-muted py-3">Aún no hay calificaciones.</td></tr>
                                    @endif
                                </tbody></table></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="card border-0 shadow-sm mb-4"><div class="card-body text-center py-5"><i class="fas fa-child display-4 text-muted mb-3"></i><h4 class="font-weight-bold">No hay hijos asignados</h4><p class="text-muted mb-0">Cuando se registre una relación familiar, aquí aparecerá el reporte académico.</p></div></div>
        @endif

        @if(count($children) > 0)
            <script>
                document.getElementById('child-selector').addEventListener('change', function () {
                    document.querySelectorAll('.tutor-child-report').forEach(function (report) {
                        report.style.display = report.dataset.childId === this.value ? '' : 'none';
                    }, this);
                });
            </script>
        @endif
    @elseif(Auth::user()->isProfesor())
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-primary text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div><h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Mis estudiantes</h6><h2 class="font-weight-bold mb-0">{{ $data['mis_estudiantes_count'] ?? 0 }}</h2></div>
                        <div class="display-4 text-white-50"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-success text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div><h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Promedio general</h6><h2 class="font-weight-bold mb-0">{{ number_format((float) ($data['promedio_general'] ?? 0), 1) }}%</h2></div>
                        <div class="display-4 text-white-50"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-warning text-dark h-100 rounded-lg" style="background-color: #9bd7ad !important;">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div><h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Asistencia hoy</h6><h2 class="font-weight-bold mb-0">{{ number_format((float) ($data['asistencia_hoy'] ?? 0), 1) }}%</h2></div>
                        <div class="display-4 text-white-50"><i class="fas fa-calendar-check"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-danger text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div><h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Bajo rendimiento</h6><h2 class="font-weight-bold mb-0">{{ $data['bajo_rendimiento'] ?? 0 }}</h2></div>
                        <div class="display-4 text-white-50"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-xl-5 mb-4">
                <div class="card border-0 shadow-sm rounded-lg h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="card-title font-weight-bold mb-0 text-dark"><i class="fas fa-layer-group mr-2 text-primary"></i>Distribución por nivel</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        @php($studentsCount = $data['mis_estudiantes_count'] ?? 0)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between"><span>Básico</span><strong>{{ $data['distribucion']['basico'] ?? 0 }}</strong></div>
                            <div class="progress mt-1"><div class="progress-bar bg-primary" style="width: {{ $studentsCount ? (($data['distribucion']['basico'] ?? 0) / $studentsCount) * 100 : 0 }}%"></div></div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between"><span>Intermedio</span><strong>{{ $data['distribucion']['intermedio'] ?? 0 }}</strong></div>
                            <div class="progress mt-1"><div class="progress-bar bg-warning" style="width: {{ $studentsCount ? (($data['distribucion']['intermedio'] ?? 0) / $studentsCount) * 100 : 0 }}%"></div></div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between"><span>Avanzado</span><strong>{{ $data['distribucion']['avanzado'] ?? 0 }}</strong></div>
                            <div class="progress mt-1"><div class="progress-bar bg-success" style="width: {{ $studentsCount ? (($data['distribucion']['avanzado'] ?? 0) / $studentsCount) * 100 : 0 }}%"></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7 mb-4">
                <div class="card border-0 shadow-sm rounded-lg h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="card-title font-weight-bold mb-0 text-dark"><i class="fas fa-star mr-2 text-warning"></i>Top rendimiento</h5>
                        <a href="{{ route('grades.index') }}" class="btn btn-sm btn-outline-primary">Cuaderno pedagógico</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light"><tr><th>Estudiante</th><th>Curso</th><th class="text-right">Promedio</th></tr></thead>
                                <tbody>
                                    @if(!empty($data['ranking'] ?? []))
                                        @foreach($data['ranking'] as $student)
                                            <tr><td>{{ $student->name }}</td><td>{{ $student->course?->name ?? 'Sin curso' }}</td><td class="text-right font-weight-bold text-success">{{ number_format((float) $student->promedio_academico, 1) }}%</td></tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="3" class="text-center text-muted py-4">Aún no hay notas registradas.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-lg">
                    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="card-title font-weight-bold mb-0 text-dark"><i class="fas fa-user-graduate mr-2 text-primary"></i>Estudiantes asignados</h5>
                        <a href="{{ route('grades.index') }}" class="btn btn-primary btn-sm">Registrar notas</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light"><tr><th>Nombre</th><th>Curso</th><th>Paralelo</th><th>Nivel</th><th>Promedio</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    @if(!empty($data['mis_estudiantes'] ?? []))
                                        @foreach($data['mis_estudiantes'] as $student)
                                            @php($studentAverage = $student->grades->where('status', 'completed')->filter(fn($grade) => is_numeric($grade->score))->count() ? round($student->grades->where('status', 'completed')->filter(fn($grade) => is_numeric($grade->score))->avg('score'), 2) : 0)
                                            <tr>
                                                <td>{{ $student->name }}</td>
                                                <td>{{ $student->course?->name ?? 'Sin curso' }}</td>
                                                <td>{{ $student->parallel?->name ?? 'Sin paralelo' }}</td>
                                                <td>{{ $student->level ?? '-' }}</td>
                                                <td><span class="font-weight-bold {{ $studentAverage >= 70 ? 'text-success' : ($studentAverage >= 50 ? 'text-warning' : 'text-danger') }}">{{ number_format((float) $studentAverage, 1) }}%</span></td>
                                                <td><a href="{{ route('grades.show', $student->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye mr-1"></i> Ver</a></td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="6" class="text-center text-muted py-4">No tienes estudiantes asignados.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-lg">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="card-title font-weight-bold mb-0 text-dark"><i class="fas fa-bell mr-2 text-warning"></i>Últimas notas registradas</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light"><tr><th>Estudiante</th><th>Materia</th><th>Tipo</th><th>Nota</th><th>Fecha</th></tr></thead>
                                <tbody>
                                    @if(!empty($data['notas_recientes'] ?? []))
                                        @foreach($data['notas_recientes'] as $grade)
                                            <tr><td>{{ $grade->student?->name ?? 'Estudiante' }}</td><td>{{ $grade->subject?->name ?? 'Sin materia' }}</td><td>{{ ucfirst($grade->type ?? 'Nota') }}</td><td class="font-weight-bold">{{ $grade->score ?? 0 }}</td><td>{{ $grade->created_at?->format('d/m/Y') ?? '-' }}</td></tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="5" class="text-center text-muted py-4">No hay notas recientes.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif(empty($selectedCourseId))
        <div class="row mb-4">
            <div class="col-12">
                <div class="text-center my-4 p-5 border rounded-lg bg-light shadow-sm mx-auto" style="max-width: 800px;">
                    <div class="display-4 text-warning mb-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4 class="font-weight-bold text-dark mb-2">Selección de Curso Requerida</h4>
                    <p class="text-muted mb-0">Por favor, seleccione un curso en el menú superior para visualizar las estadísticas de rendimiento, asistencia y robótica correspondientes.</p>
                </div>
            </div>
        </div>
    @else
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-primary text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Total Estudiantes</h6>
                            <h2 class="font-weight-bold mb-0">{{ $data['total_students'] ?? 0 }}</h2>
                        </div>
                        <div class="display-4 text-white-50"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-warning text-dark h-100 rounded-lg" style="background-color: #9bd7ad !important;">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Total Cursos</h6>
                            <h2 class="font-weight-bold mb-0">{{ $data['total_courses'] ?? 0 }}</h2>
                        </div>
                        <div class="display-4 text-white-50"><i class="fas fa-graduation-cap"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-success text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Asistencia de Hoy</h6>
                            <h2 class="font-weight-bold mb-0">{{ $data['today_attendance'] ?? 'N/A' }}</h2>
                        </div>
                        <div class="display-4 text-white-50"><i class="fas fa-calendar-check"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-danger text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Retos Lanzados</h6>
                            <h2 class="font-weight-bold mb-0">{{ $data['robot_challenges'] ?? 0 }}</h2>
                        </div>
                        <div class="display-4 text-white-50"><i class="fas fa-robot"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xl-8 mb-4">
                <div class="card border-0 shadow-sm rounded-lg">
                    <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                        <h5 class="card-title font-weight-bold mb-0 text-dark"><i class="fas fa-chart-bar mr-2 text-success"></i>Estadísticas del Trimestre (Simulado)</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div style="position: relative; height:250px; width:100%;"><canvas id="dashboardChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- LIBRERÍA Y SCRIPT DEL GRÁFICO -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chart = document.getElementById('dashboardChart');
    if (!chart) return;

    const ctx = chart.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Primer Trimestre', 'Segundo Trimestre', 'Tercer Trimestre'],
            datasets: [{
                label: 'Promedio de Rendimiento General',
                data: [72.4, 0, 0],
                backgroundColor: ['rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)', 'rgba(75, 192, 192, 0.6)'],
                borderColor: ['rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)', 'rgba(75, 192, 192, 1)'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
});
</script>
@endsection
