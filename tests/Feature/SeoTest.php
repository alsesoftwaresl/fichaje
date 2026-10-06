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
        foreach (['/', '/guia/registro-de-jornada', '/registro', '/sitemap.xml'] as $ruta) {
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
}
