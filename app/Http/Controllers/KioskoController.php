<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Kiosco de fichaje: pantalla pública (sin login) que cada empresa abre en una tablet o
 * PC compartido. Se accede por una URL con token secreto y el empleado ficha tecleando su
 * PIN de 6 dígitos.
 */
class KioskoController extends Controller
{
    /**
     * Muestra el teclado del kiosco, o la pantalla de "bloqueado" si la empresa no tiene
     * suscripción ni licencia.
     */
    public function show(string $token): View
    {
        $empresa = $this->empresaDelToken($token);

        if (! $this->estaSuscrita($empresa)) {
            return view('kiosko.bloqueado', compact('empresa'));
        }

        return view('kiosko.show', compact('empresa'));
    }

    /**
     * Registra un fichaje a partir del PIN. Alterna entrada/salida según el último
     * fichaje del empleado y guarda origen "kiosco", IP y navegador.
     */
    public function fichar(Request $request, string $token): RedirectResponse
    {
        $empresa = $this->empresaDelToken($token);

        if (! $this->estaSuscrita($empresa)) {
            return redirect()->route('kiosko.show', $token);
        }

        $data = $request->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        $empleado = User::buscarPorPin($empresa->id, $data['pin']);

        if (! $empleado) {
            // Mensaje genérico a propósito: no se distingue entre "PIN
            // incorrecto" y "empleado inactivo" para no dar pistas.
            return back()->withErrors(['pin' => 'PIN incorrecto.']);
        }

        // No hay usuario autenticado en el kiosco: el tenant se fija a mano
        // a partir de la empresa resuelta por el token de la URL.
        Tenant::set($empresa->id);

        $ultimoFichaje = Fichaje::where('user_id', $empleado->id)
            ->orderByDesc('fecha_hora')
            ->first();

        $tipo = $ultimoFichaje?->tipo === 'entrada' ? 'salida' : 'entrada';

        Fichaje::create([
            'user_id' => $empleado->id,
            'tipo' => $tipo,
            'fecha_hora' => now(),
            'origen' => 'kiosco',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return redirect()->route('kiosko.show', $token)->with('resultado', [
            'nombre' => $empleado->name,
            'tipo' => $tipo,
        ]);
    }

    /**
     * Si la copia local dice que no, se pregunta a Stripe (como mucho una vez
     * cada 30 s por empresa — esta página es pública y no debe poder martillear
     * la API): el webhook puede no haber llegado todavía.
     */
    protected function estaSuscrita(Empresa $empresa): bool
    {
        if ($empresa->tieneAcceso()) {
            return true;
        }

        if ($empresa->stripe_id && Cache::add('kiosko-sync-'.$empresa->id, 1, 30)) {
            $empresa->sincronizarSuscripcionesDesdeStripe();
            $empresa->unsetRelation('subscriptions');
        }

        return $empresa->tieneAcceso();
    }

    /**
     * Busca la empresa por el token de la URL; da 404 si no existe o está desactivada.
     */
    protected function empresaDelToken(string $token): Empresa
    {
        $empresa = Empresa::where('kiosko_token', $token)->first();

        abort_if(! $empresa || ! $empresa->activa, 404);

        return $empresa;
    }
}
