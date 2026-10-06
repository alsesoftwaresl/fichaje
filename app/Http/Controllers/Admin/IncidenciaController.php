<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\IncidenciasCalculador;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IncidenciaController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $empresaId = Auth::user()->empresa_id;

        $desde = isset($filtros['desde']) ? Carbon::parse($filtros['desde']) : now()->startOfMonth();
        $hasta = isset($filtros['hasta']) ? Carbon::parse($filtros['hasta']) : now();

        $incidencias = IncidenciasCalculador::delPeriodo($empresaId, $desde, $hasta);

        if (! empty($filtros['user_id'])) {
            $incidencias = $incidencias->filter(fn (array $i) => $i['empleado']->id === (int) $filtros['user_id']);
        }

        $resumen = $incidencias
            ->groupBy(fn (array $i) => $i['empleado']->id)
            ->map(fn ($deUnEmpleado) => [
                'empleado' => $deUnEmpleado->first()['empleado'],
                'retrasos' => $deUnEmpleado->where('tipo', 'retraso')->count(),
                'minutos_retraso' => (int) $deUnEmpleado->where('tipo', 'retraso')->sum('minutos'),
                'sin_fichar' => $deUnEmpleado->where('tipo', 'sin_fichar')->count(),
                'sin_cerrar' => $deUnEmpleado->where('tipo', 'sin_cerrar')->count(),
                'horas_extra' => round($deUnEmpleado->where('tipo', 'horas_de_mas')->sum('horas_extra'), 2),
            ])
            ->sortBy(fn (array $fila) => $fila['empleado']->name)
            ->values();

        return view('admin.incidencias.index', [
            'resumen' => $resumen,
            'incidencias' => $incidencias->sortByDesc(fn (array $i) => $i['fecha'])->values(),
            'empleados' => User::deEmpresa($empresaId)->where('activo', true)->orderBy('name')->get(),
            'filtros' => [
                'user_id' => $filtros['user_id'] ?? null,
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString(),
            ],
            'maxDias' => IncidenciasCalculador::MAX_DIAS_PERIODO,
        ]);
    }
}
