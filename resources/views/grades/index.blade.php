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
                <div class="table-responsive table-scroll-box">
                    <table class="table table-hover align-middle table-bordered cuaderno-ser-saber-hacer" id="gradesTable">
                    <thead>
                        <tr>
                            <th rowspan="3" class="px-3 text-center align-middle" title="Número en la lista del curso y paralelo">Nro</th>
                            <th rowspan="3" class="px-4 align-middle">{{ __('messages.student_name') }}</th>
                            <th rowspan="3" class="align-middle">{{ __('messages.level') }}</th>
                            <th colspan="16" class="text-center banner-evaluacion">EVALUACIÓN MAESTRO(A)</th>
                            <th rowspan="3" class="text-center align-middle col-total">TOTAL<br>(90 PTS)</th>
                            <th rowspan="3" class="text-center align-middle col-autoeval">AUTOEVALUACIÓN<br>TOTAL (5 PTS)</th>
                            <th rowspan="3" class="text-center align-middle col-nota">NOTA<br>BIMESTRAL<br>(100 PTS)</th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-center grupo-ser">SER</th>
                            <th rowspan="2" class="text-center align-middle vertical-text prom-ser">PROMEDIO<br>DIMENSIÓN<br>(10 PTS)</th>
                            <th colspan="5" class="text-center grupo-saber">SABER</th>
                            <th rowspan="2" class="text-center align-middle vertical-text prom-saber">PROMEDIO<br>DIMENSIÓN<br>(45 PTS)</th>
                            <th colspan="5" class="text-center grupo-hacer">HACER</th>
                            <th rowspan="2" class="text-center align-middle vertical-text prom-hacer">PROMEDIO<br>DIMENSIÓN<br>(40 PTS)</th>
                        </tr>
                        <tr>
                            <th class="text-center">Ser 1</th>
                            <th class="text-center">Ser 2</th>
                            <th class="text-center">Ser 3</th>
                            <th class="text-center">Saber 1</th>
                            <th class="text-center">Saber 2</th>
                            <th class="text-center">Saber 3</th>
                            <th class="text-center">Saber 4</th>
                            <th class="text-center">Saber 5</th>
                            <th class="text-center">Hacer 1 <span class="robot-hint" title="Se completa con la actividad del robot YURA"><i class="fas fa-robot"></i></span></th>
                            <th class="text-center">Hacer 2 <span class="robot-hint" title="Se completa con la actividad del robot YURA"><i class="fas fa-robot"></i></span></th>
                            <th class="text-center">Hacer 3 <span class="robot-hint" title="Se completa con la actividad del robot YURA"><i class="fas fa-robot"></i></span></th>
                            <th class="text-center">Hacer 4 <span class="robot-hint" title="Se completa con la actividad del robot YURA"><i class="fas fa-robot"></i></span></th>
                            <th class="text-center">Hacer 5 <span class="robot-hint" title="Se completa con la actividad del robot YURA"><i class="fas fa-robot"></i></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Dimensiones del cuaderno pedagógico (SER/SABER/HACER) y sus puntos máximos.
                            // Cada nota se ingresa en escala 0-100 y se escala proporcionalmente a los
                            // puntos de su dimensión; AUTOEVALUACIÓN se ingresa directamente sobre 5.
                            $dimensionPoints = ['ser' => 10, 'saber' => 45, 'hacer' => 40];
                            $scaleToDimension = function (array $scores, int $maxPoints) {
                                $numeric = array_values(array_filter($scores, fn ($v) => is_numeric($v) && $v !== ''));
                                if (empty($numeric)) {
                                    return null;
                                }
                                $avg = array_sum($numeric) / count($numeric);
                                return round(($avg / 100) * $maxPoints, 2);
                            };
                        @endphp
                        @forelse($students as $student)
                        @php
                            $studentGrades = $gradesByStudent[$student->id] ?? null;

                            $ser = [1 => data_get($studentGrades, 'activity.1.score', ''), 2 => data_get($studentGrades, 'activity.2.score', ''), 3 => data_get($studentGrades, 'activity.3.score', '')];
                            $saber = [1 => data_get($studentGrades, 'exam.1.score', ''), 2 => data_get($studentGrades, 'exam.2.score', ''), 3 => data_get($studentGrades, 'exam.3.score', ''), 4 => data_get($studentGrades, 'exam.4.score', ''), 5 => data_get($studentGrades, 'exam.5.score', '')];
                            $hacer = [1 => data_get($studentGrades, 'robot.1.score', ''), 2 => data_get($studentGrades, 'robot.2.score', ''), 3 => data_get($studentGrades, 'robot.3.score', ''), 4 => data_get($studentGrades, 'robot.4.score', ''), 5 => data_get($studentGrades, 'robot.5.score', '')];
                            $autoeval = data_get($studentGrades, 'autoeval.1.score', '');

                            $promSer = $scaleToDimension($ser, $dimensionPoints['ser']);
                            $promSaber = $scaleToDimension($saber, $dimensionPoints['saber']);
                            $promHacer = $scaleToDimension($hacer, $dimensionPoints['hacer']);

                            $dimensionesLlenas = array_filter([$promSer, $promSaber, $promHacer], fn ($v) => $v !== null);
                            $total = count($dimensionesLlenas) ? round(array_sum($dimensionesLlenas), 2) : null;
                            $notaBimestral = ($total !== null || is_numeric($autoeval))
                                ? round(($total ?? 0) + (is_numeric($autoeval) ? $autoeval : 0), 2)
                                : null;
                        @endphp
                        <tr data-student-id="{{ $student->id }}">
                            <td class="text-center align-middle">{{ $loop->iteration }}</td>
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
                            <td class="text-center align-middle">
                                <input type="number" step="1" min="1" max="100" inputmode="numeric" class="form-control form-control-sm text-center grade-input mx-auto" style="width:60px;" data-student="{{ $student->id }}" data-type="activity" data-number="{{ $n }}" data-dimension="ser" value="{{ $value }}" {{ auth()->user()->isProfesor() ? '' : 'disabled' }}>
                            </td>
                            @endforeach
                            <td class="text-center font-weight-bold align-middle dim-cell dim-ser">{{ $promSer ?? '-' }}</td>
                            {{-- SABER (5) --}}
                            @foreach($saber as $n => $value)
                            <td class="text-center align-middle">
                                <input type="number" step="1" min="1" max="100" inputmode="numeric" class="form-control form-control-sm text-center grade-input mx-auto" style="width:60px;" data-student="{{ $student->id }}" data-type="exam" data-number="{{ $n }}" data-dimension="saber" value="{{ $value }}" {{ auth()->user()->isProfesor() ? '' : 'disabled' }}>
                            </td>
                            @endforeach
                            <td class="text-center font-weight-bold align-middle dim-cell dim-saber">{{ $promSaber ?? '-' }}</td>
                            {{-- HACER (5) - se completa normalmente con el resultado de la actividad del robot --}}
                            @foreach($hacer as $n => $value)
                            <td class="text-center align-middle">
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
                            <td colspan="22" class="text-center py-5">
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
