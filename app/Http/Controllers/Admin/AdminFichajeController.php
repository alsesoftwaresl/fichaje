<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FichajesExport;
use App\Http\Controllers\Concerns\PaginaColecciones;
use App\Http\Controllers\Controller;
use App\Models\Fichaje;
use App\Models\User;
use App\Services\JornadasAgrupador;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Vista del admin de empresa sobre los fichajes de toda la plantilla: listado agrupado por
 * jornadas y exportación a CSV, Excel o PDF.
 */
class AdminFichajeController extends Controller
{
    use PaginaColecciones;

    /**
     * Listado de jornadas (entrada + salida) con filtros por empleado y rango de fechas.
     */
    public function index(Request $request): View
    {
        $jornadas = $this->paginar($this->jornadasFiltradas($request));

        $empleados = User::deEmpresa(Auth::user()->empresa_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.fichajes.index', [
            'jornadas' => $jornadas,
            'empleados' => $empleados,
            'filtros' => $this->filtros($request),
        ]);
    }

    /**
     * Descarga los mismos datos filtrados del listado en el formato pedido: excel, pdf o
     * csv (por defecto).
     */
    public function exportar(Request $request, string $formato): Response
    {
        $jornadas = $this->jornadasFiltradas($request);
        $nombreArchivo = 'fichajes-'.now()->format('Y-m-d-His');

        return match ($formato) {
            'excel' => Excel::download(new FichajesExport($jornadas), "{$nombreArchivo}.xlsx"),
            'pdf' => Pdf::loadView('admin.fichajes.pdf', [
                'jornadas' => $jornadas,
                'empresa' => Auth::user()->empresa,
                'filtros' => $this->filtros($request),
            ])->setPaper('a4', 'landscape')->download("{$nombreArchivo}.pdf"),
            default => $this->exportarCsv($jornadas, $nombreArchivo),
        };
    }

    /**
     * Genera el CSV en streaming (sin cargarlo entero en memoria): una fila por jornada
     * con sus correcciones.
     */
    protected function exportarCsv(Collection $jornadas, string $nombreArchivo): StreamedResponse
    {
        $callback = function () use ($jornadas) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Empleado', 'DNI/NIE', 'Fecha', 'Entrada', 'Salida', 'Horas', 'Correcciones']);

            foreach ($jornadas as $jornada) {
                $fichajeRef = $jornada['entrada'] ?? $jornada['salida'];

                fputcsv($handle, [
                    $jornada['usuario']->name,
                    $jornada['usuario']->dni_nie,
                    $fichajeRef->fecha_hora->format('Y-m-d'),
                    $jornada['entrada']?->fecha_hora->format('H:i:s'),
                    $jornada['salida']?->fecha_hora->format('H:i:s'),
                    $jornada['horas'],
                    $jornada['notas'],
                ]);
            }

            fclose($handle);
        };

        return ResponseFacade::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nombreArchivo.'.csv"',
        ]);
    }

    /**
     * Jornadas con los filtros del formulario (empleado, desde, hasta). Una jornada cuenta
     * para el día en que empezó: se cargan los fichajes de un día antes y uno después del
     * rango para que un turno de noche salga entero (entrada y salida) y no partido en dos.
     * El aislamiento por empresa lo aplica el global scope del modelo.
     */
    protected function jornadasFiltradas(Request $request): Collection
    {
        $desde = $this->fechaDelFiltro($request, 'desde')?->startOfDay();
        $hasta = $this->fechaDelFiltro($request, 'hasta')?->endOfDay();

        $fichajes = Fichaje::query()
            ->with(['usuario', 'correcciones'])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($desde, fn ($q) => $q->where('fecha_hora', '>=', $desde->copy()->subDay()))
            ->when($hasta, fn ($q) => $q->where('fecha_hora', '<=', $hasta->copy()->addDay()))
            ->orderBy('fecha_hora')
            ->get();

        return JornadasAgrupador::agrupar($fichajes)
            ->filter(function (array $jornada) use ($desde, $hasta) {
                $inicio = ($jornada['entrada'] ?? $jornada['salida'])->fecha_hora;

                return (! $desde || $inicio->gte($desde)) && (! $hasta || $inicio->lte($hasta));
            })
            ->values();
    }

    /**
     * Fecha de un filtro (AAAA-MM-DD, como la envía el formulario). Un valor mal escrito o
     * inexistente (URL tocada a mano) se ignora en vez de romper la página.
     */
    protected function fechaDelFiltro(Request $request, string $campo): ?Carbon
    {
        $valor = is_string($request->input($campo)) ? $request->input($campo) : '';

        if ($valor === '' || Validator::make([$campo => $valor], [$campo => 'date_format:Y-m-d'])->fails()) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $valor);
    }

    /**
     * Filtros para volver a pintarlos en el formulario y en los enlaces de exportar: solo
     * los que llegan como texto.
     */
    protected function filtros(Request $request): array
    {
        return array_filter($request->only(['user_id', 'desde', 'hasta']), 'is_string');
    }
}
