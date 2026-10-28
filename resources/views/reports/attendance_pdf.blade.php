<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Asistencia - YURA Platform</title>
    @php
        $single = $dateFrom->equalTo($dateTo);
        $letters = ['Presente' => 'P', 'Falta' => 'F', 'Atraso' => 'R', 'Licencia' => 'L'];
        $classOf = fn (?string $status) => match (\App\Models\Attendance::normalizeStatus($status)) {
            'presente' => 'st-p', 'falta' => 'st-f', 'atraso' => 'st-r', 'licencia' => 'st-l', default => '',
        };
        $sum = fn (string $key) => $totals->sum($key);
        $groupPercent = \App\Models\Attendance::presentPercent(
            collect($grid)->flatten()->map(fn ($status) => (object) ['status' => $status]), 1
        );
        // Columnas de días más estrechas cuantos más días hay.
        $dayFont = $days->count() > 20 ? '6.5px' : '8px';
    @endphp
    <style>
        @page { margin: 18px 20px 30px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 9px; color: #1F2937; }
        .header { border-bottom: 3px solid #C8102E; padding-bottom: 8px; margin-bottom: 10px; }
        .header h1 { color: #082C6C; margin: 0; text-transform: uppercase; letter-spacing: 1px; font-size: 16px; }
        .header .sub { color: #5B6474; margin: 3px 0 0; }
        .meta { width: 100%; margin-bottom: 8px; }
        .meta td { padding: 2px 0; border: 0; }
        .meta b { color: #0B3D91; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { background-color: #0B3D91; color: #fff; padding: 5px 3px; font-size: 8px; text-transform: uppercase; border: 1px solid #0B3D91; }
        table.list td { padding: 4px 3px; border: 1px solid #DDE3EC; }
        table.list tr:nth-child(even) td { background-color: #F7F9FD; }
        .c { text-align: center; }
        .name { white-space: nowrap; }
        th.day { font-size: {{ $dayFont }}; padding: 3px 1px; }
        td.day { font-size: {{ $dayFont }}; font-weight: bold; text-align: center; padding: 3px 1px; }
        .st-p { color: #1f7a45; }
        .st-f { color: #C8102E; }
        .st-r { color: #9a6b00; }
        .st-l { color: #1847b5; }
        th.tot { background-color: #082C6C; }
        td.pct { font-weight: bold; color: #082C6C; }
        .summary { margin-top: 12px; width: 100%; border-collapse: collapse; }
        .summary td { width: 20%; padding: 8px; background-color: #F4F6FA; border: 2px solid #fff; text-align: center; }
        .summary small { display: block; color: #5B6474; text-transform: uppercase; font-size: 7.5px; }
        .summary b { font-size: 14px; color: #082C6C; }
        .legend { margin-top: 8px; color: #5B6474; font-size: 8px; }
        .note { margin-top: 6px; padding: 6px 8px; background-color: #FEF6F7; color: #9E0B24; font-size: 8px; }
        .empty { padding: 20px; text-align: center; color: #5B6474; }
        .footer { position: fixed; bottom: -18px; left: 0; right: 0; font-size: 7.5px; color: #8a93a3; border-top: 1px solid #DDE3EC; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Asistencia</h1>
        <p class="sub">YURA Platform | Sistema de Seguimiento Pedagógico</p>
    </div>

    <table class="meta">
        <tr>
            <td><b>Asignatura:</b> {{ $subject->name ?? 'Todas' }}</td>
            <td><b>Curso y paralelo:</b> {{ $parallel ? ($parallel->course->name . ' - ' . $parallel->name) : 'Todos' }}</td>
            <td>
                <b>{{ $single ? 'Fecha:' : 'Periodo:' }}</b>
                {{ $dateFrom->format('d/m/Y') }}@unless($single) al {{ $dateTo->format('d/m/Y') }}@endunless
            </td>
        </tr>
        <tr>
            <td><b>Días con registro:</b> {{ $days->count() }}</td>
            <td><b>Generado por:</b> {{ Auth::user()->name }}</td>
            <td><b>Fecha de generación:</b> {{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    @if($students->isEmpty())
        <div class="empty">No hay estudiantes para mostrar.</div>
    @else
        <table class="list">
            <thead>
                <tr>
                    <th style="width: 22px;">Nro</th>
                    <th style="text-align: left;">Estudiante</th>
                    @if($showDays)
                        @foreach($days as $day)
                            <th class="day">{{ \Illuminate\Support\Carbon::parse($day)->format('d/m') }}</th>
                        @endforeach
                    @endif
                    <th class="tot c">P</th>
                    <th class="tot c">F</th>
                    <th class="tot c">R</th>
                    <th class="tot c">L</th>
                    <th class="tot c">% Asist.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $student)
                    @php $t = $totals[$student->id]; @endphp
                    <tr>
                        <td class="c">{{ $loop->iteration }}</td>
                        <td class="name"><b>{{ $student->name }}</b></td>
                        @if($showDays)
                            @foreach($days as $day)
                                @php $status = $grid[$student->id][$day] ?? null; @endphp
                                <td class="day {{ $classOf($status) }}">{{ $status ? ($letters[$status] ?? mb_substr($status, 0, 1)) : '·' }}</td>
                            @endforeach
                        @endif
                        <td class="c st-p">{{ $t['Presente'] }}</td>
                        <td class="c st-f">{{ $t['Falta'] }}</td>
                        <td class="c st-r">{{ $t['Atraso'] }}</td>
                        <td class="c st-l">{{ $t['Licencia'] }}</td>
                        <td class="c pct">{{ $t['percent'] === null ? '-' : $t['percent'] . '%' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($days->isEmpty())
            <div class="note">No hay asistencia registrada en este periodo para la asignatura y el paralelo elegidos.</div>
        @elseif(! $showDays)
            <div class="note">El periodo tiene {{ $days->count() }} días con registro: para que la tabla quepa en la hoja se muestra solo el total por estudiante. Para ver el detalle día por día, exporta un rango de un mes o menos.</div>
        @endif

        <table class="summary">
            <tr>
                <td><small>Estudiantes</small><b>{{ $students->count() }}</b></td>
                <td><small>Presentes</small><b>{{ $sum('Presente') }}</b></td>
                <td><small>Faltas</small><b>{{ $sum('Falta') }}</b></td>
                <td><small>Retrasos / Licencias</small><b>{{ $sum('Atraso') }} / {{ $sum('Licencia') }}</b></td>
                <td><small>% asistencia del grupo</small><b>{{ $groupPercent === null ? '-' : $groupPercent . '%' }}</b></td>
            </tr>
        </table>

        <p class="legend">
            P = Presente · F = Falta · R = Retraso · L = Licencia / permiso · «·» = sin registro.
            El porcentaje es Presente sobre el total de registros, sin contar las licencias.
        </p>
    @endif

    <div class="footer">© {{ date('Y') }} YURA Platform · V {{ config('app.version') }}</div>
</body>
</html>
