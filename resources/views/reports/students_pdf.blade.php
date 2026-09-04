<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Estudiantes - YURA Platform</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #f9d423; padding-bottom: 10px; }
        .header h1 { color: #1a1a2e; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #1a1a2e; color: white; text-align: left; padding: 8px; text-transform: uppercase; font-size: 9px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .badge { padding: 3px 8px; border-radius: 10px; font-weight: bold; font-size: 8px; }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 8px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
        .summary { margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 10px; }
        .summary-item { display: inline-block; width: 30%; }
        .summary-item b { display: block; font-size: 14px; color: #a90329; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte General de Estudiantes</h1>
        <p>YURA Platform | Sistema de Seguimiento Pedagógico</p>
        <p>Fecha de Generación: {{ date('d/m/Y H:i') }} | Generado por: {{ Auth::user()->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Nivel</th>
                <th>Curso</th>
                <th>Paralelo</th>
                <th>Padre / Responsable</th>
                <th>Profesor</th>
                <th>Estado</th>
                <th>Promedio</th>
                <th>Asis.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
            <tr>
                <td><b>{{ $student->nombre }}</b><br><small>Edad: {{ $student->edad }}</small></td>
                <td>{{ strtoupper($student->nivel) }}</td>
                <td>{{ optional($student->course)->name ?? $student->getAttribute('course') ?? 'N/A' }}</td>
                <td>{{ $student->parallel->name ?? 'N/A' }}</td>
                <td>{{ $student->padre->name ?? 'N/A' }}</td>
                <td>{{ $student->profesor->name ?? 'N/A' }}</td>
                <td>
                    <span class="badge {{ $student->estado == 'activo' ? 'badge-success' : 'badge-danger' }}">
                        {{ strtoupper($student->estado) }}
                    </span>
                </td>
                <td>
                    <span class="badge {{ $student->promedio >= 70 ? 'badge-success' : ($student->promedio >= 51 ? 'badge-warning' : 'badge-danger') }}">
                        {{ $student->promedio }}%
                    </span>
                </td>
                <td>{{ $student->asistencia }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-item">
            <small>Total Estudiantes</small>
            <b>{{ count($students) }}</b>
        </div>
        <div class="summary-item">
            <small>Promedio General</small>
            <b>{{ round($students->avg('promedio'), 2) }}%</b>
        </div>
        <div class="summary-item">
            <small>Estado Sistema</small>
            <b style="color: #28a745;">ESTABLE</b>
        </div>
    </div>

    <div class="footer">
        © 2026 Wilson Mendieta Arnez - Proyecto YURA Platform
    </div>
</body>
</html>
