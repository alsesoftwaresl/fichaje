{{--
    Informe mensual del registro de jornada (InformeMensual): una página por persona, con el
    detalle diario, los totales y espacio para las firmas. Lo usan el admin (de todos o de
    uno) y cada empleado (el suyo). DejaVu Sans para que dompdf pinte bien las tildes.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        .sub { color: #64748b; margin: 0 0 10px; }
        .datos { width: 100%; margin-bottom: 10px; }
        .datos td { padding: 2px 0; vertical-align: top; }
        .etiqueta { color: #64748b; width: 110px; }
        table.dias { width: 100%; border-collapse: collapse; }
        table.dias th { background: #f1f5f9; text-align: left; padding: 4px 5px; font-size: 8px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
        table.dias td { padding: 4px 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .fecha { white-space: nowrap; width: 62px; }
        .muted { color: #94a3b8; }
        .totales { margin-top: 8px; width: 100%; border-collapse: collapse; }
        .totales td { padding: 4px 5px; border: 1px solid #cbd5e1; }
        .totales .valor { text-align: right; font-weight: bold; width: 90px; }
        .firmas { width: 100%; margin-top: 28px; }
        .firmas td { width: 50%; padding-top: 34px; border-top: 1px solid #94a3b8; text-align: center; color: #475569; }
        .pie { margin-top: 14px; font-size: 7.5px; color: #64748b; }
        .salto { page-break-after: always; }
    </style>
</head>
<body>
    @foreach ($informes as $informe)
        @php $e = $informe['empleado']; @endphp
        <div class="{{ $loop->last ? '' : 'salto' }}">
            <h1>Registro de jornada — {{ ucfirst($informe['mes']->locale('es')->translatedFormat('F Y')) }}</h1>
            <p class="sub">Resumen mensual (arts. 34.9, 12.4.c y 35.5 del Estatuto de los Trabajadores)</p>

            <table class="datos">
                <tr>
                    <td class="etiqueta">Empresa</td>
                    <td>{{ $empresa->nombre }}@if ($empresa->nif) · CIF/NIF {{ $empresa->nif }}@endif</td>
                </tr>
                <tr>
                    <td class="etiqueta">Persona trabajadora</td>
                    <td>{{ $e->name }}@if ($e->dni_nie) · DNI/NIE {{ $e->dni_nie }}@endif</td>
                </tr>
                <tr>
                    <td class="etiqueta">Horario previsto</td>
                    <td>
                        @if ($e->tieneHorario())
                            {{ collect($e->tramos())->map(fn ($t) => $t['entrada'].'–'.$t['salida'])->implode(' y ') }}
                            ({{ collect($e->dias_laborables)->sort()->map(fn ($d) => ['', 'L', 'M', 'X', 'J', 'V', 'S', 'D'][(int) $d])->implode(' ') }})
                        @else
                            Sin horario configurado en la aplicación
                        @endif
                    </td>
                </tr>
            </table>

            <table class="dias">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Entradas y salidas</th>
                        <th class="num">Horas</th>
                        <th class="num">Previstas</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($informe['dias'] as $dia)
                        <tr>
                            <td class="fecha">{{ $dia['fecha']->locale('es')->translatedFormat('D d/m') }}</td>
                            <td>
                                @forelse ($dia['jornadas'] as $j)
                                    {{ $j['entrada'] ? App\Services\JornadasAgrupador::horaEfectiva($j['entrada'])->format('H:i') : '—' }}
                                    –
                                    @if ($j['salida'])
                                        @php $salida = App\Services\JornadasAgrupador::horaEfectiva($j['salida']); @endphp
                                        {{ $salida->format('H:i') }}@if (! $salida->isSameDay($dia['fecha'])) (+1)@endif
                                    @else
                                        sin salida
                                    @endif
                                    @if (! $loop->last)<br>@endif
                                @empty
                                    <span class="muted">Sin fichajes</span>
                                @endforelse
                            </td>
                            <td class="num">{{ number_format($dia['horas'], 2, ',', '.') }}</td>
                            <td class="num">{{ $dia['previstas'] > 0 ? number_format($dia['previstas'], 2, ',', '.') : '—' }}</td>
                            <td>{{ implode(' · ', $dia['notas']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Sin fichajes ni horario previsto este mes.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="totales">
                <tr>
                    <td>Total de horas trabajadas en el mes</td>
                    <td class="valor">{{ number_format($informe['total'], 2, ',', '.') }} h</td>
                </tr>
                @if ($e->tieneHorario())
                    <tr>
                        <td>Horas previstas por el horario</td>
                        <td class="valor">{{ number_format($informe['previstas'], 2, ',', '.') }} h</td>
                    </tr>
                    <tr>
                        <td>Horas por encima del horario previsto</td>
                        <td class="valor">{{ number_format($informe['exceso'], 2, ',', '.') }} h</td>
                    </tr>
                @endif
                @if ($informe['sinSalida'] > 0)
                    <tr>
                        <td>Jornadas sin salida registrada (no suman horas)</td>
                        <td class="valor">{{ $informe['sinSalida'] }}</td>
                    </tr>
                @endif
            </table>

            <table class="firmas">
                <tr>
                    <td>Firma de la empresa</td>
                    <td>Firma de la persona trabajadora (recibí)</td>
                </tr>
            </table>

            <p class="pie">
                Las horas se calculan con la hora válida de cada fichaje: si se corrigió, la corregida, y la hora
                registrada originalmente se conserva en el registro junto con el motivo y el autor de la corrección.
                Cada jornada cuenta en el día en que empezó; (+1) indica una salida al día siguiente. Las horas por
                encima del horario se comparan con el horario configurado en la aplicación; si son horas
                extraordinarias o complementarias depende del contrato y del convenio aplicable.
                Generado el {{ now()->format('d/m/Y H:i') }}.
            </p>
        </div>
    @endforeach
</body>
</html>
