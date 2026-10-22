<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tarifa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

/**
 * El admin activa o quita el módulo opcional de Nóminas desde Facturación. Se añade o se
 * quita de su suscripción de Stripe (cuota fija mensual, IVA incluido).
 */
class ModuloNominasController extends Controller
{
    /**
     * Activa el módulo: exige una suscripción activa y que el módulo se cobre aparte.
     */
    public function activar(): RedirectResponse
    {
        $empresa = Auth::user()->empresa;
        $tarifa = Tarifa::actual();

        if (! $tarifa->nominas_modulo_activo) {
            return back()->with('status', 'Nóminas está incluido, no hace falta activarlo.');
        }

        if ($empresa->modulo_nominas) {
            return back()->with('status', 'El módulo de Nóminas ya está activo.');
        }

        if (! $empresa->subscribed('default')) {
            return back()->with('status', 'Para activar Nóminas necesitas una suscripción activa. Si entras con una licencia, consulta si la tuya lo incluye.');
        }

        if (! $tarifa->stripe_price_nominas_id) {
            return back()->with('status', 'El precio del módulo todavía no está sincronizado con Stripe. Contacta con el soporte.');
        }

        try {
            $empresa->activarModuloNominas();
        } catch (ApiErrorException $e) {
            Log::warning('No se pudo activar el módulo de Nóminas: '.$e->getMessage());

            return back()->with('status', 'No se pudo activar el módulo ahora. Inténtalo de nuevo en unos minutos.');
        }

        AuditLog::registrar('modulo_nominas_activado', $empresa);

        return back()->with('status', 'Módulo de Nóminas activado.');
    }

    /**
     * Quita el módulo. Las nóminas ya subidas se conservan y los empleados siguen viéndolas.
     */
    public function desactivar(): RedirectResponse
    {
        $empresa = Auth::user()->empresa;

        if (! $empresa->modulo_nominas) {
            return back();
        }

        try {
            $empresa->desactivarModuloNominas();
        } catch (ApiErrorException $e) {
            Log::warning('No se pudo quitar el módulo de Nóminas: '.$e->getMessage());

            return back()->with('status', 'No se pudo quitar el módulo ahora. Inténtalo de nuevo en unos minutos.');
        }

        AuditLog::registrar('modulo_nominas_desactivado', $empresa);

        return back()->with('status', 'Módulo de Nóminas desactivado. Las nóminas ya subidas se conservan.');
    }
}
