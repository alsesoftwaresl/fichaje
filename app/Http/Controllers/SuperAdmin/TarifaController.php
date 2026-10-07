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

/**
 * El super admin edita la tarifa global (cuota base, empleados incluidos, precio por
 * extra) y la sincroniza con Stripe.
 */
class TarifaController extends Controller
{
    /**
     * Formulario con la tarifa actual.
     */
    public function edit(): View
    {
        return view('super-admin.tarifas.edit', [
            'tarifa' => Tarifa::actual(),
        ]);
    }

    /**
     * Guarda la tarifa, anota el cambio en auditoría y crea los precios nuevos en Stripe.
     * Si Stripe falla, la tarifa queda guardada y se avisa.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'precio_base_mensual' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'empleados_incluidos' => ['required', 'integer', 'min:0'],
            'precio_empleado_extra' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'iva_porcentaje' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            // Plan para asesorías: opcionales para no romper formularios antiguos.
            'partner_precio_licencia' => ['sometimes', 'numeric', 'min:0', 'max:9999.99'],
            'partner_empleados_incluidos' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'partner_precio_empleado_extra' => ['sometimes', 'numeric', 'min:0', 'max:9999.99'],
            'partner_licencias_minimas' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'partner_pvp_recomendado' => ['sometimes', 'numeric', 'min:0', 'max:9999.99'],
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
