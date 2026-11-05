@extends('layouts.app')

@section('title', 'Promover curso')

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0 shadow-sm">
        <div class="card-header card-header-quechua d-flex align-items-center justify-content-between flex-wrap">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-level-up-alt mr-2"></i> Promover curso
            </h3>
            <a href="{{ route('students.index') }}" class="btn btn-light btn-sm rounded-pill px-3">
                <i class="fas fa-arrow-left mr-1"></i> Volver a estudiantes
            </a>
        </div>
        <div class="card-body bg-white">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-1" aria-hidden="true"></i>
                Pasa juntos a los estudiantes que aprobaron (por ejemplo, de 3.° A a 4.° A) y después corrige los datos que haga falta.
                Desmarca a quienes repiten: se quedan en su paralelo.
                La promoción se registra en la <strong>{{ $year?->name ?? 'gestión actual' }}</strong>, así que hazla
                <strong>al empezar la nueva gestión</strong> (créala y márcala como actual en «Gestiones»). El curso anterior queda guardado en la gestión pasada.
            </div>

            {{-- Paso 1: curso de origen --}}
            <form method="GET" action="{{ route('students.promote') }}" class="filter-bar" aria-label="Curso de origen">
                <div class="fb-field">
                    <label for="from"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> 1. Curso y paralelo de origen</label>
                    <select name="from" id="from" onchange="this.form.submit()">
                        <option value="">Elige el paralelo…</option>
                        @foreach($parallels as $parallel)
                            <option value="{{ $parallel->id }}" @selected($from?->id === $parallel->id)>
                                {{ $parallel->course?->name }} — Paralelo {{ $parallel->name }} ({{ $parallel->students_count }} estudiante(s))
                            </option>
                        @endforeach
                    </select>
                </div>
                <noscript><div class="fb-actions"><button type="submit" class="fb-btn fb-btn-primary">Ver estudiantes</button></div></noscript>
            </form>

            @if($from)
                @if($students->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">Este paralelo no tiene estudiantes activos.</p>
                @else
                    <form method="POST" action="{{ route('students.promote.store') }}" id="promote-form"
                          data-confirm="Se cambiará el curso de los estudiantes marcados. Después puedes corregir a cualquiera desde su ficha."
                          data-confirm-title="¿Promover a los estudiantes marcados?" data-confirm-ok="Promover">
                        @csrf
                        <input type="hidden" name="from_parallel_id" value="{{ $from->id }}">

                        <h6 class="font-weight-bold mt-2">2. Estudiantes que pasan <small class="text-muted font-weight-normal">(desmarca a quienes repiten)</small></h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:44px" class="text-center">
                                            <input type="checkbox" id="promote-all" checked aria-label="Marcar o desmarcar a todos">
                                        </th>
                                        <th>Estudiante</th>
                                        <th class="text-center">Edad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $student)
                                        @php $checked = old('student_ids') ? in_array($student->id, array_map('intval', (array) old('student_ids')), true) : true; @endphp
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="promote-student" id="st-{{ $student->id }}" @checked($checked)>
                                            </td>
                                            <td><label for="st-{{ $student->id }}" class="mb-0 font-weight-normal">{{ $student->name }}</label></td>
                                            <td class="text-center">{{ $student->age ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @error('student_ids') <p class="text-danger small">{{ $message }}</p> @enderror

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="to_parallel_id" class="font-weight-bold">3. Pasan a</label>
                                <select name="to_parallel_id" id="to_parallel_id" class="form-control @error('to_parallel_id') is-invalid @enderror" required>
                                    <option value="">Elige el paralelo de destino…</option>
                                    @foreach($parallels->where('id', '!=', $from->id) as $parallel)
                                        @php $free = max(0, $parallel->max_students - $parallel->students_count); @endphp
                                        <option value="{{ $parallel->id }}" @selected((int) old('to_parallel_id') === $parallel->id)>
                                            {{ $parallel->course?->name }} — Paralelo {{ $parallel->name }} ({{ $free }} cupo(s) libre(s))
                                        </option>
                                    @endforeach
                                </select>
                                @error('to_parallel_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 form-group d-flex align-items-end">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="hidden" name="add_year" value="0">
                                    <input type="checkbox" class="custom-control-input" id="add_year" name="add_year" value="1" @checked(old('add_year', '1') === '1')>
                                    <label class="custom-control-label" for="add_year">Sumar 1 año a la edad de los promovidos</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <small class="text-muted"><span id="promote-count">{{ $students->count() }}</span> de {{ $students->count() }} estudiante(s) marcados.</small>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fas fa-level-up-alt mr-1"></i> Promover
                            </button>
                        </div>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const all = document.getElementById('promote-all');
        const boxes = () => Array.from(document.querySelectorAll('.promote-student'));
        const count = document.getElementById('promote-count');
        function refresh() {
            const marked = boxes().filter(box => box.checked).length;
            if (count) count.textContent = marked;
            if (all) {
                all.checked = marked === boxes().length;
                all.indeterminate = marked > 0 && marked < boxes().length;
            }
        }
        if (all) all.addEventListener('change', () => { boxes().forEach(box => { box.checked = all.checked; }); refresh(); });
        boxes().forEach(box => box.addEventListener('change', refresh));
        refresh();
    });
</script>
@endpush
@endsection
