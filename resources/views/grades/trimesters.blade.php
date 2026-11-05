@extends('layouts.app')

@section('title', 'Notas trimestrales')

@section('content')
<style>
    .notas-trimestrales th, .notas-trimestrales td { vertical-align: middle; }
    .notas-trimestrales thead th { background-color: var(--yura-blue-light); color: var(--yura-blue-dark); font-weight: 700; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
    .notas-trimestrales thead th.col-anual { background-color: var(--yura-blue); color: var(--yura-white); }
    .notas-trimestrales .nota { font-weight: 700; font-size: 0.95rem; }
    .notas-trimestrales .nota-anual { background-color: var(--yura-blue-light); color: var(--yura-blue); font-weight: 700; font-size: 1rem; }
</style>
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-clipboard-list mr-2"></i> Notas trimestrales
            </h3>
        </div>

        <div class="card-body bg-white p-4">
            <div class="alert alert-info mb-4" role="alert">
                <i class="fas fa-info-circle mr-2"></i>
                Solo lectura: nota final de cada trimestre sobre 100 (SER, SABER, HACER y autoevaluación), con el mismo cálculo del Cuaderno Pedagógico.
            </div>

            <form method="GET" action="{{ route('grades.trimesters') }}" class="filter-bar" aria-label="Filtros de notas trimestrales" data-filter-required="subject_id,parallel_id">
                <div class="fb-field {{ $selectedSubjectId ? 'is-set' : '' }}">
                    <label for="tf-subject"><i class="fas fa-book-open" aria-hidden="true"></i> Asignatura</label>
                    <select id="tf-subject" name="subject_id">
                        <option value="">Elige una asignatura</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $selectedSubjectId == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-field {{ $selectedParallelId ? 'is-set' : '' }}">
                    <label for="tf-parallel"><i class="fas fa-users" aria-hidden="true"></i> Curso y paralelo</label>
                    <select id="tf-parallel" name="parallel_id">
                        <option value="">Elige un paralelo</option>
                        @foreach($parallels as $parallel)
                            <option value="{{ $parallel->id }}" {{ $selectedParallelId == $parallel->id ? 'selected' : '' }}>{{ $parallel->course->name }} - {{ $parallel->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($selectedSubjectId || $selectedParallelId)
                    <div class="fb-actions">
                        <a href="{{ route('grades.trimesters') }}" class="fb-btn fb-btn-ghost" title="Quitar asignatura y paralelo">
                            <i class="fas fa-undo" aria-hidden="true"></i> Limpiar
                        </a>
                        @if($selectedSubjectId && $selectedParallelId)
                            <a href="{{ route('grades.trimesters.pdf', ['subject_id' => $selectedSubjectId, 'parallel_id' => $selectedParallelId]) }}" class="fb-btn fb-btn-outline">
                                <i class="fas fa-file-pdf" aria-hidden="true"></i> Planilla PDF
                            </a>
                            <a href="{{ route('grades.trimesters.excel', ['subject_id' => $selectedSubjectId, 'parallel_id' => $selectedParallelId]) }}" class="fb-btn fb-btn-outline">
                                <i class="fas fa-file-excel" aria-hidden="true"></i> Planilla Excel
                            </a>
                        @endif
                    </div>
                @endif
                <p class="fb-hint" data-filter-hint hidden>
                    <i class="fas fa-info-circle" aria-hidden="true"></i> Elige asignatura y paralelo; las notas se cargan solas al completar los filtros.
                </p>
            </form>

            @if(empty($selectedSubjectId) || empty($selectedParallelId))
                <div class="card border-warning shadow-sm my-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                        <h5 class="font-weight-bold">Seleccione una asignatura y un paralelo.</h5>
                        <p class="text-muted mb-0">Verá la nota final de cada estudiante en los tres trimestres y su promedio anual.</p>
                    </div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-bordered notas-trimestrales mt-3" id="trimestersTable">
                        <thead>
                            <tr>
                                <th class="text-center" title="Número en la lista del curso y paralelo">Nro</th>
                                <th>{{ __('messages.student_name') }}</th>
                                <th class="text-center">1.er trimestre<br><small>(100 pts)</small></th>
                                <th class="text-center">2.º trimestre<br><small>(100 pts)</small></th>
                                <th class="text-center">3.er trimestre<br><small>(100 pts)</small></th>
                                <th class="text-center col-anual">Promedio anual</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr data-student-id="{{ $row['student']->id }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="font-weight-bold">
                                        {{ $row['student']->name }}
                                        <a href="{{ route('students.boletin', $row['student']) }}" class="ml-2 small" title="Boletín de notas trimestrales de {{ $row['student']->name }}">
                                            <i class="fas fa-file-pdf" aria-hidden="true"></i> Boletín
                                        </a>
                                    </td>
                                    @foreach([1, 2, 3] as $quarter)
                                        <td class="text-center nota">{{ $row['quarters'][$quarter] ?? '-' }}</td>
                                    @endforeach
                                    <td class="text-center nota-anual">{{ $row['annual'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-5">No hay estudiantes activos en este paralelo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0"><i class="fas fa-info-circle mr-1" aria-hidden="true"></i> «-» significa que el trimestre todavía no tiene notas. El promedio anual considera solo los trimestres con nota.</p>
            @endif
        </div>
    </div>
</div>
@endsection
