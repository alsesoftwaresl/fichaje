<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Lee el mes de la petición (?mes=AAAA-MM) con límites razonables; si no viene o no vale
 * (URL tocada a mano), el mes actual. Lo usan la plantilla gratuita y los informes.
 */
trait MesDeLaPeticion
{
    protected function mes(Request $request): Carbon
    {
        $valor = is_string($request->query('mes')) ? $request->query('mes') : '';

        if (preg_match('/^(20[2-9]\d)-(0[1-9]|1[0-2])$/', $valor, $m)) {
            return Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
        }

        return now()->startOfMonth();
    }
}
