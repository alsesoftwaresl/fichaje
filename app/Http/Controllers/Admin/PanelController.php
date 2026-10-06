<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ausencia;
use App\Models\Cita;
use App\Services\EstadoEquipoCalculador;
use App\Services\IncidenciasCalculador;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Panel de inicio del admin de empresa: quién está trabajando, de vacaciones o fuera,
 * incidencias de hoy, solicitudes pendientes y avisos de cita.
 */
class PanelController extends Controller
{
    /**
     * Reúne los datos del día para la vista del panel.
     */
    public function index(): View
    {
        $empresaId = Auth::user()->empresa_id;

        return view('admin.panel.index', [
            'estadoEquipo' => EstadoEquipoCalculador::delDia($empresaId),
            'incidencias' => IncidenciasCalculador::delDia($empresaId),
            'ausenciasPendientes' => Ausencia::where('estado', 'pendiente')->count(),
            // Avisos de cita (médico...) de hoy, para que el admin sepa quién
            // está fuera con permiso y a qué hora vuelve.
            'citasHoy' => Cita::vigentes()->with('usuario')->whereDate('fecha', today())->orderBy('hora_inicio')->get(),
        ]);
    }
}
