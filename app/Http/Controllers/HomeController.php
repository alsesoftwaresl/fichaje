<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'tarifa' => Tarifa::actual(),
        ]);
    }
}
