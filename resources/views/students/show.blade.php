@extends('layouts.app')

@section('title', $student->nombre ?? 'Ficha del Estudiante')

@section('content')
<div class="container-fluid p-4 animate-fade-in">
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <div>
            <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver al Listado
            </a>
        </div>
        <div>
            <a href="{{ route('students.export.pdf', $student->id) }}" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill mr-2">
                <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
            </a>
            <a href="{{ route('students.export.excel', $student->id) }}" class="btn btn-sm btn-outline-success rounded-pill">
                <i class="fas fa-file-excel mr-1"></i> Exportar Excel
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    @if($student->foto_path)
                        <img src="{{ asset('storage/' . $student->foto_path) }}" alt="{{ $student->nombre }}" class="rounded-circle img-fluid" style="width:140px;height:140px;object-fit:cover;" />
                    @else
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width:140px;height:140px;">
                            <i class="fas fa-user fa-3x text-primary"></i>
                        </div>
                    @endif
                </div>

                <div class="col-md-9">
                    <h2 class="font-weight-bold mb-1">{{ $student->nombre ?? $student->name }}</h2>
                    <div class="mb-2">
                        <span class="badge badge-quechua-gold px-3 py-2 font-weight-bold">{{ $student->course ?? $student->course_id ?? 'N/A' }}</span>
                        <small class="text-muted ml-2">{{ strtoupper($student->level ?? $student->nivel ?? '') }}</small>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-4">
                            <h6 class="text-uppercase text-muted small">Datos Personales</h6>
                            <p class="mb-1"><strong>Fecha de Nacimiento:</strong> {{ is_string($student->registration_date) ? $student->registration_date : ($student->registration_date?->format('Y-m-d') ?? ($student->fecha_nacimiento ?? '-')) }}</p>
                            <p class="mb-0"><strong>Código / C.I.:</strong> {{ $student->id ?? ($student->codigo ?? 'N/A') }}</p>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-uppercase text-muted small">Información Académica</h6>
                            <p class="mb-1"><strong>Promedio General:</strong>
                                {{ isset($student->promedio_acumulado) && is_numeric($student->promedio_acumulado) ? $student->promedio_acumulado . '%' : ($student->promedio_acumulado ?? ($student->promedio ?? '-')) }}
                            </p>
                            <div class="progress" style="height:8px; width:140px;">
                                @php $prog = isset($student->promedio_acumulado) && is_numeric($student->promedio_acumulado) ? (int)$student->promedio_acumulado : 0; @endphp
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $prog }}%;"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-uppercase text-muted small">Contacto</h6>
                            <p class="mb-1"><strong>Tutor / Padre:</strong> {{ $student->parent?->name ?? $student->padre?->name ?? 'No registrado' }}</p>
                            <p class="mb-0"><strong>Email:</strong> {{ $student->parent?->email ?? $student->padre?->email ?? '-' }}</p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
