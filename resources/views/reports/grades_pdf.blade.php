<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cuaderno Pedagógico - YURA Platform</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 8px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #a9d3e5; padding-bottom: 8px; }
        .header h1 { color: #1a1a2e; margin: 0; text-transform: uppercase; letter-spacing: 1px; font-size: 15px; }
        .header p { margin: 3px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px; text-align: center; }
        th { background-color: #1a1a2e; color: #fff; font-size: 7px; text-transform: uppercase; }
        td.name { text-align: left; font-weight: bold; }
        .banner { background-color: #a9d3e5; color: #14313f; font-weight: bold; }
        .grupo-ser { background-color: #eef7ee; color: #204d20; font-weight: bold; }
        .grupo-saber, .grupo-hacer { background-color: #c98a99; color: #3a0d16; font-weight: bold; }
        .col-total { background-color: #a9d3e5; font-weight: bold; }
        .col-autoeval { background-color: #cfe3bd; font-weight: bold; }
        .col-nota { background-color: #f3c58e; font-weight: bold; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 7px; color: #999; border-top: 1px solid #eee; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Cuaderno Pedagógico — SER / SABER / HACER</h1>
        <p>YURA Platform | Sistema de Seguimiento Pedagógico</p>
        <p>
            Asignatura: {{ $subject->name ?? 'Todas' }} &nbsp;|&nbsp;
            Paralelo: {{ $parallel ? ($parallel->course->name . ' - ' . $parallel->name) : 'Todos' }} &nbsp;|&nbsp;
            Trimestre: {{ $selectedQuarter }}
        </p>
        <p>Fecha de generación: {{ date('d/m/Y H:i') }} | Generado por: {{ Auth::user()->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">Nro</th>
                <th rowspan="2">Estudiante</th>
                <th colspan="{{ $columns['ser'] }}" class="grupo-ser">SER</th>
                <th rowspan="2" class="banner">Prom. SER<br>(10)</th>
                <th colspan="{{ $columns['saber'] }}" class="grupo-saber">SABER</th>
                <th rowspan="2" class="banner">Prom. SABER<br>(45)</th>
                <th colspan="{{ $columns['hacer'] }}" class="grupo-hacer">HACER</th>
                <th rowspan="2" class="banner">Prom. HACER<br>(40)</th>
                <th rowspan="2" class="col-total">TOTAL<br>(90)</th>
                <th rowspan="2" class="col-autoeval">AUTOEVAL.<br>(5)</th>
                <th rowspan="2" class="col-nota">NOTA<br>BIMESTRAL<br>(100)</th>
            </tr>
            <tr>
                @foreach(['ser', 'saber', 'hacer'] as $dim)
                    @for($i = 1; $i <= $columns[$dim]; $i++)<th>{{ $i }}</th>@endfor
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                @php
                    // Mismo cálculo que el cuaderno y las notas trimestrales (App\Services\CuadernoNota).
                    $nota = \App\Services\CuadernoNota::summarize($gradesByStudent[$student->id] ?? null);
                    ['ser' => $ser, 'saber' => $saber, 'hacer' => $hacer, 'autoeval' => $autoeval] = $nota;
                    $promSer = $nota['prom_ser'];
                    $promSaber = $nota['prom_saber'];
                    $promHacer = $nota['prom_hacer'];
                    $total = $nota['total'];
                    $notaBimestral = $nota['nota'];
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="name">{{ $student->name }}</td>
                    @foreach(array_slice($ser, 0, $columns['ser'], true) as $v)<td>{{ $v !== '' ? $v : '-' }}</td>@endforeach
                    <td class="banner">{{ $promSer ?? '-' }}</td>
                    @foreach(array_slice($saber, 0, $columns['saber'], true) as $v)<td>{{ $v !== '' ? $v : '-' }}</td>@endforeach
                    <td class="banner">{{ $promSaber ?? '-' }}</td>
                    @foreach(array_slice($hacer, 0, $columns['hacer'], true) as $v)<td>{{ $v !== '' ? $v : '-' }}</td>@endforeach
                    <td class="banner">{{ $promHacer ?? '-' }}</td>
                    <td class="col-total">{{ $total ?? '-' }}</td>
                    <td class="col-autoeval">{{ $autoeval !== '' ? $autoeval : '-' }}</td>
                    <td class="col-nota">{{ $notaBimestral ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 9 + array_sum($columns) }}">No hay estudiantes para mostrar.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} Proyecto YURA Platform
    </div>
</body>
</html>
