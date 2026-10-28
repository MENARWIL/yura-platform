@extends('layouts.app')

@section('title', __('messages.students_list'))

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-users mr-2"></i> {{ __('messages.students_list') }}
            </h3>
            <div class="card-tools d-flex align-items-center">
                <div class="mr-3">
                    @if(auth()->user()->isAdmin() || auth()->user()->isAcademic())
                        <a href="{{ route('students.create') }}" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm mr-2">
                            <i class="fas fa-user-plus mr-1"></i> Registrar Estudiante
                        </a>
                        <a href="{{ route('tutors.create') }}" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm mr-2">
                            <i class="fas fa-user-shield mr-1"></i> Registrar Tutor
                        </a>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->isProfesor())
                        <button type="button" class="btn btn-outline-danger btn-sm px-3 rounded-pill shadow-sm" data-toggle="modal" data-target="#exportStudentsModal">
                            <i class="fas fa-file-export mr-1"></i> Exportar
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body p-4 bg-white">
            <form action="{{ route('students.index') }}" method="GET" class="filter-bar" aria-label="Filtros de estudiantes" data-live-search="#students-results">
                <div class="fb-field fb-wide {{ request('student_name') ? 'is-set' : '' }}">
                    <label for="student_name"><i class="fas fa-user-graduate" aria-hidden="true"></i> Estudiante</label>
                    <div class="fb-search">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="search" name="student_name" id="student_name" value="{{ request('student_name') }}" placeholder="Buscar por nombre" autocomplete="off">
                    </div>
                </div>
                <div class="fb-field {{ request('course_id') ? 'is-set' : '' }}">
                    <label for="course_id"><i class="fas fa-layer-group" aria-hidden="true"></i> Curso</label>
                    <select name="course_id" id="course_id">
                        <option value="">Todos los cursos</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-field {{ request('parallel_id') ? 'is-set' : '' }}">
                    <label for="parallel_id"><i class="fas fa-users" aria-hidden="true"></i> Paralelo</label>
                    <select name="parallel_id" id="parallel_id">
                        <option value="">Todos los paralelos</option>
                        @foreach($parallels as $parallel)
                            <option value="{{ $parallel->id }}" {{ request('parallel_id') == $parallel->id ? 'selected' : '' }}>
                                {{ $parallel->course?->name }} - {{ $parallel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-field fb-narrow">
                    <label for="per_page"><i class="fas fa-list-ol" aria-hidden="true"></i> Mostrar</label>
                    <select name="per_page" id="per_page">
                        @foreach([5, 10, 15, 20] as $size)
                            <option value="{{ $size }}" {{ $students->perPage() === $size ? 'selected' : '' }}>{{ $size }} por página</option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-actions">
                    @if(request('student_name') || request('course_id') || request('parallel_id'))
                        <a href="{{ route('students.index') }}" class="fb-btn fb-btn-ghost" title="Quitar todos los filtros">
                            <i class="fas fa-undo" aria-hidden="true"></i> Limpiar
                        </a>
                    @endif
                    <button type="submit" class="fb-btn fb-btn-primary">
                        <i class="fas fa-search" aria-hidden="true"></i> Buscar
                    </button>
                </div>
            </form>

            <div id="students-results" data-live-results>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="px-4">Foto / Responsable</th>
                            <th>Apellido Paterno</th>
                            <th>Apellido Materno</th>
                            <th>Nombres</th>
                            <th>{{ __('messages.level') ?? 'Nivel' }}</th>
                            <th>{{ __('messages.age_gender') ?? 'Edad/Género' }}</th>
                            <th class="text-center">{{ __('messages.average') ?? 'Progreso' }}</th>
                            <th class="text-right px-4">Ficha Académica</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($students ?? []) as $student)
                        @php
                            // Prefer computed promedio_real if available, otherwise fall back to existing fields
                            $promedioDisplay = $student->promedio_real ?? ($student->promedio ?? $student->progreso ?? '-');
                            $promedio = is_numeric($promedioDisplay) ? (int) $promedioDisplay : 0;
                            $promedioTextClass = $promedio >= 70 ? 'text-success' : ($promedio >= 51 ? 'text-warning' : 'text-danger');
                            $promedioBarClass = $promedio >= 70 ? 'bg-success' : ($promedio >= 51 ? 'bg-warning' : 'bg-danger');
                        @endphp
                        <tr>
                            <td class="px-4">
                                <div class="d-flex align-items-center">
                                    @if($student->foto_path)
                                        <img src="{{ asset('storage/' . $student->foto_path) }}" alt="Foto de {{ $student->nombre }}" class="rounded-circle mr-3 shadow-sm" style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-3 shadow-sm" style="width:40px;height:40px;">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                    @endif
                                    <small class="text-muted"><i class="fas fa-user-friends mr-1"></i> {{ $student->parent?->name ?? $student->padre?->name ?? 'Sin asignar' }}</small>
                                </div>
                            </td>
                            @if($student->last_name || $student->first_name)
                                <td class="font-weight-bold text-dark">{{ $student->last_name ?: '-' }}</td>
                                <td class="font-weight-bold text-dark">{{ $student->second_last_name ?: '-' }}</td>
                                <td class="text-dark">{{ $student->first_name ?: '-' }}</td>
                            @else
                                <td colspan="3">
                                    <span class="text-dark">{{ $student->nombre }}</span>
                                    <small class="d-block text-warning"><i class="fas fa-info-circle mr-1"></i>Sin dividir en apellidos/nombres</small>
                                </td>
                            @endif
                            <td>
                                <span class="badge badge-quechua-gold px-3 py-2 rounded-pill font-weight-bold">
                                    {{ mb_strtoupper($student->level ?? $student->nivel ?? '6to Primaria', 'UTF-8') }}
                                </span>
                            </td>
                            <td>
                                <div class="small text-dark">
                                    <span class="d-block font-weight-bold">{{ $student->age ?? $student->edad ?? 11 }} años</span>
                                    <span class="text-muted">
                                        <i class="fas fa-{{ ($student->gender ?? $student->genero ?? 'Masculino') == 'Masculino' ? 'mars text-primary' : 'venus text-danger' }} mr-1"></i>
                                        {{ $student->gender ?? $student->genero ?? 'Masculino' }}
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                    <div class="d-inline-block text-center">
                                        <span class="font-weight-bold d-block {{ $promedioTextClass }}">
                                                     {{ is_numeric($promedioDisplay) ? $promedioDisplay . '%' : ($promedioDisplay ?? '-') }}
                                         </span>
                                    <div class="progress mt-1" style="height: 6px; width: 60px; border-radius: 2px; background-color: var(--yura-blue-light);">
                                     <div class="progress-bar {{ $promedioBarClass }}" role="progressbar" style="width: {{ $promedio }}%;" aria-valuenow="{{ $promedio }}" aria-valuemin="0" aria-valuemax="100"></div>
                                     </div>
                                     </div>
                            </td>

                            <td class="px-4 text-right">
                                <a href="{{ route('grades.show', $student->id) }}" class="btn btn-outline-primary btn-sm rounded-pill">
                                    <i class="fas fa-eye mr-1"></i> Ver Historial
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 bg-white text-muted">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-2 opacity-25"></i>
                                    <p>{{ __('messages.no_students') ?? 'No hay estudiantes registrados para este paralelo.' }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($students->total() > 0)
                <div class="list-footer">
                    <span class="list-count">Mostrando {{ $students->firstItem() }}–{{ $students->lastItem() }} de {{ $students->total() }} estudiantes</span>
                    {{ $students->onEachSide(1)->links() }}
                </div>
            @endif
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isProfesor())
<div class="modal fade" id="exportStudentsModal" tabindex="-1" role="dialog" aria-labelledby="exportStudentsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportStudentsModalLabel">Exportar estudiantes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="export-students-form" method="GET">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="d-block font-weight-bold">¿Qué desea exportar?</label>
                        <div class="mb-2">
                            <input type="radio" id="export_scope_all" name="export_scope" value="all" checked>
                            <label for="export_scope_all" class="mb-0 ml-1">Toda la unidad educativa</label>
                        </div>
                        <div class="mb-2">
                            <input type="radio" id="export_scope_course" name="export_scope" value="course">
                            <label for="export_scope_course" class="mb-0 ml-1">Un curso específico</label>
                        </div>
                        <div>
                            <input type="radio" id="export_scope_parallel" name="export_scope" value="parallel">
                            <label for="export_scope_parallel" class="mb-0 ml-1">Un curso y paralelo específico</label>
                        </div>
                    </div>
                    <div class="form-group export-course-field" style="display:none;">
                        <label for="export_course_id">Curso</label>
                        <select name="course_id" id="export_course_id" class="form-control">
                            <option value="">Seleccione un curso</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group export-parallel-field" style="display:none;">
                        <label for="export_parallel_id">Paralelo</label>
                        <select name="parallel_id" id="export_parallel_id" class="form-control">
                            <option value="">Seleccione un paralelo</option>
                            @foreach($parallels as $parallel)
                                <option value="{{ $parallel->id }}" data-course-id="{{ $parallel->course_id }}">
                                    {{ $parallel->course?->name }} - {{ $parallel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted" data-dismiss="modal">Cancelar</button>
                    <button type="submit" formaction="{{ route('students.export.excel.all') }}" class="btn btn-outline-success">
                        <i class="fas fa-file-excel mr-1"></i> Descargar Excel
                    </button>
                    <button type="submit" formaction="{{ route('students.export.pdf.all') }}" class="btn btn-outline-danger">
                        <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('export-students-form');
    if (!form) return;

    const courseField = form.querySelector('.export-course-field');
    const parallelField = form.querySelector('.export-parallel-field');
    const courseSelect = document.getElementById('export_course_id');
    const parallelSelect = document.getElementById('export_parallel_id');
    const parallelOptions = Array.from(parallelSelect.options);

    function filterParallels() {
        const courseId = courseSelect.value;
        parallelSelect.innerHTML = '';
        parallelOptions.forEach(function (option) {
            if (option.value === '' || !courseId || option.dataset.courseId === courseId) {
                parallelSelect.appendChild(option.cloneNode(true));
            }
        });
    }

    function updateScope() {
        const scope = form.querySelector('input[name="export_scope"]:checked').value;

        courseField.style.display = (scope === 'course' || scope === 'parallel') ? 'block' : 'none';
        parallelField.style.display = (scope === 'parallel') ? 'block' : 'none';

        courseSelect.disabled = (scope === 'all');
        parallelSelect.disabled = (scope !== 'parallel');

        if (scope === 'all') {
            courseSelect.value = '';
            parallelSelect.value = '';
        }
        if (scope !== 'parallel') {
            parallelSelect.value = '';
        }
    }

    form.querySelectorAll('input[name="export_scope"]').forEach(function (radio) {
        radio.addEventListener('change', updateScope);
    });
    courseSelect.addEventListener('change', function () {
        filterParallels();
        parallelSelect.value = '';
    });

    updateScope();
});
</script>
@endpush
