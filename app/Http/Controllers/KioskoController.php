<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
     * Registra un fichaje identificando al empleado por su PIN o, si el kiosco de la
     * empresa lo permite, por su DNI/NIE. Alterna entrada/salida según el último fichaje
     * y guarda origen "kiosco", IP y navegador.
     */
    public function fichar(Request $request, string $token): RedirectResponse
    {
        $empresa = $this->empresaDelToken($token);

        if (! $this->estaSuscrita($empresa)) {
            return redirect()->route('kiosko.show', $token);
        }

        $conDni = $request->filled('dni');
        $modo = $empresa->kiosko_modo ?? 'pin';

        // Cada empresa elige con qué se ficha; lo demás se rechaza aunque alguien lo envíe a mano.
        if (($conDni && $modo === 'pin') || (! $conDni && $modo === 'dni')) {
            return back()->withErrors([$conDni ? 'dni' : 'pin' => 'Este kiosco no permite fichar así.']);
        }

        $empleado = $conDni
            ? $this->empleadoPorDni($request, $empresa)
            : $this->empleadoPorPin($request, $empresa);

        if ($empleado instanceof RedirectResponse) {
            return $empleado;
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
     * Busca al empleado por su PIN de 6 dígitos. Devuelve el empleado o una respuesta de
     * error ya preparada.
     */
    protected function empleadoPorPin(Request $request, Empresa $empresa): User|RedirectResponse
    {
        $data = $request->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        $empleado = User::buscarPorPin($empresa->id, $data['pin']);

        // Mensaje genérico a propósito: no se distingue entre "PIN
        // incorrecto" y "empleado inactivo" para no dar pistas.
        return $empleado ?? back()->withErrors(['pin' => 'PIN incorrecto.']);
    }

    /**
     * Busca al empleado activo de la empresa por su DNI o NIE (sin importar mayúsculas,
     * espacios, guiones o puntos). Tras varios fallos seguidos desde el mismo sitio se frena
     * un rato, para que no se pueda ir probando DNIs.
     */
    protected function empleadoPorDni(Request $request, Empresa $empresa): User|RedirectResponse
    {
        $clave = 'kiosko-dni:'.$empresa->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, 10)) {
            return back()->withErrors(['dni' => 'Demasiados intentos. Espera unos minutos y vuelve a probar.']);
        }

        $dni = Str::upper(preg_replace('/[\s\-\.]/', '', (string) $request->input('dni')));

        $empleado = preg_match('/^(\d{8}|[XYZ]\d{7})[A-Z]$/', $dni)
            ? User::where('empresa_id', $empresa->id)->where('dni_nie', $dni)->where('activo', true)->first()
            : null;

        if (! $empleado) {
            RateLimiter::hit($clave, 300);

            // Mensaje genérico: no se dice si el DNI existe o está de baja.
            return back()->withErrors(['dni' => 'No hemos encontrado ese DNI o NIE.']);
        }

        return $empleado;
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
