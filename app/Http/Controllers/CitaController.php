<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Cita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Aviso de una salida dentro de la jornada (médico, una gestión...): el
 * empleado indica el día y de qué hora a qué hora no está. Surte efecto al
 * momento; el propio empleado o un admin pueden anularlo.
 */
class CitaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            // El empleado explica con sus palabras por qué sale (médico, una
            // gestión...): es lo que ve el admin.
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        $cita = Cita::create($data + ['user_id' => Auth::id()]);

        AuditLog::registrar('cita_avisada', $cita, [
            'fecha' => $cita->fecha->toDateString(),
            'rango' => $cita->rango(),
        ]);

        return redirect()->route('ausencias.mis')
            ->with('status', 'Aviso registrado. Tu empresa lo verá en el panel.');
    }

    public function anular(Cita $cita): RedirectResponse
    {
        $usuario = Auth::user();

        abort_unless($usuario->esAdminEmpresa() || $cita->user_id === $usuario->id, 403);

        if (! $cita->anulada_en) {
            $cita->update(['anulada_en' => now(), 'anulada_por' => $usuario->id]);
            AuditLog::registrar('cita_anulada', $cita);
        }

        return back()->with('status', 'Aviso anulado.');
    }
}
