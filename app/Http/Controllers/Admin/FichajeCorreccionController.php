<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Fichaje;
use App\Models\FichajeCorreccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FichajeCorreccionController extends Controller
{
    public function store(Request $request, Fichaje $fichaje): RedirectResponse
    {
        // $fichaje ya viene filtrado por el scope de tenant en el route-model-binding:
        // si perteneciera a otra empresa, Laravel devolvería 404 antes de llegar aquí.

        $data = $request->validate([
            'fecha_hora_corregida' => ['required', 'date'],
            'motivo' => ['required', 'string', 'max:2000'],
        ]);

        $correccion = FichajeCorreccion::create([
            'fichaje_original_id' => $fichaje->id,
            'fecha_hora_corregida' => $data['fecha_hora_corregida'],
            'motivo' => $data['motivo'],
            'corregido_por' => Auth::id(),
        ]);

        AuditLog::registrar('fichaje_corregido', $fichaje, [
            'correccion_id' => $correccion->id,
            'fecha_hora_original' => $fichaje->fecha_hora->toDateTimeString(),
            'fecha_hora_corregida' => $correccion->fecha_hora_corregida->toDateTimeString(),
        ]);

        return back()->with('status', 'Corrección registrada.');
    }
}
