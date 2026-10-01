<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ausencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AusenciaController extends Controller
{
    public function misAusencias(): View
    {
        $ausencias = Ausencia::where('user_id', Auth::id())
            ->orderByDesc('fecha_inicio')
            ->paginate(20);

        return view('ausencias.mis-ausencias', compact('ausencias'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:vacaciones,baja_medica,otro'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'motivo' => ['nullable', 'string', 'max:2000'],
        ]);

        $ausencia = Ausencia::create($data + [
            'user_id' => Auth::id(),
            'estado' => 'pendiente',
        ]);

        AuditLog::registrar('ausencia_solicitada', $ausencia);

        return redirect()->route('ausencias.mis')->with('status', 'Solicitud enviada. Tu admin la revisará.');
    }
}
