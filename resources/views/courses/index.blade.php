@extends('layouts.app')

@section('title', 'Cursos y Paralelos')

@section('content')
<div class="container-fluid py-4">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="mb-0"><i class="fas fa-book-open mr-2"></i> Cursos y Paralelos</h3>
            <div>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#courseModal">
                    <i class="fas fa-plus mr-1"></i> Nuevo curso
                </button>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#parallelModal">
                    <i class="fas fa-plus mr-1"></i> Nuevo paralelo
                </button>
            </div>
        </div>
        <div class="card-body bg-white">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @forelse($courses as $course)
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <span class="font-weight-bold h5 mb-0">{{ $course->name }}</span>
                            <span class="badge {{ $course->nivel_educativo === \App\Models\Course::NIVEL_SECUNDARIA ? 'badge-info' : 'badge-quechua-gold' }} ml-2">
                                {{ $course->nivelLabel() }}
                            </span>
                            <span class="badge badge-secondary ml-1">{{ $course->parallels_count }} paralelo(s)</span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-success add-parallel-btn"
                                    data-toggle="modal" data-target="#parallelModal"
                                    data-course-id="{{ $course->id }}">
                                <i class="fas fa-plus mr-1"></i> Paralelo
                            </button>
                            <a href="{{ route('courses.edit', $course) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <form action="{{ route('courses.destroy', $course) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este curso? También se eliminarán sus paralelos.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar curso">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($course->parallels->isEmpty())
                            <div class="text-center text-muted py-3">
                                Este curso todavía no tiene paralelos.
                                <button type="button" class="btn btn-link p-0 add-parallel-btn" data-toggle="modal" data-target="#parallelModal" data-course-id="{{ $course->id }}">
                                    Agregar el primero
                                </button>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th class="pl-3">Paralelo</th>
                                            <th>Cupo máximo</th>
                                            <th>Estudiantes</th>
                                            <th class="text-right pr-3">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($course->parallels as $parallel)
                                            <tr>
                                                <td class="pl-3">{{ $parallel->name }}</td>
                                                <td>{{ $parallel->max_students }}</td>
                                                <td>
                                                    {{ $parallel->students_count }}
                                                    @if($parallel->students_count >= $parallel->max_students)
                                                        <span class="badge badge-danger ml-1">Lleno</span>
                                                    @endif
                                                </td>
                                                <td class="text-right pr-3">
                                                    <a href="{{ route('parallels.edit', $parallel) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('parallels.destroy', $parallel) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('¿Eliminar este paralelo?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar paralelo">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    No hay cursos registrados. Crea el primero con el botón "Nuevo curso".
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- MODAL: Nuevo curso -->
<div class="modal fade" id="courseModal" tabindex="-1" role="dialog" aria-labelledby="courseModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('courses.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="courseModalLabel"><i class="fas fa-book-open mr-2"></i>Nuevo curso</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="course_name">Nombre del curso</label>
                        <input id="course_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Sexto de Primaria">
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="course_nivel">Nivel educativo</label>
                        <select id="course_nivel" name="nivel_educativo" class="form-control @error('nivel_educativo') is-invalid @enderror" required>
                            <option value="">Seleccione un nivel...</option>
                            @foreach(\App\Models\Course::NIVELES as $value => $label)
                                <option value="{{ $value }}" {{ old('nivel_educativo') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('nivel_educativo')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar curso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Nuevo paralelo -->
<div class="modal fade" id="parallelModal" tabindex="-1" role="dialog" aria-labelledby="parallelModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('parallels.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="parallelModalLabel"><i class="fas fa-layer-group mr-2"></i>Nuevo paralelo</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="parallel_course_id">Curso</label>
                        <select id="parallel_course_id" name="course_id" class="form-control @error('course_id') is-invalid @enderror" required>
                            <option value="">Seleccione un curso...</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }} ({{ $course->nivelLabel() }})</option>
                            @endforeach
                        </select>
                        @error('course_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="parallel_name">Nombre del paralelo</label>
                        <input id="parallel_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="50" placeholder="Ej. Paralelo A">
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="parallel_max_students">Capacidad máxima</label>
                        <input id="parallel_max_students" type="number" name="max_students" class="form-control @error('max_students') is-invalid @enderror" value="{{ old('max_students', 30) }}" min="1" required>
                        @error('max_students')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar paralelo</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Preselecciona el curso en el modal de paralelo cuando se abre desde el botón "+ Paralelo" de un curso específico.
        document.querySelectorAll('.add-parallel-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const courseId = this.dataset.courseId;
                const select = document.getElementById('parallel_course_id');
                if (select && courseId) {
                    select.value = courseId;
                }
            });
        });

        @if($errors->any())
            // Si el formulario de un modal fue enviado y falló la validación (ej. paralelo
            // duplicado), reabrir el modal correspondiente para que el usuario vea el error.
            @if(old('course_id'))
                $('#parallelModal').modal('show');
            @elseif(old('name') !== null)
                $('#courseModal').modal('show');
            @endif
        @endif
    });
</script>
@endpush
@endsection
