<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\InicioSesion;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Segundo paso del inicio de sesión: código de 6 cifras de la app de autenticación o uno
 * de los códigos de recuperación (cada uno sirve una sola vez).
 */
class DosPasosController extends Controller
{
    /** Intentos fallidos permitidos por minuto antes de frenar. */
    const INTENTOS = 5;

    /**
     * Pantalla para escribir el código. Sin un inicio de sesión a medias, vuelve al login.
     */
    public function reto(Request $request): View|RedirectResponse
    {
        if (! InicioSesion::pendiente($request)) {
            return redirect()->route('login');
        }

        return view('auth.dos-pasos');
    }

    /**
     * Comprueba el código y, si vale, completa la entrada.
     */
    public function verificar(Request $request): RedirectResponse
    {
        $pendiente = InicioSesion::pendiente($request);

        if (! $pendiente) {
            return redirect()->route('login')->with('status', 'Ha pasado demasiado tiempo. Vuelve a iniciar sesión.');
        }

        $request->validate(['codigo' => ['required', 'string', 'max:20']]);

        $usuario = $pendiente['usuario'];
        $clave = 'dos-pasos:'.$usuario->id;

        if (RateLimiter::tooManyAttempts($clave, static::INTENTOS)) {
            throw ValidationException::withMessages(['codigo' => 'Demasiados intentos. Espera un minuto y vuelve a probar.']);
        }

        $metodo = $this->comprobar($usuario, (string) $request->input('codigo'));

        if (! $metodo) {
            RateLimiter::hit($clave, 60);

            throw ValidationException::withMessages(['codigo' => 'El código no es correcto o ya se ha usado.']);
        }

        RateLimiter::clear($clave);
        $request->session()->forget(InicioSesion::PENDIENTE);
        InicioSesion::entrar($request, $usuario, $pendiente['recordar']);
        $request->session()->regenerate();

        if ($metodo === 'recuperacion') {
            AuditLog::registrar('codigo_recuperacion_usado', $usuario, [
                'quedan' => count($usuario->dos_pasos_recuperacion ?? []),
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * "app" si es el código de la app (y no se había usado ya en este intervalo),
     * "recuperacion" si es un código de recuperación (que se gasta), o null.
     */
    protected function comprobar(User $usuario, string $codigo): ?string
    {
        $paso = Totp::verificar($usuario->dos_pasos_secreto, $codigo);

        // El mismo código no vale dos veces (alguien que lo vea por encima del hombro).
        if ($paso !== null) {
            return Cache::add('dos-pasos-usado:'.$usuario->id.':'.$paso, true, 120) ? 'app' : null;
        }

        $normalizado = strtoupper(preg_replace('/[\s-]/', '', $codigo));
        $codigos = $usuario->dos_pasos_recuperacion ?? [];

        foreach ($codigos as $i => $guardado) {
            if (hash_equals(str_replace('-', '', $guardado), $normalizado)) {
                unset($codigos[$i]);
                $usuario->forceFill(['dos_pasos_recuperacion' => array_values($codigos)])->save();

                return 'recuperacion';
            }
        }

        return null;
    }
}
