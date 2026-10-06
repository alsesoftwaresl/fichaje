<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Ficheros que leen los buscadores y las IAs: robots.txt, sitemap.xml y
 * llms.txt. Se generan desde aquí (no son estáticos) para que usen siempre la
 * URL real de la web (APP_URL) y se mantengan al día con las rutas públicas.
 */
class SeoController extends Controller
{
    /** Zonas privadas: no tienen nada que indexar y no hay que gastar rastreo en ellas. */
    protected const RUTAS_PRIVADAS = [
        '/admin', '/super-admin', '/dashboard', '/login', '/forgot-password', '/reset-password',
        '/verify-email', '/confirm-password', '/forzar-password', '/profile', '/mis-fichajes',
        '/mis-ausencias', '/mis-nominas', '/gestion-nominas', '/nominas', '/citas', '/fichajes',
        '/kiosko', '/deploy', '/stripe', '/auth', '/registro/completar',
    ];

    /** Buscadores con IA y rastreadores de modelos: se les permite expresamente leer la web pública. */
    protected const ROBOTS_IA = [
        'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-User', 'Claude-SearchBot',
        'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'Bingbot',
    ];

    /** Páginas públicas que van al sitemap: ruta => prioridad. */
    protected const PAGINAS = [
        'home' => '1.0',
        'guia.registro-jornada' => '0.9',
        'registro.create' => '0.8',
    ];

    public function robots(): Response
    {
        $bloque = "Allow: /\n".collect(self::RUTAS_PRIVADAS)->map(fn ($r) => "Disallow: {$r}")->implode("\n");

        $texto = "User-agent: *\n{$bloque}\n\n";

        foreach (self::ROBOTS_IA as $bot) {
            $texto .= "User-agent: {$bot}\n{$bloque}\n\n";
        }

        $texto .= 'Sitemap: '.route('sitemap')."\n";

        return response($texto, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $urls = collect(self::PAGINAS)->map(fn ($prioridad, $ruta) => "  <url>\n    <loc>".e(route($ruta))."</loc>\n    <priority>{$prioridad}</priority>\n  </url>")->implode("\n");

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$urls}\n</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Descripción en texto plano pensada para los asistentes de IA (convención
     * llms.txt): qué es el producto y dónde está la información fiable.
     */
    public function llms(): Response
    {
        return response(view('seo.llms', ['tarifa' => \App\Models\Tarifa::actual()])->render(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
