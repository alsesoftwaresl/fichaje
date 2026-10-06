<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quien ha entrado con una contraseña temporal (se la puso o generó un admin)
 * solo puede elegir la suya o cerrar sesión; cualquier otra página lo manda
 * a elegirla.
 */
class ForzarCambioPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user?->debe_cambiar_password && ! $request->routeIs('password.forzar', 'password.forzar.store', 'logout')) {
            return redirect()->route('password.forzar');
        }

        return $next($request);
    }
}
