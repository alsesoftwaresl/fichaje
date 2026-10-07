<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Empresa;
use App\Models\Licencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * El super admin crea y gestiona los códigos de licencia gratuita (qué duración,
 * cuántos usos, hasta cuándo se pueden canjear).
 */
class LicenciaController extends Controller
{
    /**
     * Lista todos los códigos con sus usos y las empresas que los canjearon.
     */
    public function index(): View
    {
        return view('super-admin.licencias.index', [
            'licencias' => Licencia::with('empresas')->latest()->get(),
            'empleadosPorDefecto' => \App\Models\Tarifa::actual()->partner_empleados_incluidos,
        ]);
    }

    /**
     * Genera un código nuevo con el formato ACH-XXXX-XXXX.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nota' => ['nullable', 'string', 'max:255'],
            'meses' => ['nullable', 'integer', 'min:1', 'max:120'],
            'max_usos' => ['required', 'integer', 'min:1', 'max:1000'],
            'max_empleados' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'canjeable_hasta' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $licencia = Licencia::create($data + ['codigo' => Licencia::generarCodigo()]);

        AuditLog::registrar('licencia_creada', $licencia, ['codigo' => $licencia->codigo]);

        return redirect()->route('super-admin.licencias.index')
            ->with('status', 'Código creado: '.$licencia->codigo);
    }

    /**
     * Activa o desactiva un código (desactivado no se puede canjear; las licencias ya
     * canjeadas siguen).
     */
    public function toggle(Licencia $licencia): RedirectResponse
    {
        $licencia->update(['activa' => ! $licencia->activa]);

        AuditLog::registrar($licencia->activa ? 'licencia_activada' : 'licencia_desactivada', $licencia);

        return back()->with('status', $licencia->activa ? 'Código activado.' : 'Código desactivado: ya no se puede canjear.');
    }

    /** Quita la licencia a una empresa (queda pendiente de suscribirse). */
    public function quitar(Empresa $empresa): RedirectResponse
    {
        $licencia = $empresa->licencia;

        $empresa->forceFill(['licencia_id' => null, 'licencia_hasta' => null])->save();

        if ($licencia) {
            AuditLog::registrar('licencia_retirada', $empresa, ['codigo' => $licencia->codigo]);
        }

        return back()->with('status', 'Licencia retirada.');
    }
}
