<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\MesDeLaPeticion;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Fichaje;
use App\Models\User;
use App\Services\InformeMensual;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Informes mensuales del registro de jornada para el admin (ver InformeMensual): resumen
 * de todos los empleados en pantalla y PDF de todos o de uno, listo para firmar y entregar
 * (art. 12.4.c y 35.5 del Estatuto de los Trabajadores) o para la Inspección.
 */
class InformeController extends Controller
{
    use MesDeLaPeticion;

    /**
     * Totales del mes de cada empleado.
     */
    public function index(Request $request): View
    {
        $mes = $this->mes($request);

        return view('admin.informes.index', [
            'mes' => $mes,
            'informes' => InformeMensual::deVarios($this->empleadosDelMes($mes), $mes),
        ]);
    }

    /**
     * PDF del mes: de un empleado (?user_id=) o de todos, una página por persona. Queda en
     * la auditoría porque saca datos personales de la aplicación.
     */
    public function pdf(Request $request): Response
    {
        $mes = $this->mes($request);
        $empleados = $this->empleadosDelMes($mes);

        if ($request->filled('user_id')) {
            $empleados = $empleados->where('id', (int) $request->query('user_id'))->values();
            abort_if($empleados->isEmpty(), 404);
        }

        $empresa = Auth::user()->empresa;

        AuditLog::registrar('informe_mensual_descargado', $empresa, [
            'mes' => $mes->format('Y-m'),
            'empleados' => $empleados->pluck('id')->all(),
        ]);

        $nombre = 'registro-jornada-'.$mes->format('Y-m').($empleados->count() === 1 ? '-'.str($empleados->first()->name)->slug() : '');

        return Pdf::loadView('informes.mensual-pdf', [
            'informes' => InformeMensual::deVarios($empleados, $mes),
            'empresa' => $empresa,
        ])->setPaper('a4')->download($nombre.'.pdf');
    }

    /**
     * Empleados de la empresa que salen en el informe: los activos y los dados de baja que
     * ficharon ese mes (sus horas también hay que conservarlas y entregarlas).
     *
     * @return Collection<int, User>
     */
    protected function empleadosDelMes(Carbon $mes): Collection
    {
        $conFichajes = Fichaje::whereBetween('fecha_hora', [$mes->copy()->startOfMonth(), $mes->copy()->endOfMonth()])
            ->distinct()
            ->pluck('user_id');

        return User::deEmpresa(Auth::user()->empresa_id)
            ->where(fn ($q) => $q->where('activo', true)->orWhereIn('id', $conFichajes))
            ->where('rol', '!=', 'super_admin')
            ->orderBy('name')
            ->get();
    }
}
