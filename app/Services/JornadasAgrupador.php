<?php

namespace App\Services;

use App\Models\Fichaje;
use Illuminate\Support\Collection;

/**
 * Agrupa fichajes (eventos individuales, uno por entrada/salida) en
 * "jornadas" de cara a mostrarlos/exportarlos como una sola fila con hora
 * de entrada, hora de salida y horas trabajadas. Es puramente de
 * presentación: la tabla `fichajes` sigue siendo append-only, un evento por
 * fila — esto no cambia el modelo de datos ni el registro legal.
 */
class JornadasAgrupador
{
    /**
     * @param  Collection<int, Fichaje>  $fichajes  Deben traer cargadas las
     *                                               relaciones 'usuario' y 'correcciones'.
     * @return Collection<int, array{usuario: mixed, entrada: ?Fichaje, salida: ?Fichaje, horas: ?float, correcciones: Collection}>
     */
    public static function agrupar(Collection $fichajes): Collection
    {
        return $fichajes
            ->groupBy('user_id')
            ->flatMap(fn (Collection $deUnUsuario) => static::agruparDeUnUsuario($deUnUsuario->sortBy('fecha_hora')->values()))
            ->sortByDesc(fn (array $jornada) => ($jornada['entrada'] ?? $jornada['salida'])->fecha_hora)
            ->values();
    }

    /**
     * Empareja las entradas y salidas de un usuario en jornadas, respetando el orden. Una
     * entrada sin salida queda como jornada en curso.
     */
    protected static function agruparDeUnUsuario(Collection $fichajesOrdenados): Collection
    {
        $jornadas = collect();
        $entradaPendiente = null;

        foreach ($fichajesOrdenados as $fichaje) {
            if ($fichaje->tipo === 'entrada') {
                if ($entradaPendiente) {
                    // Dos entradas seguidas sin salida intermedia (no debería
                    // ocurrir con el toggle normal, pero no lo descartamos).
                    $jornadas->push(static::jornada($entradaPendiente, null));
                }
                $entradaPendiente = $fichaje;

                continue;
            }

            // tipo === 'salida'
            $jornadas->push(static::jornada($entradaPendiente, $fichaje));
            $entradaPendiente = null;
        }

        if ($entradaPendiente) {
            // Sigue fichado (todavía no hay salida para esta entrada).
            $jornadas->push(static::jornada($entradaPendiente, null));
        }

        return $jornadas;
    }

    /**
     * Construye una jornada (entrada, salida, horas trabajadas y correcciones) usando la
     * hora corregida cuando existe.
     */
    protected static function jornada(?Fichaje $entrada, ?Fichaje $salida): array
    {
        // Las horas trabajadas se calculan con la hora corregida cuando
        // existe (si hay varias correcciones sobre el mismo fichaje, la más
        // reciente gana) — el fichaje original en sí nunca se toca, pero no
        // tendría sentido mostrar unas "horas trabajadas" que ignoren una
        // corrección ya aplicada.
        $horaEntrada = static::horaEfectiva($entrada);
        $horaSalida = static::horaEfectiva($salida);

        $horas = ($horaEntrada && $horaSalida)
            ? round($horaEntrada->floatDiffInMinutes($horaSalida) / 60, 2)
            : null;

        return [
            'usuario' => ($entrada ?? $salida)->usuario,
            'entrada' => $entrada,
            'salida' => $salida,
            'horas' => $horas,
            'correcciones' => collect([$entrada, $salida])
                ->filter()
                ->flatMap(fn (Fichaje $f) => $f->correcciones),
        ];
    }

    /**
     * Hora real de un fichaje teniendo en cuenta su corrección más reciente
     * (si tiene alguna) — reutilizada también por IncidenciasCalculador para
     * que una corrección ya aplicada se refleje igual ahí.
     */
    public static function horaEfectiva(?Fichaje $fichaje): ?\Illuminate\Support\Carbon
    {
        if (! $fichaje) {
            return null;
        }

        $ultimaCorreccion = $fichaje->correcciones->sortByDesc('created_at')->first();

        return $ultimaCorreccion?->fecha_hora_corregida ?? $fichaje->fecha_hora;
    }
}
