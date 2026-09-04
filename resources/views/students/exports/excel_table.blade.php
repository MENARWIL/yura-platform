<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reporte Estudiante - {{ $student->nombre ?? $student->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width:100%; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #efefef; }
    </style>
</head>
<body>
    <h2>Ficha del Estudiante: {{ $student->nombre ?? $student->name }}</h2>
    <p><strong>Código:</strong> {{ $student->id }}</p>
    <p><strong>Curso:</strong> {{ $student->course ?? '-' }}</p>

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
</body>
</html>