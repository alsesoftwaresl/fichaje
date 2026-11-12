<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige la verificación en dos pasos para entrar al panel de super admin en producción.
 *
 * Esa cuenta ve todas las empresas y reparte licencias: si alguien adivinara o robara su
 * contraseña, el daño sería para todos los clientes. Para el resto de usuarios la verificación
 * sigue siendo opcional (lo decide cada empresa). Sin ella, se le manda a su perfil a activarla.
 */
class DosPasosObligatorio
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction() && ! Auth::user()?->tieneDosPasos()) {
            return redirect()->route('profile.edit')
                ->with('status', 'Para entrar al panel de super admin activa antes la verificación en dos pasos (más abajo).');
        }

        return $next($request);
    }
}
