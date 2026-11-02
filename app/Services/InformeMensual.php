<?php

namespace App\Services;

use App\Models\Ausencia;
use App\Models\Fichaje;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Resumen mensual del registro de jornada de cada persona: día a día, con el total del mes,
 * las horas previstas por su horario y las que pasan de ese horario.
 *
 * Responde a tres obligaciones del Estatuto de los Trabajadores:
 * - art. 34.9: el registro tiene que estar a disposición de la persona trabajadora, de sus
 *   representantes y de la Inspección;
 * - art. 12.4.c: en el tiempo parcial, el registro se totaliza cada mes y se entrega una
 *   copia del resumen junto con la nómina;
 * - art. 35.5: las horas extraordinarias se registran día a día y se totalizan.
 *
 * Las "horas por encima del horario" se calculan contra el horario configurado en la app;
 * si son horas extraordinarias, complementarias o se compensan lo decide la empresa según
 * el contrato y el convenio (la app no lo sabe), por eso no se llaman así.
 *
 * El tenant tiene que estar fijado (petición web o Tenant::set()).
 */
class InformeMensual
{
    /**
     * Informe de un empleado en el mes de $mes (cualquier día del mes).
     *
     * @return array{empleado: User, mes: Carbon, dias: list<array{fecha: Carbon, jornadas: list<array>, horas: float, previstas: float, notas: list<string>}>, total: float, previstas: float, exceso: float, sinSalida: int}
     */
    public static function deEmpleado(User $empleado, Carbon $mes): array
    {
        return static::deVarios(collect([$empleado]), $mes)->first();
    }

    /**
     * Informes de varios empleados con un número fijo de consultas (fichajes con sus
     * relaciones y ausencias), sea cual sea el número de empleados o de días.
     *
     * @param  Collection<int, User>  $empleados
     * @return Collection<int, array>
     */
    public static function deVarios(Collection $empleados, Carbon $mes): Collection
    {
        $desde = $mes->copy()->startOfMonth();
        $hasta = $mes->copy()->endOfMonth();
        $ids = $empleados->pluck('id');

        // Un día antes y uno después: un turno de noche que empieza el último día del mes
        // acaba al siguiente, y uno que empezó el día anterior al mes no es de este mes.
        $fichajes = Fichaje::with(['usuario', 'correcciones', 'registradoPor'])
            ->whereIn('user_id', $ids)
            ->where('fecha_hora', '>=', $desde->copy()->subDay())
            ->where('fecha_hora', '<=', $hasta->copy()->addDay())
            ->orderBy('fecha_hora')
            ->orderBy('id')
            ->get();

        // Cada jornada cuenta para el día en que empezó (igual que en las incidencias).
        $jornadasPorEmpleadoYDia = JornadasAgrupador::agrupar($fichajes)
            ->filter(fn (array $j) => ($j['entrada'] ?? $j['salida'])->fecha_hora->between($desde, $hasta))
            ->groupBy(fn (array $j) => $j['usuario']->id.'|'.($j['entrada'] ?? $j['salida'])->fecha_hora->toDateString());

        $ausencias = Ausencia::where('estado', 'aprobada')
            ->whereIn('user_id', $ids)
            ->whereDate('fecha_inicio', '<=', $hasta)
            ->whereDate('fecha_fin', '>=', $desde)
            ->get()
            ->groupBy('user_id');

        return $empleados->map(function (User $empleado) use ($desde, $hasta, $jornadasPorEmpleadoYDia, $ausencias) {
            $dias = [];

            for ($dia = $desde->copy(); $dia->lte($hasta); $dia->addDay()) {
                $jornadas = $jornadasPorEmpleadoYDia->get($empleado->id.'|'.$dia->toDateString(), collect())
                    ->sortBy(fn (array $j) => ($j['entrada'] ?? $j['salida'])->fecha_hora)
                    ->values();

                $ausencia = ($ausencias->get($empleado->id) ?? collect())->first(fn (Ausencia $a) => $a->cubre($dia));
                $previstas = $ausencia ? 0.0 : static::horasPrevistas($empleado, $dia);

                // Un día sin fichajes solo sale si tocaba trabajar (para que se vea que no se
                // fichó) o si había una ausencia en un día laborable. Así no aparecen fines de
                // semana "de vacaciones".
                $diaLaborable = ! $empleado->tieneHorario() || $empleado->trabajaEnDia($dia);

                if ($jornadas->isEmpty() && $previstas === 0.0 && ! ($ausencia && $diaLaborable)) {
                    continue;
                }

                $dias[] = [
                    'fecha' => $dia->copy(),
                    'jornadas' => $jornadas->all(),
                    'horas' => round($jornadas->sum('horas'), 2),
                    'previstas' => $previstas,
                    'notas' => static::notas($jornadas, $ausencia),
                ];
            }

            $total = round(array_sum(array_column($dias, 'horas')), 2);
            $previstas = round(array_sum(array_column($dias, 'previstas')), 2);

            return [
                'empleado' => $empleado,
                'mes' => $desde->copy(),
                'dias' => $dias,
                'total' => $total,
                'previstas' => $previstas,
                // Solo si tiene horario: sin horario no hay con qué comparar.
                'exceso' => $empleado->tieneHorario() ? max(0.0, round($total - $previstas, 2)) : 0.0,
                'sinSalida' => collect($dias)->sum(fn ($d) => collect($d['jornadas'])->filter(fn ($j) => $j['entrada'] && ! $j['salida'])->count()),
            ];
        })->values();
    }

    /**
     * Horas que el horario prevé ese día (0 si no trabaja ese día, no tiene horario o aún
     * no estaba dado de alta). Un tramo de 22:00 a 06:00 cuenta 8 horas.
     */
    protected static function horasPrevistas(User $empleado, Carbon $dia): float
    {
        if (! $empleado->trabajaEnDia($dia) || $empleado->created_at?->copy()->startOfDay()->gt($dia)) {
            return 0.0;
        }

        $minutos = collect($empleado->tramos())->sum(function (array $t) {
            [$h1, $m1] = array_map('intval', explode(':', $t['entrada']));
            [$h2, $m2] = array_map('intval', explode(':', $t['salida']));
            $diferencia = ($h2 * 60 + $m2) - ($h1 * 60 + $m1);

            return $diferencia > 0 ? $diferencia : $diferencia + 24 * 60;
        });

        return round($minutos / 60, 2);
    }

    /**
     * Observaciones del día: ausencia aprobada, correcciones, fichajes añadidos por la
     * empresa y salidas que faltan.
     *
     * @return list<string>
     */
    protected static function notas(Collection $jornadas, ?Ausencia $ausencia): array
    {
        $notas = [];

        if ($ausencia) {
            // Una baja médica es un dato de salud (art. 9 RGPD): en un documento que puede
            // llegar a representantes o a la Inspección basta con "ausencia justificada".
            $notas[] = $ausencia->tipo === 'vacaciones' ? 'Vacaciones' : 'Ausencia justificada';
        }

        foreach ($jornadas as $j) {
            if ($j['correcciones']->isNotEmpty()) {
                $notas[] = 'Hora corregida ('.$j['correcciones']->pluck('motivo')->implode('; ').')';
            }

            foreach ([$j['entrada'], $j['salida']] as $f) {
                if ($f?->origen === 'empresa') {
                    $notas[] = ucfirst($f->tipo).' añadida por la empresa ('.$f->motivo.')';
                }
            }

            if ($j['entrada'] && ! $j['salida']) {
                $notas[] = 'Falta la salida';
            }
        }

        return $notas;
    }
}
