<?php

namespace App\Services;

use App\Models\Fichaje;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Único punto por el que se registra un fichaje (web y kiosco), para que las dos vías
 * apliquen las mismas reglas:
 *
 * - Entrada o salida se deduce del último fichaje, pero una entrada abierta desde hace
 *   más de Fichaje::HORAS_MAXIMAS_JORNADA horas se da por olvidada: el siguiente fichaje
 *   vuelve a ser una entrada (y no una "salida" que cerraría una jornada de un día entero).
 *   La entrada olvidada queda sin salida para que la empresa la complete.
 * - Un segundo fichaje del mismo empleado a menos de Fichaje::SEGUNDOS_ENTRE_FICHAJES se
 *   ignora (doble toque en el kiosco, doble clic en la web).
 */
class RegistroFichajes
{
    /**
     * Qué toca fichar después de $ultimo.
     */
    public static function siguienteTipo(?Fichaje $ultimo, ?Carbon $ahora = null): string
    {
        if ($ultimo?->tipo !== 'entrada') {
            return 'entrada';
        }

        return static::entradaOlvidada($ultimo, $ahora) ? 'entrada' : 'salida';
    }

    /**
     * true si $fichaje es una entrada que lleva abierta demasiado tiempo para ser la
     * jornada en curso.
     */
    public static function entradaOlvidada(?Fichaje $fichaje, ?Carbon $ahora = null): bool
    {
        return $fichaje?->tipo === 'entrada'
            && $fichaje->fecha_hora->lt(($ahora ?? now())->copy()->subHours(Fichaje::HORAS_MAXIMAS_JORNADA));
    }

    /**
     * Registra el fichaje de $usuario. El tenant ya tiene que estar fijado.
     *
     * Un bloqueo por empleado serializa dos envíos simultáneos (el doble toque llega casi a
     * la vez): el segundo espera, ve el fichaje recién creado y se descarta.
     *
     * Devuelve 'fichaje' null cuando 'repetido' es true, y en 'olvidada' la entrada
     * anterior que quedó sin salida, si la hay.
     *
     * @return array{tipo: string, fichaje: ?Fichaje, repetido: bool, olvidada: ?Fichaje}
     */
    public static function registrar(User $usuario, string $origen, Request $request): array
    {
        try {
            return Cache::lock('fichar:'.$usuario->id, 10)
                ->block(5, fn () => static::registrarSinBloqueo($usuario, $origen, $request));
        } catch (LockTimeoutException) {
            // Otro fichaje del mismo empleado sigue en curso: es el mismo doble toque.
            return ['tipo' => 'entrada', 'fichaje' => null, 'repetido' => true, 'olvidada' => null];
        }
    }

    protected static function registrarSinBloqueo(User $usuario, string $origen, Request $request): array
    {
        $ultimo = Fichaje::where('user_id', $usuario->id)
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->first();

        if ($ultimo && $ultimo->fecha_hora->gt(now()->subSeconds(Fichaje::SEGUNDOS_ENTRE_FICHAJES))) {
            return ['tipo' => $ultimo->tipo, 'fichaje' => null, 'repetido' => true, 'olvidada' => null];
        }

        $tipo = static::siguienteTipo($ultimo);

        $fichaje = Fichaje::create([
            'user_id' => $usuario->id,
            'tipo' => $tipo,
            // Hora del servidor, nunca la que envíe el navegador.
            'fecha_hora' => now(),
            'origen' => $origen,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return [
            'tipo' => $tipo,
            'fichaje' => $fichaje,
            'repetido' => false,
            'olvidada' => static::entradaOlvidada($ultimo) ? $ultimo : null,
        ];
    }

    /**
     * Texto para el empleado cuando su última entrada se quedó sin salida.
     */
    public static function avisoEntradaOlvidada(Fichaje $entrada): string
    {
        return 'Tu entrada del '.$entrada->fecha_hora->format('d/m').' a las '.$entrada->fecha_hora->format('H:i')
            .' quedó sin salida. Avisa a tu empresa para que la complete.';
    }
}
