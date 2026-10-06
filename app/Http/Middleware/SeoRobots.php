<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Por defecto ninguna página debe aparecer en buscadores: solo las públicas
 * listadas aquí se pueden indexar. Todo lo demás (paneles, login, kiosco, textos
 * legales en borrador...) lleva la cabecera "X-Robots-Tag: noindex" como red de
 * seguridad, además del Disallow de robots.txt.
 */
class SeoRobots
{
    /** Rutas públicas que sí queremos en Google y en las IAs. */
    protected const INDEXABLES = ['home', 'guia.registro-jornada', 'registro.create', 'sitemap', 'robots', 'llms'];

    /**
     * Añade noindex a la respuesta si la ruta no está en la lista de indexables.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if (! in_array($request->route()?->getName(), self::INDEXABLES, true)) {
            $respuesta->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $respuesta;
    }
}
