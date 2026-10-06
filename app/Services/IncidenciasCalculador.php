<?php

namespace App\Services;

use App\Models\Ausencia;
use App\Models\Cita;
use App\Models\Fichaje;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula, para un día (o un periodo), las incidencias de control horario:
 * retrasos, horas de más, empleados que no han fichado, fichajes que se
 * quedaron sin cerrar y citas de las que no se ha vuelto. Puramente de
 * presentación: no se guarda nada en base de datos, se recalcula a partir del
 * horario esperado de cada empleado (uno o varios tramos si es turno partido)
 * y de sus fichajes reales (ya con correcciones aplicadas, vía
 * JornadasAgrupador::horaEfectiva()).
 *
 * Quien tiene una ausencia aprobada ese día (vacaciones, baja...) no genera
 * incidencias, y una cita avisada (médico, etc.) justifica no estar durante
 * su franja: si coincide con el inicio de un tramo, la entrada esperada pasa
 * a la hora de vuelta.
 */
class IncidenciasCalculador
{
    // Margen antes de considerar algo una incidencia, para no avisar por
    // diferencias de un par de minutos sin importancia real.
    const MARGEN_MINUTOS = 10;

    // Tope de días de un informe por periodo (cada día son varias consultas).
    const MAX_DIAS_PERIODO = 93;

    const ETIQUETAS = [
        'retraso' => ['Retraso', 'danger'],
        'sin_fichar' => ['Sin fichar', 'danger'],
        'sin_cerrar' => ['Fichaje sin cerrar', 'warning'],
        'sin_volver' => ['No ha vuelto', 'warning'],
        'horas_de_mas' => ['Horas de más', 'indigo'],
    ];

    /**
     * @return Collection<int, array{tipo: string, empleado: User, detalle: string, fecha: Carbon, minutos: ?int, horas_extra: ?float}>
     */
    public static function delDia(int $empresaId, ?Carbon $fecha = null): Collection
    {
        // Fichaje está protegido por un scope global de tenant; lo fijamos
        // explícitamente aquí en vez de confiar en que ya lo haya puesto el
        // middleware de la petición — así el servicio funciona igual se
        // llame desde un controlador, un comando artisan o un test.
        Tenant::set($empresaId);

        $fecha = ($fecha ?? now())->copy()->startOfDay();

        if ($fecha->gt(now()->startOfDay())) {
            return collect();
        }

        $empleados = User::deEmpresa($empresaId)->where('activo', true)->get();

        if ($empleados->isEmpty()) {
            return collect();
        }

        $ids = $empleados->pluck('id');

        $fichajesPorUsuario = Fichaje::with(['usuario', 'correcciones'])
            ->whereIn('user_id', $ids)
            ->whereDate('fecha_hora', $fecha)
            ->orderBy('fecha_hora')
            ->get()
            ->groupBy('user_id');

        // Una entrada de ayer sin salida hoy no es olvido si la siguiente
        // marca es una salida (turno que cruza la medianoche).
        $primeraMarcaDelDiaSiguiente = Fichaje::whereIn('user_id', $ids)
            ->whereDate('fecha_hora', $fecha->copy()->addDay())
            ->orderBy('fecha_hora')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $marcas) => $marcas->first());

        $ausentes = Ausencia::where('estado', 'aprobada')
            ->whereIn('user_id', $ids)
            ->get()
            ->filter(fn (Ausencia $ausencia) => $ausencia->cubre($fecha))
            ->pluck('user_id')
            ->flip();

        // Citas (médico, etc.) avisadas para ese día: justifican la salida.
        $citasPorUsuario = Cita::vigentes()
            ->whereIn('user_id', $ids)
            ->whereDate('fecha', $fecha)
            ->get()
            ->groupBy('user_id');

        return $empleados
            // Nada de incidencias de días anteriores al alta del empleado
            // (si no, un empleado nuevo saldría "sin fichar" todo el mes).
            ->reject(fn (User $empleado) => $empleado->created_at->copy()->startOfDay()->gt($fecha))
            ->reject(fn (User $empleado) => $ausentes->has($empleado->id))
            ->flatMap(fn (User $empleado) => static::incidenciasDeEmpleado(
                $empleado,
                $fichajesPorUsuario->get($empleado->id, collect()),
                $fecha,
                $primeraMarcaDelDiaSiguiente->get($empleado->id),
                $citasPorUsuario->get($empleado->id, collect()),
            ))
            ->values();
    }

    /**
     * Incidencias de todos los días entre $desde y $hasta (ambos incluidos,
     * sin pasar de hoy ni de MAX_DIAS_PERIODO días).
     *
     * @return Collection<int, array{tipo: string, empleado: User, detalle: string, fecha: Carbon, minutos: ?int, horas_extra: ?float}>
     */
    public static function delPeriodo(int $empresaId, Carbon $desde, Carbon $hasta): Collection
    {
        $desde = $desde->copy()->startOfDay();
        $hasta = $hasta->copy()->startOfDay()->min(now()->startOfDay());
        $hasta = $hasta->min($desde->copy()->addDays(static::MAX_DIAS_PERIODO - 1));

        $incidencias = collect();

        for ($dia = $desde->copy(); $dia->lte($hasta); $dia->addDay()) {
            $incidencias = $incidencias->concat(static::delDia($empresaId, $dia));
        }

        return $incidencias->values();
    }

    protected static function incidenciasDeEmpleado(User $empleado, Collection $fichajesDelDia, Carbon $fecha, ?Fichaje $primeraMarcaDelDiaSiguiente, Collection $citas): array
    {
        $incidencias = [];
        $ahora = now();
        $margen = static::MARGEN_MINUTOS;
        $esPasado = $fecha->lt($ahora->copy()->startOfDay());
        $trabajaHoy = $empleado->trabajaEnDia($fecha);

        // [[inicio, fin], ...] de cada tramo del día y de cada cita.
        $tramos = $trabajaHoy ? static::tramosDelDia($empleado, $fecha) : [];
        $franjasCita = $citas->map(fn (Cita $c) => [$c->desde(), $c->hasta()])->all();

        $jornadas = JornadasAgrupador::agrupar($fichajesDelDia);
        $entradas = $fichajesDelDia->where('tipo', 'entrada');

        foreach ($tramos as $k => [$inicio, $fin]) {
            // Una cita que cubre todo el tramo lo justifica entero.
            if (collect($franjasCita)->contains(fn ($f) => $f[0]->lte($inicio) && $f[1]->gte($fin))) {
                continue;
            }

            $esperada = static::entradaEfectiva($inicio, $franjasCita);
            $limite = $esperada->copy()->addMinutes($margen);

            // Ventana de este tramo: del punto medio con el tramo anterior al
            // punto medio con el siguiente (así la vuelta de la pausa se
            // asigna al tramo de tarde y no al de mañana).
            $ventanaDesde = $k === 0 ? $fecha->copy() : static::puntoMedio($tramos[$k - 1][1], $inicio);
            $ventanaHasta = $k === array_key_last($tramos) ? $fecha->copy()->endOfDay() : static::puntoMedio($fin, $tramos[$k + 1][0]);

            $entradaDelTramo = $entradas
                ->map(fn (Fichaje $f) => [$f, JornadasAgrupador::horaEfectiva($f)])
                ->first(fn ($par) => $par[1]->between($ventanaDesde, $ventanaHasta));

            if ($entradaDelTramo) {
                $hora = $entradaDelTramo[1];

                if ($hora->gt($limite)) {
                    $incidencias[] = static::incidencia('retraso', $empleado, $fecha, sprintf(
                        $k === 0 ? 'Entró a las %s (esperado %s)' : 'Volvió a las %s (esperado %s)',
                        $hora->format('H:i'),
                        $esperada->format('H:i')
                    ), minutos: (int) $esperada->diffInMinutes($hora));
                }

                continue;
            }

            // Sin entrada en este tramo. Si lleva fichado desde antes (no
            // salió a la pausa) no se le puede pedir otra entrada.
            $siguePresente = $jornadas->contains(fn (array $j) => $j['entrada']
                && JornadasAgrupador::horaEfectiva($j['entrada'])->lt($inicio)
                && (! $j['salida'] || JornadasAgrupador::horaEfectiva($j['salida'])->gt($inicio)));

            // De la vuelta de la pausa solo se avisa si ya fichó algo ese día
            // (si no, ya sale "sin fichar" por la entrada del primer tramo).
            $avisar = $k === 0 || $fichajesDelDia->isNotEmpty();

            if ($avisar && ! $siguePresente && ($esPasado || $ahora->gt($limite))) {
                $incidencias[] = static::incidencia('sin_fichar', $empleado, $fecha, sprintf(
                    $k === 0 ? 'No ha fichado (esperado a las %s)' : 'No ha fichado la vuelta (esperado a las %s)',
                    $esperada->format('H:i')
                ));
            }
        }

        // Sin cerrar: un día ya pasado siempre; el de hoy solo si hay horario
        // y ya ha pasado la hora de salida del último tramo (antes es normal
        // seguir fichado).
        $ultimaSalida = $tramos !== [] ? end($tramos)[1] : null;
        $puedeEstarSinCerrar = $esPasado
            ? ! ($primeraMarcaDelDiaSiguiente && $primeraMarcaDelDiaSiguiente->tipo === 'salida')
            : ($ultimaSalida !== null && $ahora->gt($ultimaSalida->copy()->addMinutes($margen)));

        if ($puedeEstarSinCerrar) {
            foreach ($jornadas->filter(fn (array $j) => $j['entrada'] && ! $j['salida']) as $jornada) {
                $incidencias[] = static::incidencia('sin_cerrar', $empleado, $fecha, sprintf(
                    'Entrada a las %s sin salida',
                    JornadasAgrupador::horaEfectiva($jornada['entrada'])->format('H:i')
                ));
            }
        }

        // No ha vuelto de una cita: avisó de que volvía a una hora, ya pasó,
        // sigue fuera (su última marca es una salida) y aún le toca trabajar.
        $ultimaMarca = $fichajesDelDia->last();

        if (! $esPasado && $tramos !== [] && $ultimaMarca && $ultimaMarca->tipo === 'salida') {
            foreach ($citas as $cita) {
                $vuelta = $cita->hasta();
                $dentroDeUnTramo = collect($tramos)->contains(fn ($t) => $vuelta->gte($t[0]) && $vuelta->lt($t[1]));

                if ($dentroDeUnTramo
                    && $ahora->gt($vuelta->copy()->addMinutes($margen))
                    && JornadasAgrupador::horaEfectiva($ultimaMarca)->lte($vuelta->copy()->addMinutes($margen))) {
                    $incidencias[] = static::incidencia('sin_volver', $empleado, $fecha, sprintf(
                        'Salida avisada (%s): volvía a las %s y no ha fichado la vuelta',
                        $cita->descripcion(),
                        $vuelta->format('H:i')
                    ));
                }
            }
        }

        if ($tramos !== []) {
            $horasTrabajadas = $jornadas->sum('horas');
            $horasEsperadas = collect($tramos)->sum(fn ($t) => $t[0]->floatDiffInMinutes($t[1])) / 60;

            if ($horasTrabajadas > $horasEsperadas + ($margen / 60)) {
                $incidencias[] = static::incidencia('horas_de_mas', $empleado, $fecha, sprintf(
                    '%s h trabajadas (esperadas %s h)',
                    number_format($horasTrabajadas, 2),
                    number_format($horasEsperadas, 2)
                ), horasExtra: round($horasTrabajadas - $horasEsperadas, 2));
            }
        }

        return $incidencias;
    }

    /**
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    protected static function tramosDelDia(User $empleado, Carbon $fecha): array
    {
        return collect($empleado->tramos())
            ->map(fn (array $t) => [
                Carbon::parse($fecha->toDateString().' '.$t['entrada']),
                Carbon::parse($fecha->toDateString().' '.$t['salida']),
            ])
            ->all();
    }

    /**
     * Si una cita cubre el inicio del tramo, no se espera al empleado hasta
     * que acabe la cita (encadenando si hay varias seguidas).
     *
     * @param  list<array{0: Carbon, 1: Carbon}>  $franjasCita
     */
    protected static function entradaEfectiva(Carbon $inicio, array $franjasCita): Carbon
    {
        $efectiva = $inicio->copy();

        do {
            $movida = false;

            foreach ($franjasCita as [$desde, $hasta]) {
                if ($desde->lte($efectiva) && $hasta->gt($efectiva)) {
                    $efectiva = $hasta->copy();
                    $movida = true;
                }
            }
        } while ($movida);

        return $efectiva;
    }

    protected static function puntoMedio(Carbon $a, Carbon $b): Carbon
    {
        return $a->copy()->addSeconds((int) ($a->diffInSeconds($b) / 2));
    }

    protected static function incidencia(string $tipo, User $empleado, Carbon $fecha, string $detalle, ?int $minutos = null, ?float $horasExtra = null): array
    {
        return [
            'tipo' => $tipo,
            'empleado' => $empleado,
            'detalle' => $detalle,
            'fecha' => $fecha->copy(),
            'minutos' => $minutos,
            'horas_extra' => $horasExtra,
        ];
    }
}
