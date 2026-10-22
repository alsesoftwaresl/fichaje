<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware "modulo.nominas": subir y gestionar nóminas solo si la empresa tiene el módulo
 * (incluido, por licencia o por prueba, o contratado). Los empleados NO pasan por aquí: ver y
 * descargar las nóminas que ya tienen no depende del módulo.
 */
class ModuloNominas
{
    /**
     * Si falta el módulo, el admin va a Facturación a activarlo; el resto recibe un 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        if ($usuario->empresa->tieneModuloNominas()) {
            return $next($request);
        }

        if ($usuario->esAdminEmpresa()) {
            return redirect()->route('admin.facturacion.index')
                ->with('status', 'Para subir y gestionar nóminas activa el módulo de Nóminas. Tus empleados siguen viendo las que ya tienen.');
        }

        abort(403, 'Tu empresa no tiene activado el módulo de Nóminas.');
    }
}
