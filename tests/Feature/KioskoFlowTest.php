<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KioskoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_fichar_con_pin_correcto_registra_el_fichaje(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        $pin = $empleado->generarNuevoPin();

        $this->get(route('kiosko.show', $empresa->kiosko_token))->assertOk();

        $response = $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin]);

        $response->assertRedirect(route('kiosko.show', $empresa->kiosko_token));
        $this->assertDatabaseHas('fichajes', [
            'user_id' => $empleado->id,
            'empresa_id' => $empresa->id,
            'tipo' => 'entrada',
            'origen' => 'kiosco',
        ]);
    }

    public function test_fichar_con_pin_incorrecto_no_registra_nada(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        $empleado->generarNuevoPin();

        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => '000000'])
            ->assertSessionHasErrors('pin');

        $this->assertDatabaseCount('fichajes', 0);
    }

    public function test_empleado_inactivo_no_puede_fichar_por_kiosco(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->inactivo()->for($empresa)->create();
        $pin = $empleado->generarNuevoPin();

        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin])
            ->assertSessionHasErrors('pin');

        $this->assertDatabaseCount('fichajes', 0);
    }

    public function test_un_pin_no_identifica_a_empleados_de_otra_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $empleadoA = User::factory()->for($empresaA)->create();
        $pin = $empleadoA->generarNuevoPin();

        // El mismo PIN probado contra el kiosco de OTRA empresa no debe fichar a nadie.
        $this->post(route('kiosko.fichar', $empresaB->kiosko_token), ['pin' => $pin])
            ->assertSessionHasErrors('pin');

        $this->assertDatabaseCount('fichajes', 0);
    }

    public function test_token_de_kiosco_invalido_devuelve_404(): void
    {
        $this->get('/kiosko/token-que-no-existe')->assertNotFound();
    }

    public function test_empresa_inactiva_devuelve_404_en_el_kiosco(): void
    {
        $empresa = Empresa::factory()->create(['activa' => false]);

        $this->get(route('kiosko.show', $empresa->kiosko_token))->assertNotFound();
    }

    public function test_el_toggle_entrada_salida_funciona_igual_que_desde_la_web(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        $pin = $empleado->generarNuevoPin();

        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin]);
        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin]);

        $this->assertSame(2, Fichaje::where('user_id', $empleado->id)->count());
        $this->assertDatabaseHas('fichajes', ['user_id' => $empleado->id, 'tipo' => 'entrada']);
        $this->assertDatabaseHas('fichajes', ['user_id' => $empleado->id, 'tipo' => 'salida']);
    }
}
