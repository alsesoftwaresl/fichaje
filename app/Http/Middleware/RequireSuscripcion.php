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
        $empresa = $user->empresa;

        if ($empresa->tieneAcceso()) {
            return $next($request);
        }

        // Puede que Stripe ya la dé por suscrita y solo falte copiarlo aquí
        // (el usuario vuelve del pago antes que el webhook, o el webhook no
        // llegó): se consulta a Stripe antes de bloquear.
        if ($empresa->stripe_id) {
            $empresa->sincronizarSuscripcionesDesdeStripe();
            $empresa->unsetRelation('subscriptions');

            if ($empresa->tieneAcceso()) {
                return $next($request);
            }
        }

        if ($user->esAdminEmpresa()) {
            return redirect()->route('admin.facturacion.index')
                ->with('status', 'Tu empresa no tiene una suscripción activa. Suscríbete para poder seguir usando la app.');
        }

        return response()->view('suscripcion.bloqueado', [], 403);
    }
}
