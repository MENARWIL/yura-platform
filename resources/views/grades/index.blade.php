@extends('layouts.app')

@section('title', __('messages.grades_management'))

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-book mr-2"></i> {{ __('messages.grades_management') }}
            </h3>
        </div>

        <div class="card-body bg-white p-4">
            <!-- FILTROS DEL CUADERNO -->
            <div class="form-group mb-4">
                <label class="text-muted small text-uppercase font-weight-bold">Seleccionar asignatura y paralelo</label>
                <form method="GET" action="{{ route('grades.index') }}" class="d-flex flex-wrap align-items-center">
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
                    <select name="quarter" class="form-control form-control-lg border-0 bg-light rounded-pill px-4 mb-2" onchange="this.form.submit()">
                        <option value="1" {{ $selectedQuarter == 1 ? 'selected' : '' }}>Primer Trimestre</option>
                        <option value="2" {{ $selectedQuarter == 2 ? 'selected' : '' }}>Segundo Trimestre</option>
                        <option value="3" {{ $selectedQuarter == 3 ? 'selected' : '' }}>Tercer Trimestre</option>
                    </select>
                    @if($selectedSubjectId && $selectedParallelId)
                        <button type="button" id="save-all-grades" class="btn btn-success btn-lg rounded-pill px-4 ml-2 mb-2">
                            <i class="fas fa-save mr-1"></i> Guardar notas
                        </button>
                    @endif
                </form>
            </div>

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
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="gradesTable">
                    <thead>
                        <tr>
                            <th rowspan="2" class="px-3 text-center">ID</th>
                            <th rowspan="2" class="px-4">{{ __('messages.student_name') }}</th>
                            <th rowspan="2">{{ __('messages.level') }}</th>
                            <th rowspan="2">{{ __('messages.attendance') }}</th>
                            <th colspan="3" class="text-center">Actividades</th>
                            <th colspan="3" class="text-center">Exámenes</th>
                            <th colspan="3" class="text-center">Plataforma Yura</th>
                            <th rowspan="2" class="text-center">Promedio Final</th>
                            <th rowspan="2" class="text-right px-4">{{ __('messages.actions') }}</th>
                        </tr>
                        <tr>
                            <th class="text-center">Act. 1</th>
                            <th class="text-center">Act. 2</th>
                            <th class="text-center">Act. 3</th>
                            <th class="text-center">Ev. 1</th>
                            <th class="text-center">Ev. 2</th>
                            <th class="text-center">Ev. 3</th>
                            <th class="text-center">Yura 1</th>
                            <th class="text-center">Yura 2</th>
                            <th class="text-center">Yura 3</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                        @php
                            $studentGrades = $gradesByStudent[$student->id] ?? null;
                            $activity1 = data_get($studentGrades, 'activity.1.score', '');
                            $activity2 = data_get($studentGrades, 'activity.2.score', '');
                            $activity3 = data_get($studentGrades, 'activity.3.score', '');
                            $exam1 = data_get($studentGrades, 'exam.1.score', '');
                            $exam2 = data_get($studentGrades, 'exam.2.score', '');
                            $exam3 = data_get($studentGrades, 'exam.3.score', '');
                            $robot1 = data_get($studentGrades, 'robot.1.score', '');
                            $robot2 = data_get($studentGrades, 'robot.2.score', '');
                            $robot3 = data_get($studentGrades, 'robot.3.score', '');

                            $averageNumbers = [];
                            foreach ([$activity1, $activity2, $activity3, $exam1, $exam2, $exam3, $robot1, $robot2, $robot3] as $score) {
                                if (is_numeric($score)) {
                                    $averageNumbers[] = $score;
                                }
                            }
                            $averageFinal = count($averageNumbers) ? round(array_sum($averageNumbers) / count($averageNumbers), 2) : '-';
                        @endphp
                        <tr data-student-id="{{ $student->id }}">
                            <td class="text-center align-middle">{{ $student->id }}</td>
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
                                <span class="badge badge-quechua-gold px-3">{{ strtoupper($student->level ?? $student->nivel ?? '6TO') }}</span>
                            </td>
                            <td class="align-middle">
                                <select name="attendance[{{ $student->id }}]" class="form-control form-control-sm attendance-select" data-student-id="{{ $student->id }}" data-subject-id="{{ $selectedSubjectId }}">
                                    <option value="">Seleccionar...</option>
                                    @foreach($attendanceStatuses as $status)
                                        <option value="{{ $status }}" {{ optional($todayAttendance[$student->id] ?? null)->status == $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center align-middle">
                                <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm text-center grade-input mx-auto" style="width:65px;" data-student="{{ $student->id }}" data-type="activity" data-number="1" value="{{ $activity1 }}">
                            </td>
                            <td class="text-center align-middle">
                                <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm text-center grade-input mx-auto" style="width:65px;" data-student="{{ $student->id }}" data-type="activity" data-number="2" value="{{ $activity2 }}">
                            </td>
                            <td class="text-center align-middle">
                                <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm text-center grade-input mx-auto" style="width:65px;" data-student="{{ $student->id }}" data-type="activity" data-number="3" value="{{ $activity3 }}">
                            </td>
                            <td class="text-center align-middle">
                                <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm text-center grade-input mx-auto" style="width:65px;" data-student="{{ $student->id }}" data-type="exam" data-number="1" value="{{ $exam1 }}">
                            </td>
                            <td class="text-center align-middle">
                                <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm text-center grade-input mx-auto" style="width:65px;" data-student="{{ $student->id }}" data-type="exam" data-number="2" value="{{ $exam2 }}">
                            </td>
                            <td class="text-center align-middle">
                                <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm text-center grade-input mx-auto" style="width:65px;" data-student="{{ $student->id }}" data-type="exam" data-number="3" value="{{ $exam3 }}">
                            </td>
                            <td class="text-center align-middle">
                                <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm text-center grade-input mx-auto" style="width:65px;" data-student="{{ $student->id }}" data-type="robot" data-number="1" value="{{ $robot1 }}">
                            </td>
                            <td class="text-center align-middle">
                                <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm text-center grade-input mx-auto" style="width:65px;" data-student="{{ $student->id }}" data-type="robot" data-number="2" value="{{ $robot2 }}">
                            </td>
                            <td class="text-center align-middle">
                                <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm text-center grade-input mx-auto" style="width:65px;" data-student="{{ $student->id }}" data-type="robot" data-number="3" value="{{ $robot3 }}">
                            </td>
                            <td class="text-center font-weight-bold grade-average">{{ $averageFinal }}</td>
                            <td class="px-4 text-right align-middle">
                                <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#robotModal" data-student-id="{{ $student->id }}">
                                    <i class="fas fa-robot mr-1"></i> Lanzar Actividad
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="15" class="text-center py-5">
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

<!-- MODAL PARA LANZAR LA ACTIVIDAD AL ROBOT YURA -->
<div class="modal fade" id="robotModal" tabindex="-1" role="dialog" aria-labelledby="robotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <form method="POST" action="{{ route('robot-activities.store') }}">
                @csrf
                <div class="modal-header bg-primary text-white" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h5 class="modal-title font-weight-bold" id="robotModalLabel"><i class="fas fa-robot mr-2"></i> {{ __('messages.launch_robot_activity') }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">{{ __('messages.select_parallel') ?? 'Seleccionar Paralelo' }}</label>
                        <select name="parallel_id" class="form-control custom-select">
                            @foreach($parallels as $parallel)
                                <option value="{{ $parallel->id }}">{{ $parallel->name ?? $parallel->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Asignatura:</label>
                        <select name="subject_id" class="form-control custom-select" required>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ $selectedSubjectId == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-secondary">Categoría de Vocabulario Quechua:</label>
                        <select name="activity_category" class="form-control custom-select" required>
                            <option value="nivel1_alimentos">Alimentos (Mikhuna)</option>
                            <option value="nivel1_colores">Colores (Llimp'ikuna)</option>
                            <option value="nivel1_elementos_casa">Elementos de la Casa (Wasi)</option>
                            <option value="nivel1_elementos_naturales">Elementos Naturales (Sallqa Pacha)</option>
                            <option value="nivel1_familia">Familia (Ayllu)</option>
                            <option value="nivel1_herramientas">Herramientas (Llamk'anakuna)</option>
                            <option value="nivel1_lugares">Lugares (Marka)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
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
            .grade-input.grade-save-error { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important; }
            .attendance-select.attendance-saving { border-color: #ffcc00 !important; box-shadow: 0 0 0 0.15rem rgba(255, 204, 0, 0.18) !important; }
            .attendance-select.attendance-saved { border-color: #28a745 !important; box-shadow: 0 0 0 0.15rem rgba(40, 167, 69, 0.18) !important; transition: border-color .2s ease, box-shadow .2s ease; }
            .attendance-select.attendance-error { border-color: #dc3545 !important; box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.18) !important; }
        `;
        document.head.appendChild(styleTag);

        function parseGradeValue(value) {
            const parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : null;
        }

        function recalcRowAverage(row) {
            const values = Array.from(row.querySelectorAll('.grade-input'))
                .map(input => parseGradeValue(input.value))
                .filter(value => value !== null);
            const averageCell = row.querySelector('.grade-average');
            if (!averageCell) {
                return;
            }
            if (!values.length) {
                averageCell.textContent = '-';
                return;
            }
            const average = values.reduce((sum, value) => sum + value, 0) / values.length;
            averageCell.textContent = average.toFixed(2);
        }

        function createToastContainer() {
            const containerId = 'grade-toast-container';
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

        function showSaveToast(message) {
            const container = createToastContainer();
            const toast = document.createElement('div');
            toast.className = 'grade-save-toast';
            toast.style.minWidth = '200px';
            toast.style.padding = '0.85rem 1rem';
            toast.style.background = 'rgba(40, 167, 69, 0.95)';
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
        }

        async function saveGrade(input) {
            const studentId = input.dataset.student;
            const type = input.dataset.type;
            const activityNumber = input.dataset.number;
            const score = parseGradeValue(input.value);

            if (score === null || score < 0 || score > 100) {
                return false;
            }

            const payload = {
                student_id: studentId,
                subject_id: '{{ $selectedSubjectId }}',
                type: type,
                activity_number: activityNumber,
                quarter: '{{ $selectedQuarter }}',
                score: score,
            };

            markInputProcessing(input);

            try {
                const response = await fetch(gradeSaveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json().catch(() => null);
                if (!response.ok || !data?.success) {
                    markInputError(input);
                    console.error('Error guardando nota:', response.statusText, data?.message || 'Respuesta inválida');
                    return;
                }

                markInputSaved(input);
                showSaveToast('✓ Calificación guardada');
                return true;
            } catch (error) {
                markInputError(input);
                console.error('Error en la petición:', error);
                return false;
            }
        }

        gradeInputs.forEach(input => {
            const row = input.closest('tr');
            if (row) {
                recalcRowAverage(row);
            }

            input.addEventListener('change', function () {
                const row = this.closest('tr');
                if (row) {
                    recalcRowAverage(row);
                }
                saveGrade(this);
            });
        });

        const saveAllButton = document.getElementById('save-all-grades');
        if (saveAllButton) {
            saveAllButton.addEventListener('click', async function () {
                const originalText = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';
                const results = await Promise.all(Array.from(gradeInputs).map(saveGrade));
                this.disabled = false;
                this.innerHTML = originalText;
                if (results.some(Boolean)) {
                    showSaveToast('✓ Notas guardadas correctamente');
                }
            });
        }

        // Attendance select auto-save
        const attendanceSelects = document.querySelectorAll('.attendance-select');
        const attendanceSaveUrl = '/attendance/save'; // Create this route or adjust accordingly

        function clearAttendanceState(select) {
            if (select._attendanceTimer) {
                clearTimeout(select._attendanceTimer);
                select._attendanceTimer = null;
            }
            select.classList.remove('attendance-saving', 'attendance-saved', 'attendance-error');
        }

        function markAttendanceProcessing(select) {
            clearAttendanceState(select);
            select.classList.add('attendance-saving');
        }

        function markAttendanceSaved(select) {
            clearAttendanceState(select);
            select.classList.add('attendance-saved');
            select._attendanceTimer = setTimeout(() => clearAttendanceState(select), 1400);
        }

        function markAttendanceError(select) {
            clearAttendanceState(select);
            select.classList.add('attendance-error');
        }

        async function saveAttendance(select) {
            const studentId = select.dataset.studentId || select.closest('tr')?.datasetStudentId || select.closest('tr')?.dataset?.studentId;
            const subjectId = select.dataset.subjectId || '{{ $selectedSubjectId }}';
            const status = select.value;

            // don't save empty
            if (!studentId || status === '') {
                return;
            }

            const today = new Date().toISOString().slice(0,10);

            const payload = {
                student_id: studentId,
                subject_id: subjectId,
                status: status,
                date: today,
            };

            markAttendanceProcessing(select);

            try {
                const response = await fetch(attendanceSaveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json().catch(() => null);
                if (!response.ok || (data && data.success === false)) {
                    markAttendanceError(select);
                    console.error('Error guardando asistencia:', response.statusText, data?.message || 'Respuesta inválida');
                    return;
                }

                markAttendanceSaved(select);
                showSaveToast('✓ Asistencia registrada');
            } catch (error) {
                markAttendanceError(select);
                console.error('Error en la petición de asistencia:', error);
            }
        }

        attendanceSelects.forEach(s => {
            s.addEventListener('change', function () {
                saveAttendance(this);
            });
        });
    });
</script>

@endsection
