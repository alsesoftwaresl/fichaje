<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use App\Services\RegistroFichajes;
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
    // Fallos de PIN o DNI permitidos desde un mismo sitio antes de frenar el kiosco, y
    // cuánto dura el freno.
    const FALLOS_PERMITIDOS = 10;

    const SEGUNDOS_DE_BLOQUEO = 300;

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

        // Tras varios fallos seguidos desde el mismo sitio se frena un rato, con PIN y con
        // DNI, para que no se puedan ir probando PIN o DNIs hasta acertar uno.
        $campo = $conDni ? 'dni' : 'pin';
        $claveIntentos = 'kiosko:'.$empresa->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($claveIntentos, self::FALLOS_PERMITIDOS)) {
            return back()->withErrors([$campo => 'Demasiados intentos. Espera unos minutos y vuelve a probar.']);
        }

        $empleado = $conDni
            ? $this->empleadoPorDni($request, $empresa)
            : $this->empleadoPorPin($request, $empresa);

        if (! $empleado) {
            RateLimiter::hit($claveIntentos, self::SEGUNDOS_DE_BLOQUEO);

            // Mensajes genéricos: no se dice si existe o está de baja.
            return back()->withErrors([$campo => $conDni ? 'No hemos encontrado ese DNI o NIE.' : 'PIN incorrecto.']);
        }

        // No hay usuario autenticado en el kiosco: el tenant se fija a mano
        // a partir de la empresa resuelta por el token de la URL.
        Tenant::set($empresa->id);

        $resultado = RegistroFichajes::registrar($empleado, 'kiosco', $request);

        if ($resultado['repetido']) {
            return back()->withErrors([
                $conDni ? 'dni' : 'pin' => 'Ya has fichado hace un momento. Espera un minuto para volver a fichar.',
            ]);
        }

        return redirect()->route('kiosko.show', $token)->with('resultado', [
            'nombre' => $empleado->name,
            'tipo' => $resultado['tipo'],
            'aviso' => $resultado['olvidada'] ? RegistroFichajes::avisoEntradaOlvidada($resultado['olvidada']) : null,
        ]);
    }

    /**
     * Busca al empleado activo de la empresa por su PIN de 6 dígitos. null si no hay ninguno
     * (PIN incorrecto o empleado de baja: no se distingue, para no dar pistas).
     */
    protected function empleadoPorPin(Request $request, Empresa $empresa): ?User
    {
        $data = $request->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        return User::buscarPorPin($empresa->id, $data['pin']);
    }

    /**
     * Busca al empleado activo de la empresa por su DNI o NIE (sin importar mayúsculas,
     * espacios, guiones o puntos). null si no hay ninguno.
     */
    protected function empleadoPorDni(Request $request, Empresa $empresa): ?User
    {
        $dni = is_string($request->input('dni')) ? Str::upper(preg_replace('/[\s\-\.]/', '', $request->input('dni'))) : '';

        return preg_match('/^(\d{8}|[XYZ]\d{7})[A-Z]$/', $dni)
            ? User::where('empresa_id', $empresa->id)->where('dni_nie', $dni)->where('activo', true)->first()
            : null;
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
