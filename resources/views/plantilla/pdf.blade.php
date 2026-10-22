<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registro diario de jornada — {{ $nombreMes }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }
        h1 { font-size: 16px; color: #1b1b54; margin: 0 0 8px; }
        .datos { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .datos td { padding: 4px 0; }
        .datos .etq { width: 14%; font-weight: bold; }
        .datos .linea { border-bottom: 1px solid #64748b; }
        table.reg { width: 100%; border-collapse: collapse; }
        table.reg th { background: #1b1b54; color: #fff; font-size: 9px; padding: 5px 3px; border: 1px solid #1b1b54; }
        table.reg td { border: 1px solid #cbd5e1; height: 15px; padding: 1px 4px; text-align: center; }
        table.reg td.dia { text-align: left; font-weight: bold; width: 11%; }
        tr.finde td { background: #eef2f7; }
        tr.total td { background: #e2f8f2; font-weight: bold; height: 18px; }
        .nota { margin-top: 8px; font-size: 8.5px; color: #475569; line-height: 1.4; }
        .pie { margin-top: 6px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>REGISTRO DIARIO DE JORNADA</h1>

    <table class="datos">
        <tr>
            <td class="etq">Empresa:</td><td class="linea" style="width:36%"></td>
            <td style="width:4%"></td>
            <td class="etq">NIF/CIF:</td><td class="linea"></td>
        </tr>
        <tr>
            <td class="etq">Trabajador/a:</td><td class="linea"></td>
            <td></td>
            <td class="etq">DNI/NIE:</td><td class="linea"></td>
        </tr>
        <tr>
            <td class="etq">Mes y año:</td><td colspan="4"><strong>{{ $nombreMes }}</strong></td>
        </tr>
    </table>

    @php
        $dias = [1 => 'lun', 2 => 'mar', 3 => 'mié', 4 => 'jue', 5 => 'vie', 6 => 'sáb', 7 => 'dom'];
    @endphp

    <table class="reg">
        <thead>
            <tr>
                <th>Día</th><th>Entrada</th><th>Salida</th><th>Pausa (min)</th><th>Horas</th><th style="width:30%">Observaciones / firma</th>
            </tr>
        </thead>
        <tbody>
            @for ($d = 1; $d <= $mes->daysInMonth; $d++)
                @php $fecha = $mes->copy()->day($d); @endphp
                <tr class="{{ $fecha->isWeekend() ? 'finde' : '' }}">
                    <td class="dia">{{ sprintf('%02d', $d) }} {{ $dias[$fecha->dayOfWeekIso] }}</td>
                    <td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endfor
            <tr class="total"><td class="dia" colspan="4">Total de horas del mes</td><td></td><td></td></tr>
        </tbody>
    </table>

    <p class="nota">
        Anota la hora de entrada y de salida de cada día (por ejemplo 08:30). Conserva este registro cuatro años a
        disposición de la persona trabajadora, sus representantes legales y la Inspección de Trabajo.
    </p>
    <p class="pie">Plantilla orientativa de {{ config('app.name') }} ({{ parse_url(config('app.url'), PHP_URL_HOST) }}). No sustituye al asesoramiento laboral.</p>
</body>
</html>
