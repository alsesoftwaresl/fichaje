<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use App\Services\AltaEmpresaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Segundo paso del alta cuando se viene de "Continuar con Google": el
 * nombre y el email del admin ya se saben (de Google, en sesión) y están
 * verificados por ellos, así que aquí solo se piden los datos de la
 * empresa y la aceptación legal — nunca contraseña ni email.
 */
class RegistroCompletarController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $datosGoogle = $request->session()->get('registro_google');

        if (! $datosGoogle) {
            return redirect()->route('registro.create');
        }

        return view('registro.completar', [
            'tarifa' => Tarifa::actual(),
            'versionTerminos' => config('legal.version_terminos'),
            'versionEncargo' => config('legal.version_encargo'),
            'nombreAdmin' => $datosGoogle['nombre'],
            'emailAdmin' => $datosGoogle['email'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datosGoogle = $request->session()->get('registro_google');

        abort_unless($datosGoogle, 419, 'La sesión de Google ha caducado, vuelve a intentarlo.');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:20', 'unique:empresas,nif'],
            'email_contacto' => ['required', 'email', 'max:255'],
            'acepta_terminos' => ['accepted'],
            'acepta_encargo' => ['accepted'],
        ]);

        ['admin' => $admin] = AltaEmpresaService::crear([
            ...$data,
            'admin_name' => $datosGoogle['nombre'],
            'admin_email' => $datosGoogle['email'],
            // Nadie va a usarla — este admin entra siempre por Google.
            'admin_password' => Hash::make(Str::random(32)),
        ], $request->ip(), requiereVerificacionEmail: false);

        $request->session()->forget('registro_google');

        Auth::login($admin);

        return redirect()->route('admin.facturacion.index')
            ->with('status', '¡Bienvenido a Achrono! Ya puedes elegir tu plan para empezar a fichar.');
    }
}
