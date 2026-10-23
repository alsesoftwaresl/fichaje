<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en todas las respuestas (también la de mantenimiento y las de
 * error, por eso va la primera de la pila global).
 *
 * - nosniff: el navegador no "adivina" el tipo de un fichero (un PDF no se ejecuta como HTML).
 * - frame-ancestors / X-Frame-Options: nadie puede meter la app en un iframe de otra web
 *   para engañar al usuario con clics (clickjacking), p. ej. en el kiosco o en Facturación.
 * - form-action: los formularios solo envían a la propia web y a Stripe (el pago es un
 *   formulario nuestro que redirige a su Checkout), así un HTML inyectado no puede mandar
 *   datos a otro sitio.
 * - base-uri y object-src: cierran dos vías clásicas de inyección.
 * - Referrer-Policy: a otras webs solo les llega el dominio, nunca rutas con tokens (el
 *   kiosco lleva el suyo en la URL).
 * - Permissions-Policy: la app no usa cámara, micrófono ni ubicación; así ninguna página
 *   puede pedirlos.
 * - HSTS (solo en producción con https): el navegador no vuelve a entrar por http.
 *
 * No se limita script-src: Alpine.js evalúa expresiones de las vistas y lo necesita.
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $cabeceras = $respuesta->headers;

        $cabeceras->set('X-Content-Type-Options', 'nosniff');
        $cabeceras->set('X-Frame-Options', 'SAMEORIGIN');
        $cabeceras->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $cabeceras->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $cabeceras->set('Content-Security-Policy', implode('; ', [
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "form-action 'self' https://checkout.stripe.com https://billing.stripe.com",
        ]));

        // Detrás del proxy del hosting la petición llega como http aunque el usuario use
        // https, así que se decide por la URL configurada y no por $request->isSecure().
        if (app()->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            $cabeceras->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // No anunciar la versión de PHP.
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $respuesta;
    }
}
