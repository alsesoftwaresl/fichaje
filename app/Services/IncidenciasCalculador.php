<?php

namespace App\Services;

use App\Models\Fichaje;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula, para un día dado, qué empleados han llegado tarde o han hecho más
 * horas de las esperadas. Puramente de presentación: no se guarda nada en
 * base de datos, se recalcula a partir del horario esperado de cada
 * empleado (users.hora_entrada_esperada/hora_salida_esperada/dias_laborables)
 * y de sus fichajes reales de ese día (ya con correcciones aplicadas, vía
 * JornadasAgrupador::horaEfectiva()).
 */
class IncidenciasCalculador
{
    // Margen antes de considerar algo una incidencia, para no avisar por
    // diferencias de un par de minutos sin importancia real.
    const MARGEN_MINUTOS = 10;

    /**
     * @return Collection<int, array{tipo: string, empleado: User, detalle: string}>
     */
    public static function delDia(int $empresaId, ?Carbon $fecha = null): Collection
    {
        // Fichaje está protegido por un scope global de tenant; lo fijamos
        // explícitamente aquí en vez de confiar en que ya lo haya puesto el
        // middleware de la petición — así el servicio funciona igual se
        // llame desde un controlador, un comando artisan o un test.
        Tenant::set($empresaId);

        $fecha = ($fecha ?? now())->copy()->startOfDay();

        $empleadosConHorarioHoy = User::deEmpresa($empresaId)
            ->where('activo', true)
            ->get()
            ->filter(fn (User $empleado) => $empleado->trabajaEnDia($fecha));

        if ($empleadosConHorarioHoy->isEmpty()) {
            return collect();
        }

        $fichajesPorUsuario = Fichaje::with(['usuario', 'correcciones'])
            ->whereIn('user_id', $empleadosConHorarioHoy->pluck('id'))
            ->whereDate('fecha_hora', $fecha)
            ->orderBy('fecha_hora')
            ->get()
            ->groupBy('user_id');

        return $empleadosConHorarioHoy
            ->flatMap(fn (User $empleado) => static::incidenciasDeEmpleado(
                $empleado,
                $fichajesPorUsuario->get($empleado->id, collect()),
                $fecha
            ))
            ->values();
    }

    protected static function incidenciasDeEmpleado(User $empleado, Collection $fichajesDelDia, Carbon $fecha): array
    {
        $incidencias = [];

        $primeraEntrada = $fichajesDelDia->firstWhere('tipo', 'entrada');

        if ($primeraEntrada) {
            $horaEntradaReal = JornadasAgrupador::horaEfectiva($primeraEntrada);
            $horaEsperadaConMargen = Carbon::parse($fecha->toDateString().' '.$empleado->hora_entrada_esperada)
                ->addMinutes(static::MARGEN_MINUTOS);

            if ($horaEntradaReal->gt($horaEsperadaConMargen)) {
                $incidencias[] = [
                    'tipo' => 'retraso',
                    'empleado' => $empleado,
                    'detalle' => sprintf(
                        'Entró a las %s (esperado %s)',
                        $horaEntradaReal->format('H:i'),
                        substr($empleado->hora_entrada_esperada, 0, 5)
                    ),
                ];
            }
        }

        $horasTrabajadas = JornadasAgrupador::agrupar($fichajesDelDia)->sum('horas');
        $horasEsperadas = Carbon::parse($empleado->hora_entrada_esperada)
            ->floatDiffInMinutes(Carbon::parse($empleado->hora_salida_esperada)) / 60;

        if ($horasTrabajadas > $horasEsperadas + (static::MARGEN_MINUTOS / 60)) {
            $incidencias[] = [
                'tipo' => 'horas_de_mas',
                'empleado' => $empleado,
                'detalle' => sprintf('%s h trabajadas (esperadas %s h)', number_format($horasTrabajadas, 2), number_format($horasEsperadas, 2)),
            ];
        }

        return $incidencias;
    }
}
