<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_manifiesto_es_valido_e_incluye_iconos_que_existen(): void
    {
        $manifiesto = json_decode(file_get_contents(public_path('manifest.json')), true);

        $this->assertNotNull($manifiesto, 'manifest.json debe ser JSON válido.');
        $this->assertSame('standalone', $manifiesto['display']);
        $this->assertSame('/dashboard', $manifiesto['start_url']);
        $this->assertSame('es', $manifiesto['lang']);
        $this->assertNotEmpty($manifiesto['name']);
        $this->assertNotEmpty($manifiesto['short_name']);

        $tamanos = [];
        foreach ($manifiesto['icons'] as $icono) {
            $this->assertFileExists(public_path(ltrim($icono['src'], '/')), 'Falta el icono '.$icono['src']);
            $tamanos[] = $icono['sizes'].':'.$icono['purpose'];
        }

        // Android exige un icono de 192 y uno de 512; uno "maskable" se ve bien con cualquier máscara.
        $this->assertContains('192x192:any', $tamanos);
        $this->assertContains('512x512:any', $tamanos);
        $this->assertContains('512x512:maskable', $tamanos);

        foreach ($manifiesto['shortcuts'] as $atajo) {
            $this->assertStringStartsWith('/', $atajo['url']);
        }
    }

    public function test_los_iconos_tienen_el_tamano_declarado(): void
    {
        foreach (['icon-192.png' => 192, 'icon-512.png' => 512, 'icon-maskable-512.png' => 512] as $archivo => $lado) {
            [$ancho, $alto] = getimagesize(public_path('images/'.$archivo));
            $this->assertSame([$lado, $lado], [$ancho, $alto], $archivo);
        }
    }

    public function test_el_service_worker_no_guarda_paginas_ni_datos_privados_en_cache(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        // Solo precarga la página sin conexión y el icono; nunca guarda respuestas de la app.
        $this->assertStringContainsString('/offline.html', $sw);
        $this->assertStringNotContainsString('cache.put', $sw);
        $this->assertStringNotContainsString('cache.add(', $sw);
        $this->assertFileExists(public_path('offline.html'));
    }

    public function test_las_paginas_enlazan_el_manifiesto_y_el_color_de_la_app(): void
    {
        foreach (['/', '/funciones', '/login'] as $ruta) {
            $this->get($ruta)->assertOk()
                ->assertSee('rel="manifest"', false)
                ->assertSee('manifest.json', false)
                ->assertSee('name="theme-color"', false);
        }
    }

    public function test_el_boton_de_instalar_solo_sale_con_sesion_iniciada(): void
    {
        $this->get('/login')->assertDontSee('x-data="instalarApp"', false);

        $admin = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create();

        $this->actingAs($admin)->get(route('admin.panel.index'))
            ->assertOk()
            ->assertSee('x-data="instalarApp"', false)
            ->assertSee('Instala Achrono en tu móvil')
            ->assertSee('Instalar app')
            ->assertSee('Añadir a pantalla de inicio');
    }
}
