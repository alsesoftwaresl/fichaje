<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Licencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LicenciaController extends Controller
{
    public function canjear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:32'],
        ]);

        $empresa = Auth::user()->empresa;

        // Con una suscripción de pago en marcha, la licencia no se aplicaría
        // y el código se gastaría para nada.
        if ($empresa->subscribed('default')) {
            return back()->withErrors(['codigo' => 'Ya tienes una suscripción activa. Cancélala antes de canjear una licencia.']);
        }

        if ($empresa->tieneLicenciaActiva()) {
            return back()->withErrors(['codigo' => 'Tu empresa ya tiene una licencia gratuita activa.']);
        }

        if ($error = Licencia::canjear($data['codigo'], $empresa)) {
            return back()->withErrors(['codigo' => $error])->withInput();
        }

        AuditLog::registrar('licencia_canjeada', $empresa->licencia()->first(), [
            'hasta' => $empresa->licencia_hasta?->toDateString(),
        ]);

        return redirect()->route('admin.facturacion.index')
            ->with('status', 'Licencia activada. Ya puedes usar la app.');
    }
}
