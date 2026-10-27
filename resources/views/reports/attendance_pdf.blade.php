<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Asistencia - YURA Platform</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #a9d3e5; padding-bottom: 10px; }
        .header h1 { color: #1a1a2e; margin: 0; text-transform: uppercase; letter-spacing: 1px; font-size: 17px; }
        .header p { margin: 4px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #1a1a2e; color: #fff; text-align: left; padding: 6px; text-transform: uppercase; font-size: 9px; }
        td { padding: 6px; border-bottom: 1px solid #eee; }
        .badge { padding: 3px 8px; border-radius: 10px; font-weight: bold; font-size: 8px; }
        .badge-presente { background-color: #d4edda; color: #155724; }
        .badge-falta { background-color: #f8d7da; color: #721c24; }
        .badge-atraso { background-color: #fff3cd; color: #856404; }
        .badge-sin-registro { background-color: #eee; color: #888; }
        .summary { margin-top: 20px; padding: 12px; background-color: #f8f9fa; border-radius: 10px; }
        .summary-item { display: inline-block; width: 30%; }
        .summary-item b { display: block; font-size: 14px; color: #1a1a2e; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 8px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Asistencia</h1>
        <p>YURA Platform | Sistema de Seguimiento Pedagógico</p>
        <p>
            Asignatura: {{ $subject->name ?? 'Todas' }} &nbsp;|&nbsp;
            Paralelo: {{ $parallel ? ($parallel->course->name . ' - ' . $parallel->name) : 'Todos' }} &nbsp;|&nbsp;
            Fecha: {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}
        </p>
        <p>Fecha de generación: {{ date('d/m/Y H:i') }} | Generado por: {{ Auth::user()->name }}</p>
    </div>

    @php
        $presentCount = 0;
        $totalCount = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th>Nro</th>
                <th>Estudiante</th>
                <th>Nivel</th>
                <th>Estado de asistencia</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                @php
                    $status = optional($attendanceForDate[$student->id] ?? null)->status;
                    $normalized = strtolower(trim((string) $status));
                    if ($status) {
                        $totalCount++;
                        if ($normalized === 'presente') { $presentCount++; }
                    }
                    $badgeClass = match($normalized) {
                        'presente' => 'badge-presente',
                        'falta' => 'badge-falta',
                        'atraso' => 'badge-atraso',
                        default => 'badge-sin-registro',
                    };
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><b>{{ $student->name }}</b></td>
                    <td>{{ mb_strtoupper($student->level ?? '-', 'UTF-8') }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $status ?? 'Sin registro' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4">No hay estudiantes para mostrar.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-item">
            <small>Total Estudiantes</small>
            <b>{{ count($students) }}</b>
        </div>
        <div class="summary-item">
            <small>Con registro este día</small>
            <b>{{ $totalCount }}</b>
        </div>
        <div class="summary-item">
            <small>% Presentes (sobre registrados)</small>
            <b>{{ $totalCount ? round(($presentCount / $totalCount) * 100, 1) : 0 }}%</b>
        </div>
    </div>

    <div class="footer">
        © {{ date('Y') }} Proyecto YURA Platform
    </div>
</body>
</html>
