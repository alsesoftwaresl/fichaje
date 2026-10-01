<?php

namespace App\Http\Middleware;

use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        if ($user->empresa_id === null) {
            // super_admin no tiene empresa: no puede entrar por rutas de tenant.
            abort(403);
        }

        if ($user->empresa?->activa !== true) {
            abort(403);
        }

        Tenant::set($user->empresa_id);

        return $next($request);
    }
}
