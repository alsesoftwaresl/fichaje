<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware "gestor.nominas": solo pasan los admin de empresa y quienes tengan el permiso
 * de contable.
 */
class PuedeGestionarNominas
{
    /**
     * Da 403 si el usuario no puede gestionar nóminas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Auth::user()?->puedeGestionarNominas(), 403);

        return $next($request);
    }
}
