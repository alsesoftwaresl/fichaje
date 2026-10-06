<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pantalla que obliga a elegir una contraseña propia a quien entró con una temporal (la
 * que le dio un admin). El middleware ForzarCambioPassword manda aquí a quien lo tenga
 * pendiente.
 */
class ForzarCambioPasswordController extends Controller
{
    /**
     * Muestra el formulario; si no hay cambio pendiente, manda al inicio.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->debe_cambiar_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.forzar-password');
    }

    /**
     * Guarda la contraseña nueva (distinta de la temporal) y quita la marca de cambio
     * obligatorio.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $usuario = $request->user();

        if (Hash::check($request->input('password'), $usuario->password)) {
            throw ValidationException::withMessages([
                'password' => 'Elige una contraseña distinta de la temporal que te han dado.',
            ]);
        }

        $usuario->update([
            'password' => $request->input('password'),
            'debe_cambiar_password' => false,
        ]);

        return redirect()->route('dashboard')->with('status', 'Contraseña guardada. Ya puedes usar la aplicación.');
    }
}
