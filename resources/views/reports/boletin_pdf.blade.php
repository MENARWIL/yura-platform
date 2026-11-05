<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Boletín de notas trimestrales</title>
    <style>
        @page { margin: 55px 55px 55px 65px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1f2937; }
        .franja { height: 6px; margin-bottom: 14px; }
        .franja span { display: inline-block; width: 33.2%; height: 6px; }
        .header { text-align: center; margin-bottom: 14px; }
        .header .org { font-size: 14px; font-weight: bold; color: #082C6C; text-transform: uppercase; letter-spacing: 1px; }
        h1 { text-align: center; font-size: 15px; color: #082C6C; letter-spacing: 1px; margin: 6px 0 16px; }
        .datos { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .datos td { padding: 3px 6px; }
        .datos td.label { font-weight: bold; color: #082C6C; width: 18%; }
        table.notas { width: 100%; border-collapse: collapse; }
        table.notas th, table.notas td { border: 1px solid #c9d6f2; padding: 6px; text-align: center; }
        table.notas th { background: #E7EEF9; color: #082C6C; font-size: 10.5px; }
        table.notas th.anual { background: #0B3D91; color: #fff; }
        table.notas td.asig { text-align: left; font-weight: bold; }
        table.notas td.anual { background: #E7EEF9; font-weight: bold; color: #0B3D91; }
        table.notas tr.prom td { background: #F4F6FA; font-weight: bold; }
        .nota { font-size: 9.5px; color: #5B6474; margin-top: 8px; }
        .firmas { width: 100%; margin-top: 70px; }
        .firmas td { width: 50%; text-align: center; padding: 0 20px; }
        .linea { border-top: 1px solid #1f2937; padding-top: 5px; font-weight: bold; }
        .pie { position: fixed; bottom: -25px; left: 0; right: 0; font-size: 8.5px; color: #8a94a6; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="franja"><span style="background:#C8102E"></span><span style="background:#dfe5ee"></span><span style="background:#0B3D91"></span></div>

    <div class="header"><div class="org">{{ $orgName }}</div></div>
    <h1>BOLETÍN DE NOTAS TRIMESTRALES</h1>

    <table class="datos">
        <tr>
            <td class="label">Estudiante:</td><td>{{ $student->name }}</td>
            <td class="label">Gestión:</td><td>{{ $year?->name ?? now()->year }}</td>
        </tr>
        <tr>
            <td class="label">Curso:</td><td>{{ $student->course_name }}</td>
            <td class="label">Paralelo:</td><td>{{ $student->parallel?->name ?? '—' }}</td>
        </tr>
    </table>

    <table class="notas">
        <thead>
            <tr>
                <th style="text-align:left">Asignatura</th>
                <th>1.er trimestre</th>
                <th>2.º trimestre</th>
                <th>3.er trimestre</th>
                <th class="anual">Promedio anual</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="asig">{{ $row['subject']->name }}</td>
                    @foreach([1, 2, 3] as $quarter)
                        <td>{{ $row['quarters'][$quarter] ?? '-' }}</td>
                    @endforeach
                    <td class="anual">{{ $row['annual'] ?? '-' }}</td>
                </tr>
            @endforeach
            <tr class="prom">
                <td class="asig">Promedio general</td>
                @foreach([1, 2, 3] as $quarter)
                    <td>{{ \App\Services\TrimesterReport::columnAverage($rows, $quarter) ?? '-' }}</td>
                @endforeach
                <td class="anual">{{ \App\Services\TrimesterReport::columnAverage($rows) ?? '-' }}</td>
            </tr>
        </tbody>
    </table>
    <p class="nota">
        Cada trimestre es la nota final sobre 100 del Cuaderno Pedagógico: SER (10), SABER (45), HACER (40) y autoevaluación (5).
        «-» indica que el trimestre aún no tiene notas; los promedios consideran solo los trimestres y asignaturas con nota.
    </p>

    <p style="text-align:right; margin-top:18px">{{ $placeAndDate }}</p>

    <table class="firmas">
        <tr>
            <td><div class="linea">Director(a)</div></td>
            <td><div class="linea">Secretaría</div></td>
        </tr>
    </table>

    <div class="pie">Documento generado por YURA Platform el {{ now()->format('d/m/Y H:i') }} — Emitido por: {{ $issuedBy?->name }}.</div>
</body>
</html>
