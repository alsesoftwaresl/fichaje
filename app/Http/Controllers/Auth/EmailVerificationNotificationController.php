<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Reenvía el correo de verificación de email.
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Vuelve a enviar el enlace de verificación (si no estaba ya verificado).
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Te hemos enviado otro enlace de verificación.');
    }
}
