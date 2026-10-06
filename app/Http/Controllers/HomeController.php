<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use Illuminate\View\View;

/**
 * Portada pública (landing) de Achrono con el precio actual.
 */
class HomeController extends Controller
{
    /**
     * Pinta la portada con la tarifa vigente.
     */
    public function __invoke(): View
    {
        return view('welcome', [
            'tarifa' => Tarifa::actual(),
        ]);
    }
}
