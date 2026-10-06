<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_ejecuta_un_comando_permitido_con_el_token_correcto(): void
    {
        config(['deploy.token' => 'el-token-correcto']);

        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=cache:clear')
            ->assertOk();
    }
}
