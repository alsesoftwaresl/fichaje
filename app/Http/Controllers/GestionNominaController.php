<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Nomina;
use App\Models\User;
use App\Notifications\NominaDisponible;
use App\Support\Avisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Lado de la empresa: subir, listar y borrar nóminas. Lo usan los admin y
 * quien tenga el permiso de contable (ver PuedeGestionarNominas).
 */
class GestionNominaController extends Controller
{
    /**
     * Lista las nóminas de la empresa (30 por página), con filtro opcional por empleado
     * y por mes.
     */
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);

        $nominas = Nomina::with('empleado')
            ->when(! empty($filtros['user_id']), fn ($q) => $q->where('user_id', $filtros['user_id']))
            ->when(! empty($filtros['mes']), fn ($q) => $q->where('periodo', $filtros['mes'].'-01'))
            ->orderByDesc('periodo')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('nominas.gestion', [
            'nominas' => $nominas,
            'empleados' => $this->empleadosDeLaEmpresa(),
            'filtros' => $filtros,
        ]);
    }

    /**
     * Sube una nómina en PDF (máx. 5 MB) para un empleado de la empresa. El archivo se
     * guarda en un disco privado con nombre aleatorio y se deja constancia en el registro
     * de auditoría.
     */
    public function store(Request $request): RedirectResponse
    {
        $empresaId = Auth::user()->empresa_id;

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')
                ->where('empresa_id', $empresaId)->where('rol', '!=', 'super_admin')],
            'mes' => ['required', 'date_format:Y-m'],
            'descripcion' => ['nullable', 'string', 'max:120'],
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'archivo.mimes' => 'La nómina tiene que ser un archivo PDF.',
            'archivo.max' => 'El PDF no puede pesar más de 5 MB.',
        ]);

        $archivo = $request->file('archivo');

        // Nombre aleatorio en disco: el nombre original nunca forma parte de
        // la ruta (podría llevar el DNI o el nombre del empleado).
        $ruta = $archivo->storeAs(
            'nominas/'.$empresaId.'/'.$data['user_id'],
            str()->uuid().'.pdf',
            Nomina::DISCO
        );

        $nomina = Nomina::create([
            'user_id' => $data['user_id'],
            'periodo' => Carbon::createFromFormat('Y-m', $data['mes'])->startOfMonth(),
            'descripcion' => $data['descripcion'] ?? null,
            'ruta' => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'tamano' => $archivo->getSize(),
            'subida_por' => Auth::id(),
        ]);

        AuditLog::registrar('nomina_subida', $nomina, [
            'empleado_id' => $nomina->user_id,
            'periodo' => $nomina->periodo->format('Y-m'),
        ]);

        Avisos::enviar([$nomina->empleado], new NominaDisponible($nomina));

        return redirect()->route('nominas.gestion.index')->with('status', 'Nómina subida.');
    }

    /**
     * Borra una nómina: el PDF del disco y su registro. Queda anotado en la auditoría.
     */
    public function destroy(Nomina $nomina): RedirectResponse
    {
        $nomina->borrarArchivo();
        $nomina->delete();

        AuditLog::registrar('nomina_eliminada', $nomina, [
            'empleado_id' => $nomina->user_id,
            'periodo' => $nomina->periodo->format('Y-m'),
        ]);

        return back()->with('status', 'Nómina eliminada.');
    }

    /**
     * Empleados activos de la empresa (sin el super admin), para el desplegable de subida
     * y de filtro.
     */
    protected function empleadosDeLaEmpresa()
    {
        return User::deEmpresa(Auth::user()->empresa_id)
            ->where('rol', '!=', 'super_admin')
            ->where('activo', true)
            ->orderBy('name')
            ->get();
    }
}
