<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de quien ha sido dado de baja (o cuya empresa se ha desactivado).
 *
 * El login ya no deja entrar a un usuario inactivo, pero una sesión abierta antes de la baja
 * (o la cookie de «Recuérdame», que dura semanas) seguía valiendo: la persona despedida podía
 * seguir viendo sus nóminas o fichando desde el móvil. Como el usuario se lee de la base de
 * datos en cada petición, la baja surte efecto en la siguiente página que abra.
 */
class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        $deBaja = $usuario && (! $usuario->activo || ($usuario->empresa_id && $usuario->empresa?->activa !== true));

        if ($deBaja) {
            // logout() también cambia el remember_token: la cookie de «Recuérdame» deja de valer.
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Tu acceso está desactivado. Si crees que es un error, habla con tu empresa.');
        }

        return $next($request);
    }
}
