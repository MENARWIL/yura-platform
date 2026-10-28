@extends('layouts.app')

@section('title', 'Asistencia')

@section('content')
@php
    $canEdit = auth()->user()->isProfesor();
    // Letra del botón y nombre visible de cada estado (el valor guardado no cambia).
    $statusButtons = [
        'Presente' => ['P', 'Presente'],
        'Falta' => ['F', 'Falta'],
        'Atraso' => ['R', 'Retraso'],
        'Licencia' => ['L', 'Licencia / permiso'],
    ];
@endphp
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-calendar-check mr-2"></i> Asistencia
            </h3>
        </div>

        <div class="card-body bg-white p-4">
            @if($errors->has('date_from') || $errors->has('date_to'))
                <div class="alert alert-danger mb-4" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    No se pudo exportar: {{ $errors->first('date_from') ?: $errors->first('date_to') }}
                </div>
            @endif
            @unless($canEdit)
                <div class="alert alert-info mb-4" role="alert">
                    <i class="fas fa-info-circle mr-2"></i>
                    Modo solo lectura: solo el profesor asignado puede registrar la asistencia.
                </div>
            @endunless
            <!-- FILTROS: la página solo se recarga cuando asignatura y paralelo están elegidos. -->
            <form method="GET" action="{{ route('attendance.index') }}" class="filter-bar" aria-label="Filtros de asistencia" data-filter-required="subject_id,parallel_id,date">
                <div class="fb-field {{ $selectedSubjectId ? 'is-set' : '' }}">
                    <label for="at-subject"><i class="fas fa-book-open" aria-hidden="true"></i> Asignatura</label>
                    <select id="at-subject" name="subject_id">
                        <option value="">Elige una asignatura</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $selectedSubjectId == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-field {{ $selectedParallelId ? 'is-set' : '' }}">
                    <label for="at-parallel"><i class="fas fa-users" aria-hidden="true"></i> Curso y paralelo</label>
                    <select id="at-parallel" name="parallel_id">
                        <option value="">Elige curso y paralelo</option>
                        @foreach($parallels as $parallel)
                            <option value="{{ $parallel->id }}" {{ $selectedParallelId == $parallel->id ? 'selected' : '' }}>
                                {{ $parallel->course->name }} - {{ $parallel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-field fb-narrow is-set">
                    <label for="at-date"><i class="fas fa-calendar-day" aria-hidden="true"></i> Fecha</label>
                    <input id="at-date" type="date" name="date" value="{{ $selectedDate }}" max="{{ now()->toDateString() }}" required>
                </div>
                @if($selectedSubjectId || $selectedParallelId)
                    <div class="fb-actions">
                        <a href="{{ route('attendance.index', ['date' => $selectedDate]) }}" class="fb-btn fb-btn-ghost" title="Quitar asignatura y paralelo">
                            <i class="fas fa-undo" aria-hidden="true"></i> Limpiar
                        </a>
                        @if($selectedSubjectId && $selectedParallelId)
                            <button type="button" class="fb-btn fb-btn-outline" data-toggle="modal" data-target="#exportAttendanceModal">
                                <i class="fas fa-file-pdf" aria-hidden="true"></i> Exportar PDF
                            </button>
                        @endif
                    </div>
                @endif
                <p class="fb-hint" data-filter-hint hidden>
                    <i class="fas fa-info-circle" aria-hidden="true"></i> Elige asignatura y paralelo; la lista se carga sola al completar los filtros.
                </p>
            </form>

            @if(empty($selectedSubjectId) || empty($selectedParallelId))
                <div class="card border-warning shadow-sm my-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                        <h5 class="font-weight-bold">Seleccione una asignatura y un paralelo para comenzar.</h5>
                        <p class="text-muted mb-0">La asistencia se registra dentro de la asignación docente seleccionada, para la fecha indicada arriba.</p>
                        @if($subjects->isEmpty() || $parallels->isEmpty())
                            <p class="text-danger mt-3 mb-0">No tienes asignaciones docentes activas disponibles.</p>
                        @endif
                    </div>
                </div>
            @else
                @php $pendingCount = $students->filter(fn ($s) => ! isset($attendanceForDate[$s->id]))->count(); @endphp
                @if($students->isNotEmpty())
                <div class="att-summary" id="att-summary">
                    <div>
                        <div class="att-legend" aria-label="Resumen del día">
                            <span style="--c:#1f9d55">Presentes: <b data-count="Presente">0</b></span>
                            <span style="--c:var(--yura-red)">Faltas: <b data-count="Falta">0</b></span>
                            <span style="--c:var(--yura-amber)">Retrasos: <b data-count="Atraso">0</b></span>
                            <span style="--c:var(--yura-action)">Licencias: <b data-count="Licencia">0</b></span>
                        </div>
                        @if($canEdit)
                            <small class="text-muted d-block mt-1" id="att-pending-text" @if(! $pendingCount) hidden @endif>
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                <span id="att-pending-label">{{ $pendingCount }} sin registrar</span>: aparecen como Presente. Cambia solo a quienes faltaron y presiona «Registrar asistencia».
                            </small>
                        @endif
                    </div>
                    @if($canEdit)
                        <button type="button" class="fb-btn fb-btn-primary" id="att-register" @if(! $pendingCount) hidden @endif>
                            <i class="fas fa-check" aria-hidden="true"></i> Registrar asistencia
                        </button>
                    @endif
                </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-bordered" id="attendance-table">
                        <thead>
                            <tr>
                                <th class="px-3 text-center align-middle" title="Número en la lista del curso y paralelo">Nro</th>
                                <th class="px-4 align-middle">Estudiante</th>
                                <th class="align-middle">Nivel</th>
                                <th class="align-middle text-center" style="width: 220px;">Asistencia ({{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                            @php
                                $savedStatus = optional($attendanceForDate[$student->id] ?? null)->status;
                                // Sin registro: el profesor ve Presente marcado por defecto (aún sin guardar).
                                $shownStatus = $savedStatus ?? ($canEdit ? 'Presente' : null);
                            @endphp
                            <tr data-student-id="{{ $student->id }}" data-status="{{ $shownStatus }}" class="{{ $savedStatus ? '' : 'att-row-pending' }}" @if(! $savedStatus) data-pending="1" @endif>
                                <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                <td class="px-4 align-middle">
                                    <div class="d-flex align-items-center">
                                        @if($student->foto_path)
                                            <img src="{{ asset('storage/' . $student->foto_path) }}" alt="{{ $student->name }}" class="rounded-circle mr-3" style="width:40px;height:40px;object-fit:cover;">
                                        @else
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-3" style="width:40px;height:40px;">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                        @endif
                                        <span class="font-weight-bold">{{ $student->name }}</span>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-quechua-gold px-3">{{ mb_strtoupper($student->level ?? '6TO', 'UTF-8') }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <div class="att-group" role="radiogroup" aria-label="Asistencia de {{ $student->name }}">
                                        @foreach($statusButtons as $value => [$letter, $label])
                                            <button type="button" class="att-btn {{ $shownStatus === $value ? 'is-active' : '' }}" data-status="{{ $value }}"
                                                role="radio" aria-checked="{{ $shownStatus === $value ? 'true' : 'false' }}" title="{{ $label }}" aria-label="{{ $label }}"
                                                {{ $canEdit ? '' : 'disabled' }}>{{ $letter }}</button>
                                        @endforeach
                                    </div>
                                    @if(! $savedStatus && ! $canEdit)
                                        <small class="d-block text-muted mt-1">Sin registro</small>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-2 opacity-25"></i>
                                        <p>No hay estudiantes para mostrar.</p>
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

@if($selectedSubjectId && $selectedParallelId)
@php
    $today = now();
    $exportPresets = [
        'Hoy' => [$today->toDateString(), $today->toDateString()],
        'Esta semana' => [$today->copy()->startOfWeek()->toDateString(), $today->toDateString()],
        'Este mes' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
        'Este año' => [$today->copy()->startOfYear()->toDateString(), $today->toDateString()],
    ];
@endphp
<div class="modal fade" id="exportAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="exportAttendanceTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="GET" action="{{ route('attendance.export.pdf') }}" id="export-attendance-form">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="exportAttendanceTitle">
                        <i class="fas fa-file-pdf text-danger mr-2" aria-hidden="true"></i> Exportar asistencia en PDF
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="subject_id" value="{{ $selectedSubjectId }}">
                    <input type="hidden" name="parallel_id" value="{{ $selectedParallelId }}">
                    <p class="text-muted small mb-3">
                        {{ $subjects->firstWhere('id', $selectedSubjectId)?->name }} ·
                        @php $exportParallel = $parallels->firstWhere('id', $selectedParallelId); @endphp
                        {{ $exportParallel ? $exportParallel->course->name . ' - ' . $exportParallel->name : '' }}
                    </p>
                    <div class="d-flex flex-wrap mb-3" style="gap: 6px;" aria-label="Periodos rápidos">
                        @foreach($exportPresets as $label => [$from, $to])
                            <button type="button" class="btn btn-sm btn-outline-primary export-preset" data-from="{{ $from }}" data-to="{{ $to }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="export-date-from" class="font-weight-bold small">Desde</label>
                            <input type="date" class="form-control" id="export-date-from" name="date_from" value="{{ $today->copy()->startOfMonth()->toDateString() }}" max="{{ $today->toDateString() }}" required>
                        </div>
                        <div class="form-group col-6">
                            <label for="export-date-to" class="font-weight-bold small">Hasta</label>
                            <input type="date" class="form-control" id="export-date-to" name="date_to" value="{{ $selectedDate }}" max="{{ $today->toDateString() }}" required>
                        </div>
                    </div>
                    <p class="text-danger small mb-0" id="export-date-error" hidden></p>
                    <p class="text-muted small mb-0">
                        Hasta un mes sale el detalle día por día; con más días, el total por estudiante.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-download mr-1" aria-hidden="true"></i> Descargar PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('export-attendance-form');
        const from = document.getElementById('export-date-from');
        const to = document.getElementById('export-date-to');
        const error = document.getElementById('export-date-error');
        if (!form) return;

        form.querySelectorAll('.export-preset').forEach(button => {
            button.addEventListener('click', function () {
                from.value = this.dataset.from;
                to.value = this.dataset.to;
                error.hidden = true;
            });
        });
        form.addEventListener('submit', function (event) {
            if (from.value && to.value && from.value > to.value) {
                event.preventDefault();
                error.textContent = 'La fecha «Desde» debe ser anterior o igual a «Hasta».';
                error.hidden = false;
                return;
            }
            error.hidden = true;
            // La descarga empieza sola; se cierra la ventana para seguir trabajando.
            setTimeout(() => window.jQuery && window.jQuery('#exportAttendanceModal').modal('hide'), 300);
        });
    });
</script>
@endif

@if($canEdit && $selectedSubjectId && $selectedParallelId)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '{{ csrf_token() }}';
        const saveUrl = "{{ route('attendance.save') }}";
        const saveListUrl = "{{ route('attendance.saveList') }}";
        const subjectId = '{{ $selectedSubjectId }}';
        const date = '{{ $selectedDate }}';
        const table = document.getElementById('attendance-table');
        const registerButton = document.getElementById('att-register');
        const pendingText = document.getElementById('att-pending-text');
        const pendingLabel = document.getElementById('att-pending-label');
        if (!table) return;

        const rows = () => Array.from(table.querySelectorAll('tbody tr[data-student-id]'));
        const pendingRows = () => rows().filter(row => row.dataset.pending === '1');
        // Mientras nadie de la lista tenga registro, los clics solo preparan la lista;
        // cuando ya hay registros del día, cada clic se guarda al instante.
        const isDraftList = () => pendingRows().length === rows().length;

        function paint(row, status) {
            row.dataset.status = status;
            row.querySelectorAll('.att-btn').forEach(button => {
                const active = button.dataset.status === status;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-checked', active ? 'true' : 'false');
            });
        }

        function refreshSummary() {
            const counts = { Presente: 0, Falta: 0, Atraso: 0, Licencia: 0 };
            rows().forEach(row => { if (counts[row.dataset.status] !== undefined) counts[row.dataset.status]++; });
            Object.keys(counts).forEach(key => {
                const el = document.querySelector(`[data-count="${key}"]`);
                if (el) el.textContent = counts[key];
            });
            const pending = pendingRows().length;
            if (registerButton) registerButton.hidden = pending === 0;
            if (pendingText) pendingText.hidden = pending === 0;
            if (pendingLabel) pendingLabel.textContent = `${pending} sin registrar`;
        }

        function markSaved(row) {
            delete row.dataset.pending;
            row.classList.remove('att-row-pending');
        }

        async function post(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(() => null);
            if (!response.ok || (data && data.success === false)) {
                const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                throw new Error(firstError || data?.message || 'No se pudo guardar la asistencia.');
            }
            return data;
        }

        table.addEventListener('click', async function (event) {
            const button = event.target.closest('.att-btn');
            if (!button || button.disabled) return;
            const row = button.closest('tr');
            const previous = row.dataset.status;
            const status = button.dataset.status;
            if (status === previous && row.dataset.pending !== '1') return;

            paint(row, status);
            if (isDraftList()) {
                refreshSummary();
                return;
            }

            refreshSummary();
            try {
                await post(saveUrl, { student_id: row.dataset.studentId, subject_id: subjectId, status: status, date: date });
                markSaved(row);
                refreshSummary();
                YuraToast.show('✓ Asistencia guardada');
            } catch (error) {
                paint(row, previous);
                refreshSummary();
                YuraToast.show(error.message, 'error');
            }
        });

        if (registerButton) {
            registerButton.addEventListener('click', async function () {
                const pending = pendingRows();
                if (!pending.length) return;
                const original = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Registrando...';
                try {
                    await post(saveListUrl, {
                        subject_id: subjectId,
                        date: date,
                        records: pending.map(row => ({ student_id: row.dataset.studentId, status: row.dataset.status })),
                    });
                    pending.forEach(markSaved);
                    YuraToast.show(`✓ Asistencia registrada (${pending.length} estudiantes)`);
                } catch (error) {
                    YuraToast.show(error.message, 'error');
                } finally {
                    this.disabled = false;
                    this.innerHTML = original;
                    refreshSummary();
                }
            });
        }

        refreshSummary();
    });
</script>
@elseif($selectedSubjectId && $selectedParallelId)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const counts = { Presente: 0, Falta: 0, Atraso: 0, Licencia: 0 };
        document.querySelectorAll('#attendance-table tbody tr[data-status]').forEach(row => {
            if (counts[row.dataset.status] !== undefined) counts[row.dataset.status]++;
        });
        Object.keys(counts).forEach(key => {
            const el = document.querySelector(`[data-count="${key}"]`);
            if (el) el.textContent = counts[key];
        });
    });
</script>
@endif
@endsection
