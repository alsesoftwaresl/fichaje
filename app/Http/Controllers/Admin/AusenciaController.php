<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Ausencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Lado del admin de empresa de las ausencias: ver todas las solicitudes del equipo y
 * aprobarlas o rechazarlas. Cada decisión queda en la auditoría.
 */
class AusenciaController extends Controller
{
    /**
     * Listado de ausencias (20 por página) con filtro por estado; las pendientes salen
     * primero.
     */
    public function index(Request $request): View
    {
        $ausencias = Ausencia::with('usuario')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->orderByRaw("estado = 'pendiente' desc")
            ->orderByDesc('fecha_inicio')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ausencias.index', [
            'ausencias' => $ausencias,
            'estado' => $request->string('estado')->toString(),
        ]);
    }

    /**
     * Aprueba una solicitud: guarda quién y cuándo la resolvió. Desde ese momento el
     * empleado cuenta como de vacaciones/baja en el estado del equipo.
     */
    public function aprobar(Ausencia $ausencia): RedirectResponse
    {
        $ausencia->update([
            'estado' => 'aprobada',
            'resuelto_por' => Auth::id(),
            'resuelto_en' => now(),
            'motivo_rechazo' => null,
        ]);

        AuditLog::registrar('ausencia_aprobada', $ausencia);

        return back()->with('status', 'Ausencia aprobada.');
    }

    /**
     * Rechaza una solicitud con un motivo opcional que verá el empleado.
     */
    public function rechazar(Request $request, Ausencia $ausencia): RedirectResponse
    {
        $data = $request->validate([
            'motivo_rechazo' => ['nullable', 'string', 'max:2000'],
        ]);

        $ausencia->update([
            'estado' => 'rechazada',
            'resuelto_por' => Auth::id(),
            'resuelto_en' => now(),
            'motivo_rechazo' => $data['motivo_rechazo'] ?? null,
        ]);

        AuditLog::registrar('ausencia_rechazada', $ausencia);

        return back()->with('status', 'Ausencia rechazada.');
    }
}
