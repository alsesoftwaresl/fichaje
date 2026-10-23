<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Fichaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Completa una jornada que se quedó sin salida porque el empleado olvidó ficharla.
 *
 * Una corrección solo cambia la hora de un fichaje que existe; aquí falta el fichaje entero,
 * así que se añade uno nuevo de tipo salida con origen "empresa", quién lo registra y el
 * motivo. Nada existente se modifica: el registro sigue siendo inalterable y trazable.
 */
class SalidaOlvidadaController extends Controller
{
    /**
     * Añade la salida de la entrada $fichaje a la hora indicada, si tiene sentido
     * (posterior a la entrada, no futura, antes del siguiente fichaje y dentro del máximo
     * de jornada).
     */
    public function store(Request $request, Fichaje $fichaje): RedirectResponse
    {
        // $fichaje ya viene filtrado por empresa (scope de tenant en el route-model-binding).
        $data = $request->validate([
            'fecha_hora' => ['required', 'date'],
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        $siguiente = Fichaje::where('user_id', $fichaje->user_id)
            ->where(fn ($q) => $q->where('fecha_hora', '>', $fichaje->fecha_hora)
                ->orWhere(fn ($q) => $q->where('fecha_hora', $fichaje->fecha_hora)->where('id', '>', $fichaje->id)))
            ->orderBy('fecha_hora')
            ->orderBy('id')
            ->first();

        if ($fichaje->tipo !== 'entrada' || $siguiente?->tipo === 'salida') {
            throw ValidationException::withMessages(['fecha_hora' => 'Esta jornada ya tiene salida. Si la hora está mal, usa "Corregir salida".']);
        }

        $salida = Carbon::parse($data['fecha_hora']);
        $limite = $fichaje->fecha_hora->copy()->addHours(Fichaje::HORAS_MAXIMAS_JORNADA);

        $error = match (true) {
            $salida->lte($fichaje->fecha_hora) => 'La salida tiene que ser posterior a la entrada ('.$fichaje->fecha_hora->format('d/m/Y H:i').').',
            $salida->gt(now()) => 'La salida no puede ser una hora futura.',
            $salida->gt($limite) => 'Una jornada no puede durar más de '.Fichaje::HORAS_MAXIMAS_JORNADA.' horas.',
            $siguiente !== null && $salida->gte($siguiente->fecha_hora) => 'La salida tiene que ser anterior al siguiente fichaje del empleado ('.$siguiente->fecha_hora->format('d/m/Y H:i').').',
            default => null,
        };

        if ($error) {
            throw ValidationException::withMessages(['fecha_hora' => $error]);
        }

        $nueva = Fichaje::create([
            'user_id' => $fichaje->user_id,
            'tipo' => 'salida',
            'fecha_hora' => $salida,
            'origen' => 'empresa',
            'registrado_por' => Auth::id(),
            'motivo' => $data['motivo'],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        AuditLog::registrar('salida_olvidada_anadida', $nueva, [
            'entrada_id' => $fichaje->id,
            'fecha_hora' => $salida->toDateTimeString(),
            'motivo' => $data['motivo'],
        ]);

        return back()->with('status', 'Salida añadida.');
    }
}
