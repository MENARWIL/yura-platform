@extends('layouts.app')

@section('title', $year->name)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between">
                    <div class="mb-2">
                        <h4 class="font-weight-bold mb-1 text-dark">
                            <i class="fas fa-folder-open mr-2 text-primary" aria-hidden="true"></i>{{ $year->name }}
                            @if($year->is_current)<span class="badge badge-primary align-middle ml-1">Actual</span>@endif
                            @if($year->isClosed())
                                <span class="badge badge-secondary align-middle ml-1"><i class="fas fa-lock mr-1" aria-hidden="true"></i>Cerrada</span>
                            @else
                                <span class="badge badge-success align-middle ml-1">Abierta</span>
                            @endif
                        </h4>
                        <p class="text-muted small mb-0">Consulta de la gestión: lo que se guardó en ella, tal como quedó.</p>
                    </div>
                    <div class="d-flex flex-wrap">
                        <a href="{{ route('admin.years.index') }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2"><i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>Volver a Gestiones</a>
                        <a href="{{ route('admin.years.export', $year) }}" class="btn btn-sm btn-primary mr-2 mb-2"><i class="fas fa-file-excel mr-1" aria-hidden="true"></i>Descargar respaldo (Excel)</a>
                        @if($year->isClosed())
                            <form method="POST" action="{{ route('admin.years.reopen', $year) }}" class="d-inline mb-2"
                                  onsubmit="return confirm('Reabrir {{ $year->name }} vuelve a permitir cambios en datos de un año cerrado. ¿Continuar?')">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning"><i class="fas fa-lock-open mr-1" aria-hidden="true"></i>Reabrir para editar</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body px-4">
                @if($year->isClosed())
                    <div class="alert alert-secondary d-flex align-items-start" role="status">
                        <i class="fas fa-lock mt-1 mr-3" aria-hidden="true"></i>
                        <div>
                            Cerrada el <strong>{{ $year->closed_at->format('d/m/Y') }}</strong>@if($year->closedBy) por <strong>{{ $year->closedBy->name }}</strong>@endif.
                            Sus datos se conservan sin cambios y <strong>nadie puede modificarlos</strong> mientras esté cerrada.
                            Solo el administrador puede reabrirla; la consulta y la descarga no la reabren.
                        </div>
                    </div>
                @endif

                <div class="row">
                    @foreach([
                        ['Matrículas', $totals['enrollments'], 'fa-user-graduate'],
                        ['Notas', $totals['grades'], 'fa-star'],
                        ['Asistencias', $totals['attendance'], 'fa-calendar-check'],
                        ['Actividades Yura', $totals['activities'], 'fa-robot'],
                    ] as [$label, $value, $icon])
                        <div class="col-6 col-lg-3 mb-3">
                            <div class="border rounded-lg p-3 h-100 bg-light">
                                <div class="text-muted small text-uppercase font-weight-bold"><i class="fas {{ $icon }} mr-1" aria-hidden="true"></i>{{ $label }}</div>
                                <div class="h3 font-weight-bold mb-0 text-primary">{{ number_format($value) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if($courses->isNotEmpty())
            <form method="GET" action="{{ route('admin.years.show', $year) }}" class="filter-bar" aria-label="Filtros de estudiantes de {{ $year->name }}">
                <div class="fb-field {{ request('course_id') ? 'is-set' : '' }}">
                    <label for="ys-course"><i class="fas fa-layer-group" aria-hidden="true"></i> Curso</label>
                    <select id="ys-course" name="course_id" onchange="this.form.elements['parallel_id'].value=''; this.form.submit()">
                        <option value="">Todos los cursos</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ (int) request('course_id') === $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-field {{ request('parallel_id') ? 'is-set' : '' }}">
                    <label for="ys-parallel"><i class="fas fa-users" aria-hidden="true"></i> Paralelo</label>
                    <select id="ys-parallel" name="parallel_id" onchange="this.form.submit()">
                        <option value="">Todos los paralelos</option>
                        @foreach($parallels as $parallel)
                            <option value="{{ $parallel->id }}" {{ (int) request('parallel_id') === $parallel->id ? 'selected' : '' }}>{{ $parallel->course?->name }} - {{ $parallel->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($filtered)
                    <div class="fb-actions">
                        <a href="{{ route('admin.years.show', $year) }}" class="fb-btn fb-btn-ghost" title="Quitar los filtros">
                            <i class="fas fa-undo" aria-hidden="true"></i> Limpiar
                        </a>
                    </div>
                @endif
            </form>
            @if($filtered)
                <p class="text-muted small mb-3" role="status">Mostrando {{ $shown }} de {{ $totals['enrollments'] }} estudiantes de la gestión. El respaldo de Excel incluye toda la gestión.</p>
            @endif
        @endif

        @forelse($groups as $title => $students)
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="font-weight-bold mb-0 text-dark">{{ $title }}</h6>
                    <span class="badge badge-light border">{{ $students->count() }} {{ $students->count() === 1 ? 'estudiante' : 'estudiantes' }}</span>
                </div>
                <div class="card-body px-4 pt-2">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 70px;">Nro</th>
                                    <th>Estudiante</th>
                                    <th class="text-right">Notas</th>
                                    <th class="text-right">Asistencias</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="font-weight-bold">{{ $student->name }}</td>
                                        <td class="text-right">{{ $student->grades }}</td>
                                        <td class="text-right">{{ $student->attendance }}</td>
                                        <td><small class="text-muted">{{ $student->status ?: '—' }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="card shadow-sm border-0">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-inbox fa-2x mb-2" aria-hidden="true"></i>
                    @if($filtered)
                        <p class="mb-2">Ningún estudiante coincide con los filtros elegidos.</p>
                        <a href="{{ route('admin.years.show', $year) }}" class="fb-btn fb-btn-ghost"><i class="fas fa-undo" aria-hidden="true"></i> Limpiar filtros</a>
                    @else
                        <p class="mb-0">Esta gestión no tiene matrículas registradas.</p>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
