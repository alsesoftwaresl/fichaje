<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginaColecciones;
use App\Models\Fichaje;
use App\Services\JornadasAgrupador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Fichaje del propio empleado desde la web (el botón Entrada/Salida de "Mis fichajes").
 */
class FichajeController extends Controller
{
    use PaginaColecciones;

    /**
     * Historial propio agrupado por jornadas, y qué botón toca (entrada o salida) según
     * el último fichaje.
     */
    public function misFichajes(): View
    {
        $fichajes = Fichaje::with(['correcciones', 'usuario'])
            ->where('user_id', Auth::id())
            ->orderBy('fecha_hora')
            ->get();

        $jornadas = $this->paginar(JornadasAgrupador::agrupar($fichajes));

        $siguienteTipo = $fichajes->last()?->tipo === 'entrada' ? 'salida' : 'entrada';

        return view('fichajes.mis-fichajes', compact('jornadas', 'siguienteTipo'));
    }

    /**
     * Registra un fichaje web: alterna entrada/salida y guarda la hora del servidor, IP y
     * navegador. Los fichajes no se pueden editar ni borrar después.
     */
    public function store(): RedirectResponse
    {
        $ultimoFichaje = Fichaje::where('user_id', Auth::id())
            ->orderByDesc('fecha_hora')
            ->first();

        $tipo = $ultimoFichaje?->tipo === 'entrada' ? 'salida' : 'entrada';

        Fichaje::create([
            'user_id' => Auth::id(),
            'tipo' => $tipo,
            // fecha_hora se genera siempre en el servidor, nunca se acepta del cliente.
            'fecha_hora' => now(),
            'origen' => 'web',
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ]);

        return redirect()->route('fichajes.mis')
            ->with('status', $tipo === 'entrada' ? 'Entrada registrada.' : 'Salida registrada.');
    }
}
