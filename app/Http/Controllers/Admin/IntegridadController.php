<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\IntegridadRegistro;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Comprobación de la integridad del registro de jornada de la empresa: el admin ve si
 * algún fichaje o corrección se ha tocado fuera de la aplicación (ver IntegridadRegistro).
 */
class IntegridadController extends Controller
{
    /**
     * Ejecuta la comprobación y la deja anotada en la auditoría (sirve para demostrar
     * cuándo se revisó el registro).
     */
    public function index(): View
    {
        $empresa = Auth::user()->empresa;
        $resultado = IntegridadRegistro::verificar($empresa->id);

        AuditLog::registrar('registro_verificado', $empresa, [
            'fichajes' => $resultado['fichajes'],
            'correcciones' => $resultado['correcciones'],
            'problemas' => count($resultado['problemas']),
        ]);

        return view('admin.integridad.index', ['resultado' => $resultado, 'empresa' => $empresa]);
    }
}
