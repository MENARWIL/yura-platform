@extends('layouts.app')

@section('title', __('messages.grades_management'))

@section('content')
<style>
    /* Cuaderno pedagógico estilo planilla SER/SABER/HACER */
    .cuaderno-ser-saber-hacer th, .cuaderno-ser-saber-hacer td { font-size: 0.78rem; }
    .cuaderno-ser-saber-hacer thead th { vertical-align: middle; }
    .cuaderno-ser-saber-hacer .banner-evaluacion {
        background-color: var(--yura-blue); color: var(--yura-white); font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
    }
    .cuaderno-ser-saber-hacer .grupo-ser {
        background-color: var(--yura-blue-light); color: var(--yura-blue-dark); font-weight: 700; text-transform: uppercase;
    }
    .cuaderno-ser-saber-hacer .grupo-saber,
    .cuaderno-ser-saber-hacer .grupo-hacer {
        background-color: var(--yura-red-light); color: var(--yura-red-dark); font-weight: 700; text-transform: uppercase;
    }
    .cuaderno-ser-saber-hacer .prom-ser,
    .cuaderno-ser-saber-hacer .prom-saber,
    .cuaderno-ser-saber-hacer .prom-hacer {
        background-color: var(--yura-bg); color: var(--yura-text); font-weight: 700;
    }
    .cuaderno-ser-saber-hacer .col-total { background-color: var(--yura-blue-light); color: var(--yura-blue); font-weight: 700; }
    .cuaderno-ser-saber-hacer .col-autoeval { background-color: var(--yura-bg); color: var(--yura-text); font-weight: 700; }
    .cuaderno-ser-saber-hacer .col-nota { background-color: var(--yura-blue); color: var(--yura-white); font-weight: 700; }
    .cuaderno-ser-saber-hacer .vertical-text {
        writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; letter-spacing: 1px;
    }
    .cuaderno-ser-saber-hacer .dim-cell.dim-ser { background-color: #F7F9FD; }
    .cuaderno-ser-saber-hacer .dim-cell.dim-saber,
    .cuaderno-ser-saber-hacer .dim-cell.dim-hacer { background-color: #FEF6F7; }
    .cuaderno-ser-saber-hacer .total-cell { background-color: var(--yura-blue-light); }
    .cuaderno-ser-saber-hacer .nota-bimestral-cell { background-color: var(--yura-blue-light); font-size: 0.95rem; }
    .cuaderno-ser-saber-hacer .robot-hint { color: #1f9d55; margin-left: 2px; font-size: 0.7rem; }

</style>
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-book mr-2"></i> {{ __('messages.grades_management') }}
            </h3>
        </div>

        <div class="card-body bg-white p-4">
            @unless(auth()->user()->isProfesor())
                <div class="alert alert-info mb-4" role="alert">
                    <i class="fas fa-info-circle mr-2"></i>
                    Modo solo lectura: solo el profesor asignado puede registrar o modificar calificaciones.
                </div>
            @endunless
            <!-- FILTROS DEL CUADERNO -->
            <form method="GET" action="{{ route('grades.index') }}" class="filter-bar" aria-label="Filtros del cuaderno pedagógico" data-filter-required="subject_id,parallel_id,quarter">
                <div class="fb-field {{ $selectedSubjectId ? 'is-set' : '' }}">
                    <label for="gf-subject"><i class="fas fa-book-open" aria-hidden="true"></i> Asignatura</label>
                    <select id="gf-subject" name="subject_id">
                        <option value="">Todas las asignaturas</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $selectedSubjectId == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-field {{ $selectedParallelId ? 'is-set' : '' }}">
                    <label for="gf-parallel"><i class="fas fa-users" aria-hidden="true"></i> Curso y paralelo</label>
                    <select id="gf-parallel" name="parallel_id">
                        <option value="">Todos los paralelos</option>
                        @foreach($parallels as $parallel)
                            <option value="{{ $parallel->id }}" {{ $selectedParallelId == $parallel->id ? 'selected' : '' }}>
                                {{ $parallel->course->name }} - {{ $parallel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-field">
                    <label for="gf-quarter"><i class="fas fa-calendar-alt" aria-hidden="true"></i> Trimestre</label>
                    <select id="gf-quarter" name="quarter">
                        <option value="1" {{ $selectedQuarter == 1 ? 'selected' : '' }}>Primer Trimestre</option>
                        <option value="2" {{ $selectedQuarter == 2 ? 'selected' : '' }}>Segundo Trimestre</option>
                        <option value="3" {{ $selectedQuarter == 3 ? 'selected' : '' }}>Tercer Trimestre</option>
                    </select>
                </div>
                @if($selectedSubjectId || $selectedParallelId)
                    <div class="fb-actions">
                        <a href="{{ route('grades.index', ['quarter' => $selectedQuarter]) }}" class="fb-btn fb-btn-ghost" title="Quitar asignatura y paralelo">
                            <i class="fas fa-undo" aria-hidden="true"></i> Limpiar
                        </a>
                        @if($selectedSubjectId && $selectedParallelId)
                            <a href="{{ route('grades.export.pdf', ['subject_id' => $selectedSubjectId, 'parallel_id' => $selectedParallelId, 'quarter' => $selectedQuarter]) }}" class="fb-btn fb-btn-outline">
                                <i class="fas fa-file-pdf" aria-hidden="true"></i> Exportar PDF
                            </a>
                        @endif
                    </div>
                @endif
                <p class="fb-hint" data-filter-hint hidden>
                    <i class="fas fa-info-circle" aria-hidden="true"></i> Elige asignatura y paralelo; el cuaderno se carga solo al completar los filtros.
                </p>
                @if($selectedSubjectId && $selectedParallelId && auth()->user()->isProfesor())
                    <p class="fb-hint">
                        <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i> Cada nota se guarda sola al salir de la casilla. Notas enteras de 1 a 100 (autoevaluación de 1 a 5).
                    </p>
                @endif
            </form>

            <!-- FORMULARIO DE CALIFICACIONES INTERACTIVAS -->
            @if(empty($selectedSubjectId) || empty($selectedParallelId))
                <div class="card border-warning shadow-sm my-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                        <h5 class="font-weight-bold">Seleccione una asignatura y un paralelo para comenzar.</h5>
                        <p class="text-muted mb-0">Elija ambos filtros. Las notas y asistencias se registrarán dentro de la asignación docente seleccionada.</p>
                        @if($subjects->isEmpty() || $parallels->isEmpty())
                            <p class="text-danger mt-3 mb-0">No tienes asignaciones docentes activas disponibles.</p>
                        @endif
                    </div>
                </div>
            @else
                @php
                    // Casillas visibles por dimensión (App\Services\NotebookColumns): el profesor agrega o descarta.
                    $dimLabels = ['ser' => 'Ser', 'saber' => 'Saber', 'hacer' => 'Hacer'];
                    $dimMax = \App\Services\NotebookColumns::MAX;
                    $colsTotal = $columns['ser'] + $columns['saber'] + $columns['hacer'];
                @endphp
                <div class="table-responsive table-scroll-box">
                    <table class="table table-hover align-middle table-bordered cuaderno-ser-saber-hacer" id="gradesTable"
                           data-columns-url="{{ $canEditColumns ? route('grades.columns') : '' }}"
                           data-subject="{{ $selectedSubjectId }}" data-parallel="{{ $selectedParallelId }}" data-quarter="{{ $selectedQuarter }}">
                    <thead>
                        <tr>
                            <th rowspan="3" class="px-3 text-center align-middle" title="Número en la lista del curso y paralelo">Nro</th>
                            <th rowspan="3" class="px-4 align-middle">{{ __('messages.student_name') }}</th>
                            <th rowspan="3" class="align-middle">{{ __('messages.level') }}</th>
                            <th colspan="{{ 3 + $colsTotal }}" class="text-center banner-evaluacion" id="banner-evaluacion">EVALUACIÓN MAESTRO(A)</th>
                            <th rowspan="3" class="text-center align-middle col-total">TOTAL<br>(90 PTS)</th>
                            <th rowspan="3" class="text-center align-middle col-autoeval">AUTOEVALUACIÓN<br>TOTAL (5 PTS)</th>
                            <th rowspan="3" class="text-center align-middle col-nota">NOTA<br>BIMESTRAL<br>(100 PTS)</th>
                        </tr>
                        <tr>
                            @foreach(['ser' => '10', 'saber' => '45', 'hacer' => '40'] as $dim => $pts)
                            <th colspan="{{ $columns[$dim] }}" class="text-center grupo-{{ $dim }} text-nowrap" id="grupo-{{ $dim }}" data-dim="{{ $dim }}" data-count="{{ $columns[$dim] }}" data-max="{{ $dimMax[$dim] }}">
                                {{ strtoupper($dim) }}
                                @if($canEditColumns)
                                    <span class="ml-2 d-inline-flex" role="group" aria-label="Casillas {{ strtoupper($dim) }}">
                                        <button type="button" class="btn btn-sm btn-light border rounded-circle py-0 px-2 mr-1 col-btn" data-dim="{{ $dim }}" data-action="remove" title="Descartar la última casilla {{ strtoupper($dim) }}" aria-label="Descartar la última casilla {{ strtoupper($dim) }}" @disabled($columns[$dim] <= 1)><i class="fas fa-minus" aria-hidden="true"></i></button>
                                        <button type="button" class="btn btn-sm btn-light border rounded-circle py-0 px-2 col-btn" data-dim="{{ $dim }}" data-action="add" title="Agregar otra casilla {{ strtoupper($dim) }}" aria-label="Agregar otra casilla {{ strtoupper($dim) }}" @disabled($columns[$dim] >= $dimMax[$dim])><i class="fas fa-plus" aria-hidden="true"></i></button>
                                    </span>
                                @endif
                            </th>
                            <th rowspan="2" class="text-center align-middle vertical-text prom-{{ $dim }}">PROMEDIO<br>DIMENSIÓN<br>({{ $pts }} PTS)</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($dimMax as $dim => $max)
                                @for($i = 1; $i <= $max; $i++)
                                <th class="text-center dim-col {{ $i > $columns[$dim] ? 'd-none' : '' }}" data-dim="{{ $dim }}" data-col="{{ $i }}">{{ $dimLabels[$dim] }} {{ $i }}@if($dim === 'hacer') <span class="robot-hint" title="Se completa con la actividad del robot YURA"><i class="fas fa-robot"></i></span>@endif</th>
                                @endfor
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                        @php
                            // Mismo cálculo que la vista de notas trimestrales (App\Services\CuadernoNota):
                            // SER 10, SABER 45 y HACER 40 pts (notas 1-100 escaladas) + AUTOEVALUACIÓN sobre 5.
                            $nota = \App\Services\CuadernoNota::summarize($gradesByStudent[$student->id] ?? null);
                            ['ser' => $ser, 'saber' => $saber, 'hacer' => $hacer, 'autoeval' => $autoeval] = $nota;
                            $promSer = $nota['prom_ser'];
                            $promSaber = $nota['prom_saber'];
                            $promHacer = $nota['prom_hacer'];
                            $total = $nota['total'];
                            $notaBimestral = $nota['nota'];
                        @endphp
                        <tr data-student-id="{{ $student->id }}">
                            <td class="text-center align-middle">{{ method_exists($students, 'firstItem') ? $students->firstItem() + $loop->index : $loop->iteration }}</td>
                            <td class="px-4 align-middle">
                                <div class="d-flex align-items-center">
                                    @if($student->foto_path)
                                        <img src="{{ asset('storage/' . $student->foto_path) }}" alt="{{ $student->nombre }}" class="rounded-circle mr-3" style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-3" style="width:40px;height:40px;">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                    @endif
                                    <span class="font-weight-bold">{{ $student->name ?? $student->nombre }}</span>
                                </div>
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-quechua-gold px-3">{{ mb_strtoupper($student->level ?? $student->nivel ?? '6TO', 'UTF-8') }}</span>
                            </td>
                            {{-- SER (3) --}}
                            @foreach($ser as $n => $value)
                            <td class="text-center align-middle dim-col {{ $n > $columns['ser'] ? 'd-none' : '' }}" data-dim="ser" data-col="{{ $n }}">
                                <input type="number" step="1" min="1" max="100" inputmode="numeric" class="form-control form-control-sm text-center grade-input mx-auto" style="width:60px;" data-student="{{ $student->id }}" data-type="activity" data-number="{{ $n }}" data-dimension="ser" value="{{ $value }}" {{ auth()->user()->isProfesor() ? '' : 'disabled' }}>
                            </td>
                            @endforeach
                            <td class="text-center font-weight-bold align-middle dim-cell dim-ser">{{ $promSer ?? '-' }}</td>
                            {{-- SABER (5) --}}
                            @foreach($saber as $n => $value)
                            <td class="text-center align-middle dim-col {{ $n > $columns['saber'] ? 'd-none' : '' }}" data-dim="saber" data-col="{{ $n }}">
                                <input type="number" step="1" min="1" max="100" inputmode="numeric" class="form-control form-control-sm text-center grade-input mx-auto" style="width:60px;" data-student="{{ $student->id }}" data-type="exam" data-number="{{ $n }}" data-dimension="saber" value="{{ $value }}" {{ auth()->user()->isProfesor() ? '' : 'disabled' }}>
                            </td>
                            @endforeach
                            <td class="text-center font-weight-bold align-middle dim-cell dim-saber">{{ $promSaber ?? '-' }}</td>
                            {{-- HACER (5) - se completa normalmente con el resultado de la actividad del robot --}}
                            @foreach($hacer as $n => $value)
                            <td class="text-center align-middle dim-col {{ $n > $columns['hacer'] ? 'd-none' : '' }}" data-dim="hacer" data-col="{{ $n }}">
                                <input type="number" step="1" min="1" max="100" inputmode="numeric" class="form-control form-control-sm text-center grade-input mx-auto" style="width:60px;" data-student="{{ $student->id }}" data-type="robot" data-number="{{ $n }}" data-dimension="hacer" value="{{ $value }}" placeholder="🤖" disabled title="Se completa automáticamente con el resultado de una actividad Yura.">
                            </td>
                            @endforeach
                            <td class="text-center font-weight-bold align-middle dim-cell dim-hacer">{{ $promHacer ?? '-' }}</td>
                            <td class="text-center font-weight-bold align-middle total-cell">{{ $total ?? '-' }}</td>
                            <td class="text-center align-middle">
                                <input type="number" step="1" min="1" max="5" inputmode="numeric" class="form-control form-control-sm text-center grade-input mx-auto" style="width:60px;" data-student="{{ $student->id }}" data-type="autoeval" data-number="1" data-dimension="autoeval" value="{{ $autoeval }}" {{ auth()->user()->isProfesor() ? '' : 'disabled' }}>
                            </td>
                            <td class="text-center font-weight-bold align-middle nota-bimestral-cell">{{ $notaBimestral ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ 9 + $colsTotal }}" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-2 opacity-25"></i>
                                    <p>{{ __('messages.no_students') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.list-footer', ['paginator' => $students, 'noun' => 'estudiantes'])
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '{{ csrf_token() }}';
        const gradeInputs = document.querySelectorAll('.grade-input');
        const gradeSaveUrl = "{{ route('grades.save') }}";

        const styleTag = document.createElement('style');
        styleTag.textContent = `
            .grade-input.grade-saving { border-color: #ffcc00 !important; box-shadow: 0 0 0 0.2rem rgba(255, 204, 0, 0.25) !important; }
            .grade-input.grade-saved { border-color: #28a745 !important; box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important; transition: border-color .2s ease, box-shadow .2s ease; }
            .grade-input.grade-save-error { border-color: #C8102E !important; box-shadow: 0 0 0 0.2rem rgba(200, 16, 46, 0.25) !important; }
        `;
        document.head.appendChild(styleTag);

        // Casillas SER / SABER / HACER: «+» agrega y «−» descarta la última (solo el profesor; se guarda en el servidor).
        (function () {
            const table = document.getElementById('gradesTable');
            if (!table || !table.dataset.columnsUrl) return;

            function apply(dim, count) {
                const group = document.getElementById('grupo-' + dim);
                if (!group) return;
                const max = parseInt(group.dataset.max, 10);
                group.dataset.count = count;
                group.colSpan = count;
                table.querySelectorAll('.dim-col[data-dim="' + dim + '"]').forEach(cell => {
                    cell.classList.toggle('d-none', parseInt(cell.dataset.col, 10) > count);
                });
                group.querySelector('[data-action="remove"]').disabled = count <= 1;
                group.querySelector('[data-action="add"]').disabled = count >= max;
                const total = ['ser', 'saber', 'hacer']
                    .reduce((sum, d) => sum + (parseInt(document.getElementById('grupo-' + d)?.dataset.count, 10) || 0), 0);
                const banner = document.getElementById('banner-evaluacion');
                if (banner) banner.colSpan = 3 + total;
            }

            // Ventana de confirmación de YURA (public/js/yura-ui.js) mediante un formulario oculto.
            const confirmForm = document.createElement('form');
            confirmForm.className = 'd-none';
            confirmForm.dataset.confirmTitle = '¿Descartar la casilla?';
            confirmForm.dataset.confirmOk = 'Descartar y borrar notas';
            confirmForm.innerHTML = '<input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(confirmForm);
            let onConfirmed = null;
            confirmForm.addEventListener('submit', function (event) {
                event.preventDefault();
                confirmForm.dataset.confirmed = '';
                if (onConfirmed) { const run = onConfirmed; onConfirmed = null; run(); }
            });
            function askConfirm(message, run) {
                if (!window.jQuery) { if (window.confirm(message)) run(); return; }
                confirmForm.dataset.confirm = message;
                onConfirmed = run;
                confirmForm.requestSubmit ? confirmForm.requestSubmit() : confirmForm.dispatchEvent(new Event('submit', { cancelable: true }));
            }

            async function send(button, confirmed) {
                const dim = button.dataset.dim;
                const restore = () => apply(dim, parseInt(document.getElementById('grupo-' + dim).dataset.count, 10));
                button.disabled = true;
                try {
                    const response = await fetch(table.dataset.columnsUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({
                            subject_id: table.dataset.subject, parallel_id: table.dataset.parallel,
                            quarter: table.dataset.quarter, dimension: dim, action: button.dataset.action, confirm: confirmed,
                        }),
                    });
                    const data = await response.json().catch(() => null);
                    if (response.status === 409 && data?.needs_confirm) {
                        restore();
                        askConfirm(data.message, () => send(button, true));
                        return;
                    }
                    if (!response.ok || !data?.success) {
                        showSaveToast(data?.message || 'No se pudo cambiar las casillas.', 'error');
                        restore();
                        return;
                    }
                    if (confirmed) {
                        // Se borraron notas: recargar para ver los promedios recalculados.
                        location.reload();
                        return;
                    }
                    apply(dim, data.count);
                    showSaveToast(button.dataset.action === 'add' ? '✓ Casilla agregada' : '✓ Casilla descartada');
                } catch (error) {
                    showSaveToast('Error de conexión: no se cambiaron las casillas.', 'error');
                    restore();
                }
            }

            table.querySelectorAll('.col-btn').forEach(button => {
                button.addEventListener('click', () => send(button, false));
            });
        })();

        function parseGradeValue(value) {
            const parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : null;
        }

        // Puntos máximos por dimensión del cuaderno SER/SABER/HACER (mismo criterio que el cálculo inicial en el servidor).
        const DIMENSION_POINTS = { ser: 10, saber: 45, hacer: 40 };

        function scaleToDimension(row, dimension, maxPoints) {
            const values = Array.from(row.querySelectorAll(`.grade-input[data-dimension="${dimension}"]`))
                .map(input => parseGradeValue(input.value))
                .filter(value => value !== null);
            if (!values.length) {
                return null;
            }
            const average = values.reduce((sum, value) => sum + value, 0) / values.length;
            return Math.round(((average / 100) * maxPoints) * 100) / 100;
        }

        function recalcRowAverage(row) {
            const promSer = scaleToDimension(row, 'ser', DIMENSION_POINTS.ser);
            const promSaber = scaleToDimension(row, 'saber', DIMENSION_POINTS.saber);
            const promHacer = scaleToDimension(row, 'hacer', DIMENSION_POINTS.hacer);

            const serCell = row.querySelector('.dim-ser');
            const saberCell = row.querySelector('.dim-saber');
            const hacerCell = row.querySelector('.dim-hacer');
            if (serCell) serCell.textContent = promSer === null ? '-' : promSer.toFixed(2);
            if (saberCell) saberCell.textContent = promSaber === null ? '-' : promSaber.toFixed(2);
            if (hacerCell) hacerCell.textContent = promHacer === null ? '-' : promHacer.toFixed(2);

            const dimensionValues = [promSer, promSaber, promHacer].filter(v => v !== null);
            const total = dimensionValues.length ? Math.round(dimensionValues.reduce((s, v) => s + v, 0) * 100) / 100 : null;
            const totalCell = row.querySelector('.total-cell');
            if (totalCell) totalCell.textContent = total === null ? '-' : total.toFixed(2);

            const autoevalInput = row.querySelector('.grade-input[data-dimension="autoeval"]');
            const autoeval = autoevalInput ? parseGradeValue(autoevalInput.value) : null;
            const notaBimestralCell = row.querySelector('.nota-bimestral-cell');
            if (notaBimestralCell) {
                if (total === null && autoeval === null) {
                    notaBimestralCell.textContent = '-';
                } else {
                    const nota = Math.round(((total ?? 0) + (autoeval ?? 0)) * 100) / 100;
                    notaBimestralCell.textContent = nota.toFixed(2);
                }
            }
        }

        const showSaveToast = (message, type) => window.YuraToast.show(message, type);

        // Límites por columna: SER/SABER sobre 100, autoevaluación sobre 5. Siempre enteros desde 1.
        function maxFor(input) {
            return input.dataset.type === 'autoeval' ? 5 : 100;
        }

        function validationError(input) {
            const raw = input.value.trim();
            const max = maxFor(input);
            if (raw === '') return null;
            if (!/^\d+$/.test(raw)) return 'La nota debe ser un número entero, sin decimales ni signos.';
            const value = Number(raw);
            if (value < 1 || value > max) return `La nota debe estar entre 1 y ${max}.`;
            return null;
        }

        function clearGradeState(input) {
            if (input._gradeSaveTimer) {
                clearTimeout(input._gradeSaveTimer);
                input._gradeSaveTimer = null;
            }
            input.classList.remove('grade-saving', 'grade-saved', 'grade-save-error');
        }

        function markInputProcessing(input) {
            clearGradeState(input);
            input.classList.add('grade-saving');
        }

        function markInputSaved(input) {
            clearGradeState(input);
            input.classList.add('grade-saved');
            input._gradeSaveTimer = setTimeout(() => clearGradeState(input), 1500);
        }

        function markInputError(input) {
            clearGradeState(input);
            input.classList.add('grade-save-error');
            input._gradeSaveTimer = setTimeout(() => clearGradeState(input), 3000);
        }

        // Vuelve a la última nota guardada (las notas no se borran desde el cuaderno).
        function revert(input) {
            input.value = input.dataset.saved ?? '';
            const row = input.closest('tr');
            if (row) recalcRowAverage(row);
        }

        async function saveGrade(input) {
            const error = validationError(input);
            if (error) {
                markInputError(input);
                showSaveToast(error, 'error');
                revert(input);
                return;
            }
            if (input.value.trim() === '') {
                if (input.dataset.saved) {
                    showSaveToast('La nota no se puede dejar vacía; se restauró la anterior.', 'error');
                }
                revert(input);
                return;
            }
            if (input.value.trim() === (input.dataset.saved ?? '')) {
                return;
            }

            markInputProcessing(input);

            try {
                const response = await fetch(gradeSaveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        student_id: input.dataset.student,
                        subject_id: '{{ $selectedSubjectId }}',
                        type: input.dataset.type,
                        activity_number: input.dataset.number,
                        quarter: '{{ $selectedQuarter }}',
                        score: Number(input.value),
                    }),
                });

                const data = await response.json().catch(() => null);
                if (!response.ok || !data?.success) {
                    const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                    markInputError(input);
                    showSaveToast(firstError || data?.message || 'No se pudo guardar la nota.', 'error');
                    revert(input);
                    return;
                }

                input.dataset.saved = input.value.trim();
                markInputSaved(input);
                showSaveToast('✓ Cambios guardados');
            } catch (error) {
                markInputError(input);
                showSaveToast('Error de conexión: la nota no se guardó.', 'error');
                revert(input);
            }
        }

        gradeInputs.forEach(input => {
            // Muestra "87.00" como "87" (las notas antiguas con decimales reales se dejan tal cual).
            if (input.value !== '' && Number.isInteger(Number(input.value))) {
                input.value = String(Number(input.value));
            }
            input.dataset.saved = input.value;

            const row = input.closest('tr');
            if (row) {
                recalcRowAverage(row);
            }

            // Bloquea signos, decimales y notación científica desde el teclado.
            input.addEventListener('keydown', function (event) {
                if (['-', '+', 'e', 'E', '.', ','].includes(event.key)) {
                    event.preventDefault();
                }
            });
            input.addEventListener('input', function () {
                const digits = this.value.replace(/\D/g, '').slice(0, 3);
                if (digits !== this.value) this.value = digits;
                const row = this.closest('tr');
                if (row) recalcRowAverage(row);
            });
            input.addEventListener('change', function () {
                saveGrade(this);
            });
        });
    });
</script>

@endsection
