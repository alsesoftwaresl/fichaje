<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForzarCambioPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->debe_cambiar_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.forzar-password');
    }

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
