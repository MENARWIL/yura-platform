<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Notas trimestrales</title>
    <style>
        @page { margin: 45px 45px 50px 45px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10.5px; color: #1f2937; }
        .franja { height: 5px; margin-bottom: 12px; }
        .franja span { display: inline-block; width: 33.2%; height: 5px; }
        .header { text-align: center; margin-bottom: 12px; }
        .header .org { font-size: 13px; font-weight: bold; color: #082C6C; text-transform: uppercase; }
        .header h1 { font-size: 14px; color: #082C6C; margin: 4px 0; }
        .header p { margin: 2px 0; color: #5B6474; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #c9d6f2; padding: 5px; text-align: center; }
        th { background: #E7EEF9; color: #082C6C; font-size: 10px; }
        th.anual { background: #0B3D91; color: #fff; }
        td.nombre { text-align: left; font-weight: bold; }
        td.anual { background: #E7EEF9; font-weight: bold; color: #0B3D91; }
        tr.prom td { background: #F4F6FA; font-weight: bold; }
        .pie { position: fixed; bottom: -25px; left: 0; right: 0; font-size: 8.5px; color: #8a94a6; text-align: right; border-top: 1px solid #e5e7eb; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="franja"><span style="background:#C8102E"></span><span style="background:#dfe5ee"></span><span style="background:#0B3D91"></span></div>
    <div class="header">
        @if($orgName)<div class="org">{{ $orgName }}</div>@endif
        <h1>Notas trimestrales</h1>
        <p>
            Asignatura: <strong>{{ $subject->name }}</strong> &nbsp;|&nbsp;
            Curso y paralelo: <strong>{{ trim(($parallel->course?->name ?? '') . ' - ' . $parallel->name, ' -') }}</strong>
            @if($year) &nbsp;|&nbsp; {{ $year->name }} @endif
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nro</th>
                <th style="text-align:left">Estudiante</th>
                <th>1.er trimestre</th>
                <th>2.º trimestre</th>
                <th>3.er trimestre</th>
                <th class="anual">Promedio anual</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="nombre">{{ $row['student']->name }}</td>
                    @foreach([1, 2, 3] as $quarter)
                        <td>{{ $row['quarters'][$quarter] ?? '-' }}</td>
                    @endforeach
                    <td class="anual">{{ $row['annual'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No hay estudiantes activos en este paralelo.</td></tr>
            @endforelse
            @if($rows->isNotEmpty())
                <tr class="prom">
                    <td></td>
                    <td class="nombre">Promedio del paralelo</td>
                    @foreach([1, 2, 3] as $quarter)
                        <td>{{ \App\Services\TrimesterReport::columnAverage($rows, $quarter) ?? '-' }}</td>
                    @endforeach
                    <td class="anual">{{ \App\Services\TrimesterReport::columnAverage($rows) ?? '-' }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="pie">Nota de cada trimestre sobre 100 (SER 10, SABER 45, HACER 40, autoevaluación 5). Generado por YURA Platform el {{ now()->format('d/m/Y H:i') }} — {{ Auth::user()?->name }}</div>
</body>
</html>
