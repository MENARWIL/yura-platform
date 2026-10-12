@extends('layouts.app')

@section('title', 'Asistencia')

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-calendar-check mr-2"></i> Asistencia
            </h3>
        </div>

        <div class="card-body bg-white p-4">
            @unless(auth()->user()->isProfesor())
                <div class="alert alert-info mb-4" role="alert">
                    <i class="fas fa-info-circle mr-2"></i>
                    Modo solo lectura: solo el profesor asignado puede registrar la asistencia.
                </div>
            @endunless
            <!-- FILTROS -->
            <div class="form-group mb-4">
                <label class="text-muted small text-uppercase font-weight-bold">Seleccionar asignatura, paralelo y fecha</label>
                <form method="GET" action="{{ route('attendance.index') }}" class="d-flex flex-wrap align-items-center">
                    <select name="subject_id" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 mr-2 mb-2" onchange="this.form.submit()">
                        <option value="">Todas las asignaturas</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $selectedSubjectId == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="parallel_id" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 mr-2 mb-2" onchange="this.form.submit()">
                        <option value="">Todos los paralelos</option>
                        @foreach($parallels as $parallel)
                            <option value="{{ $parallel->id }}" {{ $selectedParallelId == $parallel->id ? 'selected' : '' }}>
                                {{ $parallel->course->name }} - {{ $parallel->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="date" name="date" value="{{ $selectedDate }}" max="{{ now()->toDateString() }}" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 mr-2 mb-2" onchange="this.form.submit()">
                    @if($selectedSubjectId && $selectedParallelId)
                        <a href="{{ route('attendance.export.pdf', ['subject_id' => $selectedSubjectId, 'parallel_id' => $selectedParallelId, 'date' => $selectedDate]) }}" class="btn btn-outline-danger btn-lg rounded-pill px-4 mb-2">
                            <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
                        </a>
                    @endif
                </form>
            </div>

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
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-bordered">
                        <thead>
                            <tr>
                                <th class="px-3 text-center align-middle">ID</th>
                                <th class="px-4 align-middle">Estudiante</th>
                                <th class="align-middle">Nivel</th>
                                <th class="align-middle text-center" style="width: 220px;">Asistencia ({{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                            <tr data-student-id="{{ $student->id }}">
                                <td class="text-center align-middle">{{ $student->id }}</td>
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
                                    <select class="form-control form-control-sm attendance-select" data-student-id="{{ $student->id }}" {{ auth()->user()->isProfesor() ? '' : 'disabled' }}>
                                        <option value="">Seleccionar...</option>
                                        @foreach($attendanceStatuses as $status)
                                            <option value="{{ $status }}" {{ optional($attendanceForDate[$student->id] ?? null)->status == $status ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '{{ csrf_token() }}';
        const attendanceSaveUrl = "{{ route('attendance.save') }}";
        const selectedSubjectId = '{{ $selectedSubjectId }}';
        const selectedDate = '{{ $selectedDate }}';

        const styleTag = document.createElement('style');
        styleTag.textContent = `
            .attendance-select.attendance-saving { border-color: #ffcc00 !important; box-shadow: 0 0 0 0.15rem rgba(255, 204, 0, 0.18) !important; }
            .attendance-select.attendance-saved { border-color: #28a745 !important; box-shadow: 0 0 0 0.15rem rgba(40, 167, 69, 0.18) !important; transition: border-color .2s ease, box-shadow .2s ease; }
            .attendance-select.attendance-error { border-color: #C8102E !important; box-shadow: 0 0 0 0.15rem rgba(200, 16, 46, 0.18) !important; }
        `;
        document.head.appendChild(styleTag);

        function createToastContainer() {
            const containerId = 'attendance-toast-container';
            let container = document.getElementById(containerId);
            if (container) {
                return container;
            }
            container = document.createElement('div');
            container.id = containerId;
            container.style.position = 'fixed';
            container.style.bottom = '1rem';
            container.style.right = '1rem';
            container.style.zIndex = '1080';
            container.style.display = 'flex';
            container.style.flexDirection = 'column';
            container.style.gap = '0.5rem';
            container.style.alignItems = 'flex-end';
            document.body.appendChild(container);
            return container;
        }

        function showSaveToast(message, isError) {
            const container = createToastContainer();
            const toast = document.createElement('div');
            toast.style.minWidth = '200px';
            toast.style.padding = '0.85rem 1rem';
            toast.style.background = isError ? 'rgba(200, 16, 46, 0.95)' : 'rgba(40, 167, 69, 0.95)';
            toast.style.color = '#ffffff';
            toast.style.borderRadius = '0.75rem';
            toast.style.boxShadow = '0 12px 24px rgba(0, 0, 0, 0.12)';
            toast.style.fontSize = '0.95rem';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            toast.textContent = message;
            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.addEventListener('transitionend', () => toast.remove(), { once: true });
            }, 2000);
        }

        function clearState(select) {
            select.classList.remove('attendance-saving', 'attendance-saved', 'attendance-error');
        }

        async function saveAttendance(select) {
            const studentId = select.dataset.studentId;
            const status = select.value;

            if (!studentId || status === '' || !selectedSubjectId) {
                return;
            }

            clearState(select);
            select.classList.add('attendance-saving');

            try {
                const response = await fetch(attendanceSaveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        subject_id: selectedSubjectId,
                        status: status,
                        date: selectedDate,
                    }),
                });

                const data = await response.json().catch(() => null);
                clearState(select);

                if (!response.ok || (data && data.success === false)) {
                    select.classList.add('attendance-error');
                    showSaveToast(data?.message || 'No se pudo guardar la asistencia.', true);
                    return;
                }

                select.classList.add('attendance-saved');
                showSaveToast('✓ Asistencia registrada');
            } catch (error) {
                clearState(select);
                select.classList.add('attendance-error');
                showSaveToast('Error de conexión al guardar la asistencia.', true);
            }
        }

        document.querySelectorAll('.attendance-select').forEach(function (select) {
            select.addEventListener('change', function () {
                saveAttendance(this);
            });
        });
    });
</script>
@endsection
