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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminFichajeController extends Controller
{
    use PaginaColecciones;

    public function index(Request $request): View
    {
        $jornadas = $this->paginar(JornadasAgrupador::agrupar($this->fichajesFiltrados($request)->get()));

        $empleados = User::deEmpresa(Auth::user()->empresa_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.fichajes.index', [
            'jornadas' => $jornadas,
            'empleados' => $empleados,
            'filtros' => $request->only(['user_id', 'desde', 'hasta']),
        ]);
    }

    public function exportar(Request $request, string $formato): Response
    {
        $jornadas = JornadasAgrupador::agrupar($this->fichajesFiltrados($request)->get());
        $nombreArchivo = 'fichajes-'.now()->format('Y-m-d-His');

        return match ($formato) {
            'excel' => Excel::download(new FichajesExport($jornadas), "{$nombreArchivo}.xlsx"),
            'pdf' => Pdf::loadView('admin.fichajes.pdf', [
                'jornadas' => $jornadas,
                'empresa' => Auth::user()->empresa,
                'filtros' => $request->only(['user_id', 'desde', 'hasta']),
            ])->setPaper('a4', 'landscape')->download("{$nombreArchivo}.pdf"),
            default => $this->exportarCsv($jornadas, $nombreArchivo),
        };
    }

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
                    $jornada['correcciones']->map(fn ($c) => $c->motivo)->implode(' | '),
                ]);
            }

            fclose($handle);
        };

        return ResponseFacade::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nombreArchivo.'.csv"',
        ]);
    }

    protected function fichajesFiltrados(Request $request)
    {
        return Fichaje::query()
            ->with(['usuario', 'correcciones'])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha_hora', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha_hora', '<=', $request->date('hasta')))
            ->orderBy('fecha_hora');
    }
}
