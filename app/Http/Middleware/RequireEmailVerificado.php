<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo bloquea a admin_empresa sin el email verificado — empleados (muchos
 * sin email, fichan por DNI) y super_admin nunca pasan por aquí, da igual
 * cuál sea su $user->email_verified_at.
 */
class RequireEmailVerificado
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user->esAdminEmpresa() || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        return redirect()->route('verification.notice');
    }
}
