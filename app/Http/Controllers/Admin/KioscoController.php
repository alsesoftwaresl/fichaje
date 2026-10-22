<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ajuste del kiosco por el admin de empresa: con qué se identifican los empleados al
 * fichar (PIN, DNI o ambos).
 */
class KioscoController extends Controller
{
    /**
     * Cambia el modo de identificación del kiosco de la empresa del admin.
     */
    public function modo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kiosko_modo' => ['required', 'in:'.implode(',', Empresa::MODOS_KIOSKO)],
        ]);

        $empresa = Auth::user()->empresa;
        $anterior = $empresa->kiosko_modo;

        $cambios = ['kiosko_modo' => $data['kiosko_modo'], 'kiosko_modo_elegido' => true];

        // Solo se toca si el formulario lo envía (así no se pisa en peticiones antiguas).
        if ($request->has('fichaje_web')) {
            $cambios['fichaje_web'] = $request->boolean('fichaje_web');
        }

        $webAnterior = $empresa->fichaje_web;
        $empresa->forceFill($cambios)->save();

        AuditLog::registrar('kiosko_modo_cambiado', $empresa, [
            'anterior' => $anterior,
            'nuevo' => $data['kiosko_modo'],
            'fichaje_web_anterior' => $webAnterior,
            'fichaje_web_nuevo' => $empresa->fichaje_web,
        ]);

        return back()->with('status', 'Kiosco actualizado.');
    }
}
