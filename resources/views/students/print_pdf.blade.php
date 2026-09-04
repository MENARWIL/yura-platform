<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ficha Estudiante - {{ $student->nombre ?? $student->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; }
        .header { text-align: center; margin-bottom: 20px; }
        .student-info { margin-bottom: 10px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background:#f4f4f4; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ficha del Estudiante</h1>
        <h3>{{ $student->nombre ?? $student->name }}</h3>
    </div>

    <div class="student-info">
        <strong>Código:</strong> {{ $student->id ?? '-' }}<br>
        <strong>Curso:</strong> {{ $student->course ?? '-' }}<br>
        <strong>Promedio acumulado:</strong> {{ isset($student->promedio_acumulado) ? $student->promedio_acumulado . '%' : '-' }}
    </div>

    <h4>Notas</h4>
    <table>
        <thead>
            <tr>
                <th>Asignatura</th>
                <th>Tipo</th>
                <th>Score</th>
                <th>Estado</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grades as $grade)
                <tr>
                    <td>{{ $grade->subject?->name ?? $grade->subject_id }}</td>
                    <td>{{ $grade->type ?? '-' }}</td>
                    <td>{{ is_numeric($grade->score) ? $grade->score : '-' }}</td>
                    <td>{{ $grade->status ?? '-' }}</td>
                    <td>{{ optional($grade->created_at)->format('Y-m-d') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script>
        // Open print dialog automatically when loaded
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>