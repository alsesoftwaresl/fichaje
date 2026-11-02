<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Último paso de cualquier inicio de sesión (contraseña o Google): si la persona tiene la
 * verificación en dos pasos activa, todavía no entra; se guarda en la sesión quién es
 * durante unos minutos y se le pide el código (DosPasosController). Si no la tiene, entra.
 */
class InicioSesion
{
    /** Clave de la sesión con el inicio de sesión a medias. */
    const PENDIENTE = 'dos_pasos.pendiente';

    /** Minutos para escribir el código antes de tener que volver a poner la contraseña. */
    const MINUTOS = 5;

    /**
     * Entra o pide el segundo paso.
     */
    public static function completar(Request $request, User $usuario, bool $recordar = false): RedirectResponse
    {
        // Sesión nueva en ambos casos: evita que alguien fije de antemano el id de sesión.
        $request->session()->regenerate();

        if ($usuario->tieneDosPasos()) {
            $request->session()->put(static::PENDIENTE, [
                'id' => $usuario->id,
                'recordar' => $recordar,
                'hasta' => now()->addMinutes(static::MINUTOS)->getTimestamp(),
            ]);

            return redirect()->route('dos-pasos.reto');
        }

        static::entrar($request, $usuario, $recordar);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Inicia la sesión. Antes borra la huella de contraseña que pudiera quedar en la sesión
     * de otra persona: AuthenticateSession la compara en cada petición y, si no es la de
     * quien acaba de entrar, le cerraría la sesión (se guarda la buena al terminar).
     */
    public static function entrar(Request $request, User $usuario, bool $recordar = false): void
    {
        $request->session()->forget('password_hash_'.Auth::getDefaultDriver());
        Auth::login($usuario, $recordar);
    }

    /**
     * Usuario que está a medias de entrar (contraseña bien, falta el código), si no ha
     * caducado y sigue pudiendo entrar.
     *
     * @return array{usuario: User, recordar: bool}|null
     */
    public static function pendiente(Request $request): ?array
    {
        $datos = $request->session()->get(static::PENDIENTE);

        if (! is_array($datos) || ($datos['hasta'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        $usuario = User::where('activo', true)->find($datos['id'] ?? null);

        if (! $usuario?->tieneDosPasos()) {
            return null;
        }

        return ['usuario' => $usuario, 'recordar' => (bool) ($datos['recordar'] ?? false)];
    }
}
