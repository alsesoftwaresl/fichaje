<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Continuar con Google" — solo para admin_empresa (login y alta), nunca
 * para empleados (fichan por DNI o, si tienen email, por contraseña normal).
 *
 * No hay columna "google_id": basta con el email, que Google ya nos
 * garantiza verificado — es justo lo que hace falta para emparejar con una
 * cuenta existente o para saltarse nuestra propia verificación de email al
 * crear una nueva (ver RegistroCompletarController).
 */
class GoogleAuthController extends Controller
{
    /**
     * Envía al usuario a la pantalla de acceso de Google.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Vuelta de Google: si el email es de un admin de empresa existente, inicia sesión;
     * si es de otra cuenta, lo rechaza; si es nuevo, pasa a completar el registro con los
     * datos de la empresa.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Fallo en el callback de Google OAuth: '.$e->getMessage());

            return redirect()->route('login')
                ->with('status', 'No se pudo completar el inicio de sesión con Google. Inténtalo de nuevo.');
        }

        $existente = User::where('email', $googleUser->getEmail())->first();

        if ($existente?->rol === 'admin_empresa') {
            Auth::login($existente);

            return redirect()->route('dashboard');
        }

        // El email ya es de otra cuenta (empleado, super admin...): no se
        // puede crear una empresa con él ni entrar por Google.
        if ($existente) {
            return redirect()->route('login')
                ->with('status', 'Esta cuenta no puede usar Google. Inicia sesión con tu email y contraseña.');
        }

        // No existe todavía una empresa con este email: guardamos lo que
        // Google nos ha dado (ya verificado por ellos) y le pedimos solo los
        // datos que faltan — nombre de empresa, NIF, legales — en
        // RegistroCompletarController.
        session(['registro_google' => [
            'nombre' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
        ]]);

        return redirect()->route('registro.completar');
    }
}
