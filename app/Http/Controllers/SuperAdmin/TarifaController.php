<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tarifa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class TarifaController extends Controller
{
    public function edit(): View
    {
        return view('super-admin.tarifas.edit', [
            'tarifa' => Tarifa::actual(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'precio_base_mensual' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'empleados_incluidos' => ['required', 'integer', 'min:0'],
            'precio_empleado_extra' => ['required', 'numeric', 'min:0', 'max:9999.99'],
        ]);

        $tarifa = Tarifa::actual();
        $anterior = $tarifa->only(array_keys($data));

        $tarifa->update($data + ['actualizado_por' => Auth::id()]);

        AuditLog::registrar('tarifas_actualizadas', $tarifa, [
            'anterior' => $anterior,
            'nuevo' => $data,
        ]);

        try {
            $tarifa->sincronizarConStripe();
        } catch (ApiErrorException $e) {
            return redirect()->route('super-admin.tarifas.edit')
                ->with('status', 'Tarifas guardadas, pero no se pudieron sincronizar con Stripe: '.$e->getMessage());
        }

        return redirect()->route('super-admin.tarifas.edit')->with('status', 'Tarifas actualizadas y sincronizadas con Stripe.');
    }
}
