<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comando produccion:comprobar: detecta una configuración mala, no enseña claves y se puede
 * lanzar desde la ruta de despliegue.
 */
class ComprobarProduccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_con_la_configuracion_buena_todo_sale_bien(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.debug' => false, 'app.url' => 'https://achrono.es', 'queue.default' => 'sync',
            'session.encrypt' => true, 'session.secure' => true, 'mail.default' => 'smtp',
            'mail.from.address' => 'info@achrono.es',
        ]);

        $this->artisan('produccion:comprobar')
            ->expectsOutputToContain('OK     APP_DEBUG=false')
            ->expectsOutputToContain('Todo lo imprescindible está bien.')
            ->assertSuccessful();
    }

    public function test_con_debug_activado_falla_y_no_muestra_claves(): void
    {
        config(['app.debug' => true, 'cashier.secret' => 'sk_test_SECRETO123', 'app.key' => 'base64:CLAVESECRETA0000000000000000000000000000000=']);

        $this->artisan('produccion:comprobar')
            ->expectsOutputToContain('FALLO  APP_DEBUG está activado')
            ->doesntExpectOutputToContain('SECRETO123')
            ->doesntExpectOutputToContain('CLAVESECRETA')
            ->assertFailed();
    }

    public function test_se_puede_lanzar_desde_la_ruta_de_despliegue(): void
    {
        config(['deploy.token' => str_repeat('a', 40)]);

        $this->get('/deploy/ejecutar?token='.str_repeat('a', 40).'&cmd=produccion:comprobar')
            ->assertOk()
            ->assertSee('Comprobación de producción');
    }
}
