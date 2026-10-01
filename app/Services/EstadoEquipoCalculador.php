<?php

namespace App\Services;

use App\Models\Ausencia;
use App\Models\Fichaje;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula, para un día dado, el estado de cada empleado activo: de
 * vacaciones/baja (ausencia aprobada que cubre ese día), trabajando (su
 * último fichaje del día es una entrada sin salida) o fuera (cualquier otro
 * caso). Puramente de presentación, no persiste nada.
 */
class EstadoEquipoCalculador
{
    /**
     * @return Collection<int, array{empleado: User, estado: string, detalle: ?string}>
     */
    public static function delDia(int $empresaId, ?Carbon $fecha = null): Collection
    {
        // Fichaje/Ausencia están protegidos por un scope global de tenant;
        // lo fijamos explícitamente en vez de confiar en que ya lo haya
        // puesto el middleware de la petición.
        Tenant::set($empresaId);

        $fecha = ($fecha ?? now())->copy();

        $empleados = User::deEmpresa($empresaId)->where('activo', true)->orderBy('name')->get();

        // Ausencia ya viene auto-filtrada a la empresa actual (BelongsToTenant).
        $ausenciaPorUsuario = Ausencia::where('estado', 'aprobada')
            ->whereIn('user_id', $empleados->pluck('id'))
            ->get()
            ->filter(fn (Ausencia $ausencia) => $ausencia->cubre($fecha))
            ->keyBy('user_id');

        $ultimoFichajeHoyPorUsuario = Fichaje::whereIn('user_id', $empleados->pluck('id'))
            ->whereDate('fecha_hora', $fecha)
            ->orderBy('fecha_hora')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $fichajesDelDia) => $fichajesDelDia->last());

        return $empleados->map(function (User $empleado) use ($ausenciaPorUsuario, $ultimoFichajeHoyPorUsuario) {
            $ausencia = $ausenciaPorUsuario->get($empleado->id);

            if ($ausencia) {
                return [
                    'empleado' => $empleado,
                    'estado' => $ausencia->tipo === 'vacaciones' ? 'vacaciones' : 'baja',
                    'detalle' => match ($ausencia->tipo) {
                        'vacaciones' => 'Vacaciones',
                        'baja_medica' => 'Baja médica',
                        default => 'Ausencia',
                    },
                ];
            }

            $ultimoFichaje = $ultimoFichajeHoyPorUsuario->get($empleado->id);

            if ($ultimoFichaje && $ultimoFichaje->tipo === 'entrada') {
                return [
                    'empleado' => $empleado,
                    'estado' => 'trabajando',
                    'detalle' => 'Desde las '.$ultimoFichaje->fecha_hora->format('H:i'),
                ];
            }

            return ['empleado' => $empleado, 'estado' => 'fuera', 'detalle' => null];
        })->values();
    }
}
