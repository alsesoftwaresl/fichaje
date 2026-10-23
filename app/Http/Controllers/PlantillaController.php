<?php

namespace App\Http\Controllers;

use App\Exports\PlantillaJornadaExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plantilla gratuita de registro de jornada: una página que la explica y dos descargas
 * (Excel con fórmulas y PDF para imprimir) generadas para el mes que se elija. Sin pedir
 * correo ni registro.
 */
class PlantillaController extends Controller
{
    /**
     * Página de la plantilla con la vista previa y los botones de descarga.
     */
    public function index(): View
    {
        return view('plantilla.index', ['mesActual' => now()->format('Y-m')]);
    }

    /**
     * Descarga la plantilla en Excel (.xlsx) del mes pedido (por defecto, el actual).
     */
    public function excel(Request $request): Response
    {
        $mes = $this->mes($request);

        return Excel::download(new PlantillaJornadaExport($mes), 'plantilla-registro-jornada-'.$mes->format('Y-m').'.xlsx');
    }

    /**
     * Descarga la plantilla en PDF (A4 vertical) del mes pedido, lista para imprimir.
     */
    public function pdf(Request $request): Response
    {
        $mes = $this->mes($request);

        return Pdf::loadView('plantilla.pdf', [
            'mes' => $mes,
            'nombreMes' => PlantillaJornadaExport::nombreDelMes($mes),
        ])->setPaper('a4', 'portrait')->download('plantilla-registro-jornada-'.$mes->format('Y-m').'.pdf');
    }

    /** Mes de la petición (?mes=AAAA-MM), con límites razonables; si no viene o no vale, el actual. */
    protected function mes(Request $request): Carbon
    {
        $valor = is_string($request->query('mes')) ? $request->query('mes') : '';

        if (preg_match('/^(20[2-9]\d)-(0[1-9]|1[0-2])$/', $valor, $m)) {
            return Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
        }

        return now()->startOfMonth();
    }
}
