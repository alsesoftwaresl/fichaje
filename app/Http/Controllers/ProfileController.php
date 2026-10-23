<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Throwable;

/**
 * Perfil del usuario: editar su nombre y correo (la contraseña la cambia PasswordController).
 * No hay "eliminar cuenta": las bajas las da la empresa y los fichajes se conservan.
 */
class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $usuario->fill($request->validated());

        // Un correo nuevo no está comprobado: deja de contar como verificado hasta que se
        // confirme (al admin el panel se lo pide; así nadie se apropia de un correo ajeno).
        $cambioCorreo = $usuario->isDirty('email');

        if ($cambioCorreo) {
            $usuario->email_verified_at = null;
        }

        $usuario->save();

        if ($cambioCorreo && $usuario->email && $usuario->esAdminEmpresa()) {
            try {
                $usuario->sendEmailVerificationNotification();
            } catch (Throwable $e) {
                // Si el correo falla, desde la pantalla de verificación puede pedir otro.
                Log::warning('No se pudo enviar la verificación del nuevo correo: '.$e->getMessage());
            }
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
