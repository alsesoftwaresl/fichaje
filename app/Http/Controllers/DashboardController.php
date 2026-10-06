<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Tras el login lleva a cada rol a su pantalla de inicio.
 */
class DashboardController extends Controller
{
    /**
     * super_admin → empresas, admin_empresa → panel, empleado → sus fichajes.
     */
    public function __invoke(): RedirectResponse
    {
        $user = Auth::user();

        return match ($user->rol) {
            'super_admin' => redirect()->route('super-admin.empresas.index'),
            'admin_empresa' => redirect()->route('admin.panel.index'),
            default => redirect()->route('fichajes.mis'),
        };
    }
}
