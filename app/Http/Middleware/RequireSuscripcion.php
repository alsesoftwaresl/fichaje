<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireSuscripcion
{
    /**
     * Bloquea el uso de la app (fichajes, empleados, ausencias, panel) si la
     * empresa no tiene una suscripción activa en Stripe. La pantalla de
     * Facturación queda siempre accesible aparte (no lleva este middleware),
     * para que el admin pueda suscribirse y desbloquear el resto.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user->empresa->subscribed('default')) {
            return $next($request);
        }

        if ($user->esAdminEmpresa()) {
            return redirect()->route('admin.facturacion.index')
                ->with('status', 'Tu empresa no tiene una suscripción activa. Suscríbete para poder seguir usando la app.');
        }

        return response()->view('suscripcion.bloqueado', [], 403);
    }
}
