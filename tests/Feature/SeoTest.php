<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_permite_la_web_publica_bloquea_las_privadas_y_enlaza_el_sitemap(): void
    {
        $texto = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('User-agent: *', $texto);
        $this->assertStringContainsString('Disallow: /admin', $texto);
        $this->assertStringContainsString('Disallow: /kiosko', $texto);
        $this->assertStringContainsString('Disallow: /deploy', $texto);
        $this->assertStringNotContainsString('Disallow: /build', $texto);
        $this->assertStringContainsString('User-agent: GPTBot', $texto);
        $this->assertStringContainsString('User-agent: ClaudeBot', $texto);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $texto);
    }

    public function test_el_sitemap_lista_las_paginas_publicas_y_no_las_privadas(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.route('home').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('guia.registro-jornada').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('guia.registro-digital').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('registro.create').'</loc>', $xml);
        $this->assertStringNotContainsString('/login', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'El sitemap debe ser XML válido.');
    }

    public function test_llms_txt_describe_el_producto_con_el_precio_real(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee('control horario')
            ->assertSee(route('guia.registro-jornada'), false);
    }

    public function test_las_paginas_publicas_se_pueden_indexar_y_el_resto_lleva_noindex(): void
    {
        foreach (['/', '/guia/registro-de-jornada', '/guia/registro-horario-digital-obligatorio', '/registro', '/sitemap.xml'] as $ruta) {
            $this->get($ruta)->assertOk()->assertHeaderMissing('X-Robots-Tag');
        }

        foreach (['/login', '/legal/terminos', '/legal/privacidad'] as $ruta) {
            $this->get($ruta)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    public function test_la_portada_trae_datos_estructurados_validos_y_las_preguntas_visibles(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $bloques);
        $this->assertNotEmpty($bloques[1]);

        $tipos = [];
        foreach ($bloques[1] as $json) {
            $datos = json_decode($json, true);
            $this->assertNotNull($datos, 'JSON-LD inválido: '.json_last_error_msg());
            $tipos = array_merge($tipos, array_column($datos['@graph'] ?? [$datos], '@type'));
        }

        foreach (['Organization', 'WebSite', 'SoftwareApplication', 'FAQPage'] as $tipo) {
            $this->assertContains($tipo, $tipos);
        }

        // Lo que va al JSON-LD tiene que estar también a la vista (requisito de Google).
        $this->assertStringContainsString('¿Es obligatorio el registro de jornada en España?', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString('summary_large_image', $html);
    }

    public function test_la_guia_tiene_un_unico_h1_titulo_y_canonical(): void
    {
        $html = $this->get('/guia/registro-de-jornada')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('<title>Registro de jornada obligatorio en España', $html);
        $this->assertStringContainsString('rel="canonical" href="'.route('guia.registro-jornada').'"', $html);
        $this->assertStringContainsString('"@type":"Article"', $html);
    }

    public function test_la_portada_no_promete_lo_que_no_puede_garantizar_y_muestra_la_identidad(): void
    {
        config(['legal.titular.email' => 'hola@ejemplo.test']);

        $html = $this->get('/')->assertOk()->getContent();

        // Nada de promesas absolutas de cumplimiento.
        $this->assertStringNotContainsString('La ley, cumplida', $html);
        $this->assertStringNotContainsString('imposible de manipular', $html);
        $this->assertStringNotContainsString('aguanta una inspección', $html);

        // Transparencia: la tarjeta de la prueba, las limitaciones y el titular.
        $this->assertStringContainsString('Se pide tarjeta', $html);
        $this->assertStringContainsString('Lo que no hace', $html);
        $this->assertStringContainsString('ALSE SOFTWARE, S.L.', $html);
        $this->assertStringContainsString('mailto:hola@ejemplo.test', $html);
        $this->assertStringContainsString(route('legal.aviso-legal'), $html);
    }

    public function test_el_aviso_legal_solo_muestra_los_datos_configurados(): void
    {
        config(['legal.titular' => ['nombre' => 'Empresa Test, S.L.', 'nif' => 'B12345678', 'domicilio' => null, 'email' => 'legal@ejemplo.test', 'telefono' => null, 'registro_mercantil' => null]]);

        $this->get('/legal/aviso-legal')
            ->assertOk()
            ->assertSee('Empresa Test, S.L.')
            ->assertSee('B12345678')
            ->assertSee('legal@ejemplo.test')
            ->assertDontSee('Domicilio:')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_los_ejemplos_de_precio_usan_la_tarifa_real(): void
    {
        $tarifa = \App\Models\Tarifa::actual();
        $esperado = number_format($tarifa->calcularPrecioMensual(20), 2, ',', '.');

        $this->get('/')->assertOk()->assertSee('20 empleados')->assertSee($esperado.' €');
    }

    public function test_el_precio_base_se_muestra_exacto_sin_redondear(): void
    {
        $tarifa = \App\Models\Tarifa::actual();

        $tarifa->precio_base_mensual = 29;
        $this->assertSame('29', $tarifa->precioBaseTexto());

        $tarifa->precio_base_mensual = 28.99;
        $this->assertSame('28,99', $tarifa->precioBaseTexto());
    }

    public function test_la_portada_cuenta_el_espacio_del_empleado_y_las_funciones_menos_visibles(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Cada empleado tiene su propio espacio')
            ->assertSee('Todos sus fichajes')
            ->assertSee('Sus nóminas')
            ->assertSee('Horario corrido o turno partido')
            ->assertSee('Resumen diario por correo')
            ->assertSee('Avisos de salida');
    }

    public function test_la_pagina_de_funciones_es_indexable_y_esta_en_sitemap_y_portada(): void
    {
        $this->get('/funciones')
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertSee('Todo lo que hace Achrono')
            ->assertSee('Nóminas al alcance de cada empleado')
            ->assertSee('Lo que no hace (todavía)')
            ->assertSee('"featureList"', false);

        $this->get('/sitemap.xml')->assertSee(route('funciones'));
        $this->get('/')->assertSee('Fichar, controlar y tenerlo todo ordenado')->assertSee(route('funciones'));
        $this->get('/llms.txt')->assertSee(route('funciones'));
    }

    public function test_llms_full_resume_toda_la_web_en_texto_plano_sin_entidades_html(): void
    {
        $respuesta = $this->get('/llms-full.txt')->assertOk();
        $respuesta->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $texto = $respuesta->getContent();

        foreach (['Funciones', 'Lo que no hace', 'Precio', 'Preguntas frecuentes', 'La ley, sin exageraciones', 'Contacto y datos del titular', 'IVA incluido', 'info@achrono.es'] as $trozo) {
            $this->assertStringContainsString($trozo, $texto);
        }

        // Es texto plano: nada de &quot; ni &#039; de un escapado HTML.
        $this->assertStringNotContainsString('&quot;', $texto);
        $this->assertStringNotContainsString('&#039;', $texto);
        $this->assertStringNotContainsString('S.L..', $texto);
        $this->get('/llms.txt')->assertSee(route('llms-full'));
    }

    public function test_el_sitemap_lleva_la_fecha_de_ultima_modificacion(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<lastmod>\\d{4}-\\d{2}-\\d{2}</lastmod>#', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }

    public function test_el_fichero_de_verificacion_de_indexnow_devuelve_la_clave_y_solo_esa(): void
    {
        $clave = config('seo.indexnow_key');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $clave);

        $this->get("/{$clave}.txt")->assertOk()->assertSee($clave, false)->assertHeaderMissing('X-Robots-Tag');
        $this->get('/'.str_repeat('a', 32).'.txt')->assertNotFound();
    }

    public function test_el_comando_indexnow_avisa_con_las_urls_publicas_y_la_clave(): void
    {
        config(['app.url' => 'https://achrono.es']);
        \Illuminate\Support\Facades\URL::forceRootUrl('https://achrono.es');
        \Illuminate\Support\Facades\URL::forceScheme('https');
        \Illuminate\Support\Facades\Http::fake(['api.indexnow.org/*' => \Illuminate\Support\Facades\Http::response('', 200)]);

        $this->artisan('seo:indexnow')->expectsOutputToContain('Aviso enviado.')->assertSuccessful();

        \Illuminate\Support\Facades\Http::assertSent(function ($peticion) {
            $datos = $peticion->data();

            return $peticion->url() === 'https://api.indexnow.org/indexnow'
                && $datos['host'] === 'achrono.es'
                && $datos['key'] === config('seo.indexnow_key')
                && str_ends_with($datos['keyLocation'], config('seo.indexnow_key').'.txt')
                && in_array('https://achrono.es/funciones', $datos['urlList'], true)
                && in_array('https://achrono.es/llms-full.txt', $datos['urlList'], true);
        });
    }

    public function test_indexnow_no_avisa_desde_una_direccion_local(): void
    {
        \Illuminate\Support\Facades\Http::fake();
        config(['app.url' => 'http://localhost:8000']);

        $this->artisan('seo:indexnow')->expectsOutputToContain('no es una dirección pública')->assertFailed();
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_indexnow_informa_si_el_buscador_rechaza_el_aviso(): void
    {
        config(['app.url' => 'https://achrono.es']);
        \Illuminate\Support\Facades\Http::fake(['api.indexnow.org/*' => \Illuminate\Support\Facades\Http::response('clave inválida', 403)]);

        $this->artisan('seo:indexnow')->expectsOutputToContain('IndexNow no ha aceptado')->assertFailed();
    }

    public function test_la_pagina_404_es_de_la_marca_y_no_se_indexa(): void
    {
        $this->get('/esta-pagina-no-existe')
            ->assertNotFound()
            ->assertSee('No encontramos esa página')
            ->assertSee(route('funciones'), false)
            ->assertSee('noindex', false);
    }

    public function test_el_contenido_compartido_es_el_mismo_en_la_web_y_en_llms_full(): void
    {
        $tarifa = \App\Models\Tarifa::actual();
        $primera = \App\Support\ContenidoWeb::preguntas($tarifa)[0][0];
        $titulo = \App\Support\ContenidoWeb::funciones()[0]['titulo'];

        $this->get('/')->assertSee($primera);
        $this->get('/funciones')->assertSee($titulo);
        $this->get('/llms-full.txt')->assertSee($primera, false)->assertSee($titulo, false);
    }

    public function test_con_app_url_https_todas_las_urls_generadas_salen_con_https(): void
    {
        config(['app.url' => 'https://achrono.es']);
        (new \App\Providers\AppServiceProvider(app()))->boot();

        $this->assertStringStartsWith('https://', route('home'));
        $this->assertStringStartsWith('https://', route('funciones'));
        $this->assertStringStartsWith('https://', url('/cualquier-cosa'));

        // Restablece el esquema para no afectar a otras pruebas.
        \Illuminate\Support\Facades\URL::forceScheme('http');
    }
}

