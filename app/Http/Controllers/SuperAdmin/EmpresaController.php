<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Empresa;
use App\Models\Tarifa;
use App\Services\AltaEmpresaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Gestión de empresas clientes por el super admin: listar, dar de alta a mano, ver
 * detalle y activar/desactivar.
 */
class EmpresaController extends Controller
{
    /**
     * Lista de empresas con su número de usuarios y usuarios activos.
     */
    public function index(): View
    {
        $empresas = Empresa::withCount('usuarios')
            ->withCount(['usuarios as usuarios_activos_count' => fn ($q) => $q->where('activo', true)])
            ->orderBy('nombre')
            ->paginate(30);

        $tarifa = Tarifa::actual();

        return view('super-admin.empresas.index', compact('empresas', 'tarifa'));
    }

    /**
     * Formulario de alta de empresa con su primer admin.
     */
    public function create(): View
    {
        return view('super-admin.empresas.create', [
            'versionTerminos' => config('legal.version_terminos'),
            'versionEncargo' => config('legal.version_encargo'),
        ]);
    }

    /**
     * Da de alta empresa + admin (con la aceptación legal registrada) mediante
     * AltaEmpresaService.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:20', 'unique:empresas,nif'],
            'email_contacto' => ['required', 'email', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'confirmed', Password::defaults()],
            'acepta_terminos' => ['accepted'],
            'acepta_encargo' => ['accepted'],
        ]);

        ['empresa' => $empresa] = AltaEmpresaService::crear($data, $request->ip());

        return redirect()->route('super-admin.empresas.show', $empresa)->with('status', 'Empresa dada de alta.');
    }

    /**
     * Detalle de una empresa: datos, precio estimado, licencia y usuarios.
     */
    public function show(Empresa $empresa): View
    {
        $empresa->load('usuarios');

        $tarifa = Tarifa::actual();
        $empleadosActivos = $empresa->usuarios->where('activo', true)->count();

        return view('super-admin.empresas.show', compact('empresa', 'tarifa', 'empleadosActivos'));
    }

    /**
     * Reactiva una empresa desactivada (sus usuarios vuelven a poder entrar).
     */
    public function activar(Empresa $empresa): RedirectResponse
    {
        $empresa->update(['activa' => true]);
        AuditLog::registrar('empresa_activada', $empresa);

        return back()->with('status', 'Empresa activada.');
    }

    /**
     * Desactiva una empresa sin borrar nada: sus usuarios dejan de poder entrar.
     */
    public function desactivar(Empresa $empresa): RedirectResponse
    {
        $empresa->update(['activa' => false]);
        AuditLog::registrar('empresa_desactivada', $empresa);

        return back()->with('status', 'Empresa desactivada.');
    }
}
