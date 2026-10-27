@extends('layouts.app')

@section('title', 'Gestiones escolares')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h4 class="font-weight-bold mb-1 text-dark"><i class="fas fa-calendar-alt mr-2 text-primary"></i>Gestiones escolares</h4>
                <p class="text-muted small mb-0">
                    La gestión <strong>actual</strong> es donde se guardan las notas, la asistencia y las actividades nuevas.
                    Una gestión <strong>cerrada</strong> no admite cambios hasta que el administrador la reabra, pero su información
                    se puede <strong>consultar</strong> y <strong>descargar</strong> en cualquier momento.
                </p>
            </div>

            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Gestión</th>
                                <th>Estado</th>
                                <th class="text-right">Matrículas</th>
                                <th class="text-right">Notas</th>
                                <th class="text-right">Asistencias</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($years as $year)
                            <tr>
                                <td class="font-weight-bold">{{ $year->name }}</td>
                                <td>
                                    @if($year->is_current)<span class="badge badge-primary">Actual</span>@endif
                                    @if($year->isClosed())
                                        <span class="badge badge-secondary">Cerrada {{ $year->closed_at->format('d/m/Y') }}@if($year->closedBy) · {{ $year->closedBy->name }}@endif</span>
                                    @else
                                        <span class="badge badge-success">Abierta</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ $year->counts['enrollments'] }}</td>
                                <td class="text-right">{{ $year->counts['grades'] }}</td>
                                <td class="text-right">{{ $year->counts['attendance'] }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.years.show', $year) }}" class="btn btn-sm btn-outline-secondary" title="Consultar lo que se guardó en {{ $year->name }}"><i class="fas fa-folder-open mr-1" aria-hidden="true"></i>Consultar</a>
                                    <a href="{{ route('admin.years.export', $year) }}" class="btn btn-sm btn-outline-success" title="Descargar el respaldo de {{ $year->name }} en Excel"><i class="fas fa-file-excel mr-1" aria-hidden="true"></i>Respaldo</a>
                                    @if($year->isClosed())
                                        <form method="POST" action="{{ route('admin.years.reopen', $year) }}" class="d-inline"
                                              onsubmit="return confirm('Reabrir {{ $year->name }} vuelve a permitir cambios en datos de un año cerrado. ¿Continuar?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning"><i class="fas fa-lock-open mr-1"></i>Reabrir</button>
                                        </form>
                                    @else
                                        @unless($year->is_current)
                                        <form method="POST" action="{{ route('admin.years.current', $year) }}" class="d-inline"
                                              onsubmit="return confirm('{{ $year->name }} pasará a ser la gestión actual: ahí se guardarán los datos nuevos de todos los usuarios. ¿Continuar?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary">Poner como actual</button>
                                        </form>
                                        @endunless
                                        <form method="POST" action="{{ route('admin.years.close', $year) }}" class="d-inline-flex align-items-center"
                                              onsubmit="return confirm('Cerrar {{ $year->name }} impide cualquier cambio en sus datos hasta que se reabra. ¿Continuar?')">
                                            @csrf
                                            @if($year->is_current)
                                                <select name="next_year_id" class="form-control form-control-sm mr-2" required>
                                                    <option value="">Nueva gestión actual…</option>
                                                    @foreach($years->where('id', '!=', $year->id)->filter(fn ($o) => ! $o->isClosed()) as $option)
                                                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-lock mr-1"></i>Cerrar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body px-4">
                <h6 class="font-weight-bold">Crear una gestión</h6>
                <form method="POST" action="{{ route('admin.years.store') }}" class="form-inline">
                    @csrf
                    <input type="number" name="year" min="2000" max="2100" placeholder="Año, por ejemplo {{ (optional($years->first())->year ?? 2026) + 1 }}"
                           class="form-control mr-2 @error('year') is-invalid @enderror" required>
                    <button class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear</button>
                    @error('year')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </form>
                <p class="text-muted small mt-2 mb-0">La gestión nueva se crea abierta y sin ser la actual; podrás ponerla como actual cuando corresponda.</p>
            </div>
        </div>
    </div>
</div>
@endsection
