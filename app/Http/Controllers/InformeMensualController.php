<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MesDeLaPeticion;
use App\Services\InformeMensual;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * La persona trabajadora descarga el informe mensual de su propio registro de jornada
 * (art. 34.9 ET: el registro tiene que estar a su disposición). No depende de que la
 * empresa pague: es su registro, igual que sus nóminas.
 */
class InformeMensualController extends Controller
{
    use MesDeLaPeticion;

    /**
     * PDF del mes indicado (?mes=AAAA-MM) solo con sus propios fichajes.
     */
    public function propio(Request $request): Response
    {
        $mes = $this->mes($request);
        $usuario = Auth::user();

        return Pdf::loadView('informes.mensual-pdf', [
            'informes' => collect([InformeMensual::deEmpleado($usuario, $mes)]),
            'empresa' => $usuario->empresa,
        ])->setPaper('a4')->download('mi-registro-de-jornada-'.$mes->format('Y-m').'.pdf');
    }
}
