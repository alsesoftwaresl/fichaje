<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware "role:...": solo deja pasar a usuarios con uno de los roles indicados; a los
 * demás les da 403.
 */
class EnsureRole
{
    /**
     * Comprueba el rol del usuario con sesión.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if ($user === null || ! in_array($user->rol, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
