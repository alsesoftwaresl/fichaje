<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PaginaColecciones;
use App\Models\Fichaje;
use App\Services\JornadasAgrupador;
use App\Services\RegistroFichajes;
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

        $siguienteTipo = RegistroFichajes::siguienteTipo($fichajes->last());

        $fichajeWeb = Auth::user()->empresa->fichaje_web;

        return view('fichajes.mis-fichajes', compact('jornadas', 'siguienteTipo', 'fichajeWeb'));
    }

    /**
     * Registra un fichaje web con las reglas comunes de RegistroFichajes (entrada o salida,
     * entradas olvidadas, doble clic). Los fichajes no se pueden editar ni borrar después.
     */
    public function store(): RedirectResponse
    {
        // La empresa puede exigir que se fiche solo en el kiosco (p. ej. para que nadie ficha desde casa).
        if (! Auth::user()->empresa->fichaje_web) {
            return redirect()->route('fichajes.mis')
                ->withErrors(['fichaje' => 'Tu empresa solo permite fichar en el kiosco.']);
        }

        $resultado = RegistroFichajes::registrar(Auth::user(), 'web', request());

        if ($resultado['repetido']) {
            return redirect()->route('fichajes.mis')
                ->withErrors(['fichaje' => 'Acabas de fichar. Espera un minuto si de verdad quieres volver a hacerlo.']);
        }

        $mensaje = $resultado['tipo'] === 'entrada' ? 'Entrada registrada.' : 'Salida registrada.';

        if ($resultado['olvidada']) {
            $mensaje .= ' '.RegistroFichajes::avisoEntradaOlvidada($resultado['olvidada']);
        }

        return redirect()->route('fichajes.mis')->with('status', $mensaje);
    }
}
