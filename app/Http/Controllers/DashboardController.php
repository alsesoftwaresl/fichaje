<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
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
