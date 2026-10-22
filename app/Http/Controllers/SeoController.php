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

    /** Páginas públicas que van al sitemap: ruta => [prioridad, vista cuya fecha marca el lastmod]. */
    protected const PAGINAS = [
        'home' => ['1.0', 'welcome'],
        'funciones' => ['0.9', 'funciones'],
        'guia.registro-jornada' => ['0.9', 'guia/registro-jornada'],
        'guia.registro-digital' => ['0.8', 'guia/registro-digital'],
        'registro.create' => ['0.8', 'registro/create'],
        'contacto.create' => ['0.6', 'contacto'],
        'plantilla.index' => ['0.9', 'plantilla/index'],
        'sectores.index' => ['0.7', 'sector/index'],
    ];

    /** URLs públicas (las del sitemap y los ficheros para IAs), por ejemplo para avisar a IndexNow. */
    public static function urlsPublicas(): array
    {
        return array_merge(
            array_map(fn ($ruta) => route($ruta), array_keys(self::PAGINAS)),
            array_map(fn ($slug) => route('sector.show', $slug), array_keys(config('sectores'))),
            [route('llms'), route('llms-full')]
        );
    }

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
        $entradas = collect(self::PAGINAS)->map(fn ($datos, $ruta) => [
            route($ruta), $datos[0], resource_path('views/'.$datos[1].'.blade.php'),
        ]);

        // Una página por sector: su fecha es la del fichero de contenido.
        foreach (array_keys(config('sectores')) as $slug) {
            $entradas->push([route('sector.show', $slug), '0.8', config_path('sectores.php')]);
        }

        $urls = $entradas->map(function ($e) {
            [$url, $prioridad, $fichero] = $e;
            $fecha = is_file($fichero) ? gmdate('Y-m-d', filemtime($fichero)) : null;

            return "  <url>\n    <loc>".e($url)."</loc>\n".($fecha ? "    <lastmod>{$fecha}</lastmod>\n" : '')."    <priority>{$prioridad}</priority>\n  </url>";
        })->implode("\n");

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$urls}\n</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
    /**
     * Versión larga de llms.txt: todo el contenido de la web en texto plano (producto,
     * funciones, límites, precio, preguntas, ley y contacto) para que un asistente de IA lo
     * cite sin tener que rastrear página por página.
     */
    public function llmsFull(): Response
    {
        $tarifa = \App\Models\Tarifa::actual();

        return response(view('seo.llms-full', [
            'tarifa' => $tarifa,
            'preguntas' => \App\Support\ContenidoWeb::preguntas($tarifa),
            'funciones' => \App\Support\ContenidoWeb::funciones(),
        ])->render(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * Fichero de verificación de IndexNow: /{clave}.txt debe devolver la propia clave.
     */
    public function claveIndexNow(string $clave): Response
    {
        abort_unless(hash_equals((string) config('seo.indexnow_key'), $clave), 404);

        return response($clave, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
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
