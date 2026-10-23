<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Una URL tocada a mano (fecha imposible, una lista donde se espera un texto...) no debe
 * romper ninguna página con un error 500: se ignora el valor raro.
 */
class ParametrosRarosTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_filtros_del_admin_ignoran_valores_raros(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        $this->actingAs($admin)->get('/admin/fichajes?desde=abc&hasta=2026-13-45&user_id=xyz')->assertOk();
        $this->actingAs($admin)->get('/admin/fichajes?desde[]=1')->assertOk();
        $this->actingAs($admin)->get('/admin/fichajes/exportar/csv?hasta=9999-99-99')->assertOk();
        $this->actingAs($admin)->get('/admin/ausencias?estado[]=pendiente')->assertOk();
        $this->actingAs($admin)->get('/admin/ausencias?estado=inventado')->assertOk();
        $this->actingAs($admin)->get('/admin/incidencias?desde=abc&hasta[]=1')->assertStatus(302);
    }

    public function test_las_paginas_publicas_ignoran_valores_raros(): void
    {
        $this->get('/contacto?asunto[]=x')->assertOk();
        $this->get('/plantilla-registro-jornada/excel?mes[]=2026-01')->assertOk();
        $this->get('/plantilla-registro-jornada/pdf?mes=2026-99')->assertOk();
    }

    public function test_el_kiosco_rechaza_un_dni_que_llega_como_lista(): void
    {
        $empresa = Empresa::factory()->create(['kiosko_modo' => 'dni']);

        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['dni' => ['12345678Z']])
            ->assertSessionHasErrors(['dni' => 'No hemos encontrado ese DNI o NIE.']);
    }

    public function test_el_despliegue_no_falla_con_un_token_en_forma_de_lista(): void
    {
        config(['deploy.token' => str_repeat('a', 40)]);

        $this->get('/deploy/ejecutar?token[]=x')->assertNotFound();
    }
}
