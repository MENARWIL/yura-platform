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

    <!-- FILTRO DE CURSO PARA DASHBOARD -->
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

    <!-- TARJETAS ESTADÍSTICAS -->
    @if(Auth::user()->isProfesor())
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-primary text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Mis estudiantes</h6>
                            <h2 class="font-weight-bold mb-0">{{ $data['mis_estudiantes_count'] ?? 0 }}</h2>
                        </div>
                        <div class="display-4 text-white-50"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-success text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Nivel básico</h6>
                            <h2 class="font-weight-bold mb-0">{{ $data['distribucion']['basico'] ?? 0 }}</h2>
                        </div>
                        <div class="display-4 text-white-50"><i class="fas fa-book-open"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-warning text-white h-100 rounded-lg" style="background-color: #d97706 !important;">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Nivel intermedio</h6>
                            <h2 class="font-weight-bold mb-0">{{ $data['distribucion']['intermedio'] ?? 0 }}</h2>
                        </div>
                        <div class="display-4 text-white-50"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-danger text-white h-100 rounded-lg">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Nivel avanzado</h6>
                            <h2 class="font-weight-bold mb-0">{{ $data['distribucion']['avanzado'] ?? 0 }}</h2>
                        </div>
                        <div class="display-4 text-white-50"><i class="fas fa-crown"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-lg">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="card-title font-weight-bold mb-0 text-dark"><i class="fas fa-user-graduate mr-2 text-primary"></i>Estudiantes asignados</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Curso</th>
                                        <th>Paralelo</th>
                                        <th>Nivel</th>
                                        <th>Puntaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['mis_estudiantes'] ?? [] as $student)
                                        <tr>
                                            <td>{{ $student->name }}</td>
                                            <td>{{ $student->course?->name ?? 'Sin curso' }}</td>
                                            <td>{{ $student->parallel?->name ?? 'Sin paralelo' }}</td>
                                            <td>{{ $student->level ?? '-' }}</td>
                                            <td>{{ $student->score ?? 0 }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No tienes estudiantes asignados.</td>
                                        </tr>
                                    @endforelse
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
        <!-- Estudiantes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm bg-primary text-white h-100 rounded-lg">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Total Estudiantes</h6>
                        <h2 class="font-weight-bold mb-0">{{ $data['total_students'] ?? 0 }}</h2>
                    </div>
                    <div class="display-4 text-white-50">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cursos -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm bg-warning text-white h-100 rounded-lg" style="background-color: #d97706 !important;">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Total Cursos</h6>
                        <h2 class="font-weight-bold mb-0">{{ $data['total_courses'] ?? 0 }}</h2>
                    </div>
                    <div class="display-4 text-white-50">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asistencia de Hoy -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm bg-success text-white h-100 rounded-lg">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Asistencia de Hoy</h6>
                        <h2 class="font-weight-bold mb-0">{{ $data['today_attendance'] ?? 'N/A' }}</h2>
                    </div>
                    <div class="display-4 text-white-50">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Retos de Robótica -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm bg-danger text-white h-100 rounded-lg">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="text-uppercase text-white-50 font-weight-bold small mb-1">Retos Lanzados</h6>
                        <h2 class="font-weight-bold mb-0">{{ $data['robot_challenges'] ?? 0 }}</h2>
                    </div>
                    <div class="display-4 text-white-50">
                        <i class="fas fa-robot"></i>
                    </div>
                </div>
            </div>
        </div>
        </div>
        
        <!-- GRÁFICO INTERACTIVO -->
        <div class="row">
            <div class="col-12 col-xl-8 mb-4">
            <div class="card border-0 shadow-sm rounded-lg">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="card-title font-weight-bold mb-0 text-dark">
                        <i class="fas fa-chart-bar mr-2 text-success"></i>Estadísticas del Trimestre (Simulado)
                    </h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div style="position: relative; height:250px; width:100%;">
                        <canvas id="dashboardChart"></canvas>
                    </div>
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
    const ctx = document.getElementById('dashboardChart').getContext('2d');
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
