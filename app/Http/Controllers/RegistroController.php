<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use App\Services\AltaEmpresaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Alta pública de empresa: a diferencia de SuperAdmin\EmpresaController, esta
 * la rellena directamente quien va a ser el admin_empresa, sin que nadie
 * se la tenga que crear a mano. Al terminar queda con la sesión iniciada y
 * aterriza en Facturación — el middleware "suscripcion" ya se encarga de
 * que no pueda usar nada más hasta que contrate un plan.
 */
class RegistroController extends Controller
{
    /**
     * Formulario público de registro de empresa.
     */
    public function create(): View
    {
        return view('registro.create', [
            'tarifa' => Tarifa::actual(),
            'versionTerminos' => config('legal.version_terminos'),
            'versionEncargo' => config('legal.version_encargo'),
        ]);
    }

    /**
     * Crea empresa + admin (con la aceptación legal), inicia sesión y envía el correo
     * de verificación.
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

        ['admin' => $admin] = AltaEmpresaService::crear($data, $request->ip(), requiereVerificacionEmail: true);

        Auth::login($admin);
        $admin->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
