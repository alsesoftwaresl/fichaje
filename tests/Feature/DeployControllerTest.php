<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeployControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_ruta_da_404_si_no_hay_deploy_token_configurado(): void
    {
        config(['deploy.token' => null]);

        $this->get('/deploy/ejecutar?token=lo-que-sea&cmd=migrate')->assertNotFound();
    }

    public function test_la_ruta_da_404_con_un_token_incorrecto(): void
    {
        config(['deploy.token' => 'el-token-correcto']);

        $this->get('/deploy/ejecutar?token=otro-token&cmd=migrate')->assertNotFound();
    }

    public function test_rechaza_comandos_fuera_de_la_lista_permitida(): void
    {
        config(['deploy.token' => 'el-token-correcto']);

        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=db:wipe')
            ->assertStatus(422);
    }

    public function test_el_modo_mantenimiento_exige_un_secreto_valido(): void
    {
        config(['deploy.token' => 'el-token-correcto']);

        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=down')->assertStatus(422);
        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=down&secreto=corto')->assertStatus(422);
        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=down&secreto=con%20espacios%20y/rutas')->assertStatus(422);
    }

    public function test_activa_y_desactiva_el_mantenimiento_con_el_secreto(): void
    {
        config(['deploy.token' => 'el-token-correcto']);

        // Se simula Artisan: el real escribiría storage/framework/down y
        // dejaría en mantenimiento el servidor local mientras corren las pruebas.
        Artisan::shouldReceive('call')->once()->with('down', ['--secret' => 'clave-segura-123'])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('up', [])->andReturn(0);
        Artisan::shouldReceive('output')->twice()->andReturn('ok');

        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=down&secreto=clave-segura-123')
            ->assertOk()
            ->assertSee('/clave-segura-123');

        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=up')->assertOk();
    }

    public function test_db_seed_crea_el_super_admin_inicial_sin_duplicarlo(): void
    {
        config(['deploy.token' => 'el-token-correcto']);

        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=db:seed')->assertOk();
        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=db:seed')->assertOk();

        $this->assertSame(1, \App\Models\User::where('rol', 'super_admin')->count());
    }

    public function test_ejecuta_un_comando_permitido_con_el_token_correcto(): void
    {
        config(['deploy.token' => 'el-token-correcto']);

        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=cache:clear')
            ->assertOk();
    }
}
