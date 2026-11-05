<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Constancia de estudiante regular</title>
    <style>
        @page { margin: 70px 70px 60px 85px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 12.5px; color: #1f2937; line-height: 1.7; }
        .franja { height: 6px; margin-bottom: 18px; }
        .franja span { display: inline-block; width: 33.2%; height: 6px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header .org { font-size: 15px; font-weight: bold; color: #082C6C; text-transform: uppercase; letter-spacing: 1px; }
        .header .sub { font-size: 10px; color: #5B6474; }
        h1 { text-align: center; font-size: 17px; color: #082C6C; letter-spacing: 2px; margin: 10px 0 30px; text-decoration: underline; }
        p.cuerpo { text-align: justify; margin: 0 0 16px; }
        .datos { margin: 6px 0 22px 30px; }
        .datos td { padding: 2px 12px 2px 0; vertical-align: top; }
        .datos td.label { font-weight: bold; color: #082C6C; white-space: nowrap; }
        .fecha { text-align: right; margin-top: 26px; }
        .firmas { width: 100%; margin-top: 90px; }
        .firmas td { width: 50%; text-align: center; vertical-align: top; padding: 0 20px; }
        .linea { border-top: 1px solid #1f2937; padding-top: 6px; font-weight: bold; }
        .cargo { font-size: 11px; color: #5B6474; }
        .pie { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 8.5px; color: #8a94a6; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="franja"><span style="background:#C8102E"></span><span style="background:#dfe5ee"></span><span style="background:#0B3D91"></span></div>

    <div class="header">
        <div class="org">{{ $orgName }}</div>
        <div class="sub">Dirección — Secretaría</div>
    </div>

    <h1>CONSTANCIA DE ESTUDIANTE REGULAR</h1>

    <p class="cuerpo">
        La Dirección de <strong>{{ $orgName }}</strong>, a solicitud de la parte interesada,
        hace constar que el(la) estudiante:
    </p>

    <table class="datos">
        <tr><td class="label">Nombre completo:</td><td>{{ $student->name }}</td></tr>
        <tr><td class="label">Curso:</td><td>{{ $student->course_name }}</td></tr>
        <tr><td class="label">Paralelo:</td><td>{{ $student->parallel?->name ?? '—' }}</td></tr>
        <tr><td class="label">Gestión:</td><td>{{ $year?->name ?? now()->year }}</td></tr>
    </table>

    <p class="cuerpo">
        se encuentra inscrito(a) y asiste regularmente a clases en esta unidad educativa durante la gestión
        {{ $year?->year ?? now()->year }}, según los registros del sistema académico.
    </p>

    <p class="cuerpo">
        Se extiende la presente constancia para los fines que el(la) interesado(a) considere convenientes.
    </p>

    <p class="fecha">{{ $placeAndDate }}</p>

    <table class="firmas">
        <tr>
            <td><div class="linea">Director(a)</div><div class="cargo">{{ $orgName }}</div></td>
            <td><div class="linea">Secretaría</div><div class="cargo">{{ $orgName }}</div></td>
        </tr>
    </table>

    <div class="pie">Documento generado por YURA Platform el {{ now()->format('d/m/Y H:i') }} — Emitido por: {{ $issuedBy?->name }}. Sin firma y sello no tiene validez.</div>
</body>
</html>
