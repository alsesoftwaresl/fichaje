<?php

namespace App\Console\Commands;

use App\Http\Controllers\SeoController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Avisa a los buscadores que usan el protocolo IndexNow (Bing, Yandex, Seznam, Naver...) de
 * las páginas públicas, para que las rastreen enseguida. Bing alimenta a varios asistentes de
 * IA, así que acelera que aparezcan allí. Google no usa IndexNow: para Google está el
 * sitemap en Search Console.
 */
class AvisarIndexNow extends Command
{
    protected $signature = 'seo:indexnow';

    protected $description = 'Avisa a Bing y otros buscadores (IndexNow) de las páginas públicas';

    /**
     * Envía a IndexNow las URL públicas del sitemap (no hace nada si la web no es
     * pública).
     */
    public function handle(): int
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $host || in_array($host, ['localhost', '127.0.0.1'], true) || str_ends_with($host, '.test')) {
            $this->warn("APP_URL ({$host}) no es una dirección pública: no se avisa a nadie.");

            return self::FAILURE;
        }

        $clave = config('seo.indexnow_key');
        $urls = SeoController::urlsPublicas();

        $respuesta = Http::timeout(20)->acceptJson()->post('https://api.indexnow.org/indexnow', [
            'host' => $host,
            'key' => $clave,
            'keyLocation' => route('indexnow.clave', $clave),
            'urlList' => $urls,
        ]);

        $this->line('Páginas enviadas: '.count($urls));
        $this->line('Respuesta de IndexNow: HTTP '.$respuesta->status());

        if (! in_array($respuesta->status(), [200, 202], true)) {
            $this->error('IndexNow no ha aceptado el aviso: '.mb_substr($respuesta->body(), 0, 200));

            return self::FAILURE;
        }

        $this->info('Aviso enviado.');

        return self::SUCCESS;
    }
}
