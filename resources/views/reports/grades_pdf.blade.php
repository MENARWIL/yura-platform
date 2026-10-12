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

    @php
        $dimensionPoints = ['ser' => 10, 'saber' => 45, 'hacer' => 40];
        $scaleToDimension = function (array $scores, int $maxPoints) {
            $numeric = array_values(array_filter($scores, fn ($v) => is_numeric($v) && $v !== ''));
            if (empty($numeric)) {
                return null;
            }
            $avg = array_sum($numeric) / count($numeric);
            return round(($avg / 100) * $maxPoints, 2);
        };
    @endphp

    <table>
        <thead>
            <tr>
                <th rowspan="2">ID</th>
                <th rowspan="2">Estudiante</th>
                <th colspan="3" class="grupo-ser">SER</th>
                <th rowspan="2" class="banner">Prom. SER<br>(10)</th>
                <th colspan="5" class="grupo-saber">SABER</th>
                <th rowspan="2" class="banner">Prom. SABER<br>(45)</th>
                <th colspan="5" class="grupo-hacer">HACER</th>
                <th rowspan="2" class="banner">Prom. HACER<br>(40)</th>
                <th rowspan="2" class="col-total">TOTAL<br>(90)</th>
                <th rowspan="2" class="col-autoeval">AUTOEVAL.<br>(5)</th>
                <th rowspan="2" class="col-nota">NOTA<br>BIMESTRAL<br>(100)</th>
            </tr>
            <tr>
                <th>1</th><th>2</th><th>3</th>
                <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                @php
                    $studentGrades = $gradesByStudent[$student->id] ?? null;
                    $ser = [1 => data_get($studentGrades, 'activity.1.score', ''), 2 => data_get($studentGrades, 'activity.2.score', ''), 3 => data_get($studentGrades, 'activity.3.score', '')];
                    $saber = [1 => data_get($studentGrades, 'exam.1.score', ''), 2 => data_get($studentGrades, 'exam.2.score', ''), 3 => data_get($studentGrades, 'exam.3.score', ''), 4 => data_get($studentGrades, 'exam.4.score', ''), 5 => data_get($studentGrades, 'exam.5.score', '')];
                    $hacer = [1 => data_get($studentGrades, 'robot.1.score', ''), 2 => data_get($studentGrades, 'robot.2.score', ''), 3 => data_get($studentGrades, 'robot.3.score', ''), 4 => data_get($studentGrades, 'robot.4.score', ''), 5 => data_get($studentGrades, 'robot.5.score', '')];
                    $autoeval = data_get($studentGrades, 'autoeval.1.score', '');

                    $promSer = $scaleToDimension($ser, $dimensionPoints['ser']);
                    $promSaber = $scaleToDimension($saber, $dimensionPoints['saber']);
                    $promHacer = $scaleToDimension($hacer, $dimensionPoints['hacer']);

                    $dimensionesLlenas = array_filter([$promSer, $promSaber, $promHacer], fn ($v) => $v !== null);
                    $total = count($dimensionesLlenas) ? round(array_sum($dimensionesLlenas), 2) : null;
                    $notaBimestral = ($total !== null || is_numeric($autoeval))
                        ? round(($total ?? 0) + (is_numeric($autoeval) ? $autoeval : 0), 2)
                        : null;
                @endphp
                <tr>
                    <td>{{ $student->id }}</td>
                    <td class="name">{{ $student->name }}</td>
                    @foreach($ser as $v)<td>{{ $v !== '' ? $v : '-' }}</td>@endforeach
                    <td class="banner">{{ $promSer ?? '-' }}</td>
                    @foreach($saber as $v)<td>{{ $v !== '' ? $v : '-' }}</td>@endforeach
                    <td class="banner">{{ $promSaber ?? '-' }}</td>
                    @foreach($hacer as $v)<td>{{ $v !== '' ? $v : '-' }}</td>@endforeach
                    <td class="banner">{{ $promHacer ?? '-' }}</td>
                    <td class="col-total">{{ $total ?? '-' }}</td>
                    <td class="col-autoeval">{{ $autoeval !== '' ? $autoeval : '-' }}</td>
                    <td class="col-nota">{{ $notaBimestral ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="19">No hay estudiantes para mostrar.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} Proyecto YURA Platform
    </div>
</body>
</html>
