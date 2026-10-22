<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use Illuminate\View\View;

/**
 * Páginas por tipo de negocio (/control-horario y /control-horario/{sector}): explican cómo
 * ayuda la aplicación en cada caso, con el contenido de config/sectores.php.
 */
class SectorController extends Controller
{
    /**
     * Lista de sectores, con enlace a cada página.
     */
    public function index(): View
    {
        return view('sector.index', ['sectores' => config('sectores')]);
    }

    /**
     * Página de un sector; 404 si el sector no existe.
     */
    public function show(string $sector): View
    {
        $datos = config('sectores.'.$sector);

        abort_if(! is_array($datos), 404);

        $tarifa = Tarifa::actual();

        // {precio}, {incluidos}, {extra} y {nombre} se rellenan con la tarifa y la marca actuales.
        $sustituir = fn (string $texto) => strtr($texto, [
            '{precio}' => $tarifa->precioBaseTexto(),
            '{incluidos}' => (string) $tarifa->empleados_incluidos,
            '{extra}' => number_format((float) $tarifa->precio_empleado_extra, 2, ',', '.'),
            '{nombre}' => config('app.name'),
        ]);

        $datos['faqs'] = array_map(fn (array $f) => [$f[0], $sustituir($f[1])], $datos['faqs']);

        return view('sector.show', [
            'slug' => $sector,
            'sector' => $datos,
            'otros' => collect(config('sectores'))->except($sector),
        ]);
    }
}
