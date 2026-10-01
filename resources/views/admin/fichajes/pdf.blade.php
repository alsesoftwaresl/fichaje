<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .subtitulo { font-size: 11px; color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) td { background: #f8fafc; }
        .muted { color: #94a3b8; }
    </style>
</head>
<body>
    <h1>Fichajes — {{ $empresa->nombre }}</h1>
    <p class="subtitulo">
        Generado el {{ now()->format('d/m/Y H:i') }}
        @if (!empty($filtros['desde']) || !empty($filtros['hasta']))
            · Periodo: {{ $filtros['desde'] ?? '—' }} a {{ $filtros['hasta'] ?? '—' }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Empleado</th>
                <th>DNI/NIE</th>
                <th>Fecha</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Horas</th>
                <th>Correcciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jornadas as $jornada)
                @php $fichajeRef = $jornada['entrada'] ?? $jornada['salida']; @endphp
                <tr>
                    <td>{{ $jornada['usuario']->name }}</td>
                    <td>{{ $jornada['usuario']->dni_nie ?? '—' }}</td>
                    <td>{{ $fichajeRef->fecha_hora->format('d/m/Y') }}</td>
                    <td>{{ $jornada['entrada']?->fecha_hora->format('H:i:s') ?? '—' }}</td>
                    <td>{{ $jornada['salida']?->fecha_hora->format('H:i:s') ?? 'En curso' }}</td>
                    <td>{{ $jornada['horas'] !== null ? number_format($jornada['horas'], 2).' h' : '—' }}</td>
                    <td>{{ $jornada['correcciones']->map(fn ($c) => $c->motivo)->implode(' | ') ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted">No hay fichajes con estos filtros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
