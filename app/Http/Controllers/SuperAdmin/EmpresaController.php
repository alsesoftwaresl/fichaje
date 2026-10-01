<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Empresa;
use App\Models\LegalAceptacion;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function index(): View
    {
        $empresas = Empresa::withCount('usuarios')
            ->withCount(['usuarios as usuarios_activos_count' => fn ($q) => $q->where('activo', true)])
            ->orderBy('nombre')
            ->paginate(30);

        $tarifa = Tarifa::actual();

        return view('super-admin.empresas.index', compact('empresas', 'tarifa'));
    }

    public function create(): View
    {
        return view('super-admin.empresas.create', [
            'versionTerminos' => config('legal.version_terminos'),
            'versionEncargo' => config('legal.version_encargo'),
        ]);
    }

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

        $empresa = DB::transaction(function () use ($data, $request) {
            $empresa = Empresa::create([
                'nombre' => $data['nombre'],
                'nif' => $data['nif'] ?? null,
                'email_contacto' => $data['email_contacto'],
                'activa' => true,
            ]);

            $admin = User::create([
                'empresa_id' => $empresa->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'rol' => 'admin_empresa',
                'activo' => true,
                'password' => $data['admin_password'],
            ]);

            foreach (['terminos_condiciones' => 'version_terminos', 'encargo_tratamiento' => 'version_encargo'] as $documento => $configKey) {
                LegalAceptacion::create([
                    'empresa_id' => $empresa->id,
                    'user_id' => $admin->id,
                    'documento' => $documento,
                    'version' => config('legal.'.$configKey),
                    'aceptado_at' => now(),
                    'ip_address' => $request->ip(),
                ]);
            }

            AuditLog::registrar('empresa_creada', $empresa, ['admin_email' => $admin->email]);

            return $empresa;
        });

        return redirect()->route('super-admin.empresas.show', $empresa)->with('status', 'Empresa dada de alta.');
    }

    public function show(Empresa $empresa): View
    {
        $empresa->load('usuarios');

        $tarifa = Tarifa::actual();
        $empleadosActivos = $empresa->usuarios->where('activo', true)->count();

        return view('super-admin.empresas.show', compact('empresa', 'tarifa', 'empleadosActivos'));
    }

    public function activar(Empresa $empresa): RedirectResponse
    {
        $empresa->update(['activa' => true]);
        AuditLog::registrar('empresa_activada', $empresa);

        return back()->with('status', 'Empresa activada.');
    }

    public function desactivar(Empresa $empresa): RedirectResponse
    {
        $empresa->update(['activa' => false]);
        AuditLog::registrar('empresa_desactivada', $empresa);

        return back()->with('status', 'Empresa desactivada.');
    }
}
