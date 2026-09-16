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
                    @if(auth()->user()->isAdmin() || auth()->user()->isAcademic())
                        <a href="{{ route('students.export.pdf.all') }}" class="btn btn-outline-danger btn-sm px-3 rounded-pill mr-2 shadow-sm">
                            <i class="fas fa-file-pdf mr-1"></i> {{ __('messages.export_pdf') }}
                        </a>
                        <a href="{{ route('students.export.excel.all') }}" class="btn btn-outline-success btn-sm px-3 rounded-pill shadow-sm">
                            <i class="fas fa-file-excel mr-1"></i> {{ __('messages.export_excel') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body p-4 bg-white">
            <form action="{{ route('students.index') }}" method="GET" class="mb-4">
                <div class="form-row align-items-end">
                    <div class="col-md-4 form-group mb-md-0">
                        <label for="student_name">Nombre del estudiante</label>
                        <input type="text" name="student_name" id="student_name" class="form-control" value="{{ request('student_name') }}" placeholder="Buscar estudiante">
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <label for="course_id">Curso</label>
                        <select name="course_id" id="course_id" class="form-control">
                            <option value="">Todos los cursos</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <label for="parallel_id">Paralelo</label>
                        <select name="parallel_id" id="parallel_id" class="form-control">
                            <option value="">Todos los paralelos</option>
                            @foreach($parallels as $parallel)
                                <option value="{{ $parallel->id }}" {{ request('parallel_id') == $parallel->id ? 'selected' : '' }}>
                                    {{ $parallel->course?->name }} - {{ $parallel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group mb-md-0">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-1"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="px-4">{{ __('messages.student') ?? 'Estudiante' }}</th>
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
                                    <div>
                                        <span class="font-weight-bold d-block text-dark">{{ $student->nombre }}</span>
                                        <small class="text-muted"><i class="fas fa-user-friends mr-1"></i> {{ __('messages.responsible') ?? 'Tutor' }}: {{ $student->parent?->name ?? $student->padre?->name ?? 'Asignado' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-quechua-gold px-3 py-2 rounded-pill font-weight-bold">
                                    {{ strtoupper($student->level ?? $student->nivel ?? '6to Primaria') }}
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
                                    <div class="progress mt-1" style="height: 6px; width: 60px; border-radius: 2px; background-color: #e9ecef;">
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
                            <td colspan="5" class="text-center py-5 bg-white text-muted">
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
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
</script>
@endsection
