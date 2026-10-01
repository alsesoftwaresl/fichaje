<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequireSuscripcionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sin_suscripcion_es_redirigido_a_facturacion(): void
    {
        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)
            ->get(route('admin.panel.index'))
            ->assertRedirect(route('admin.facturacion.index'));

        $this->actingAs($admin)
            ->get(route('admin.empleados.index'))
            ->assertRedirect(route('admin.facturacion.index'));
    }

    public function test_empleado_sin_suscripcion_ve_la_pantalla_de_bloqueo(): void
    {
        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)
            ->get(route('fichajes.mis'))
            ->assertForbidden()
            ->assertViewIs('suscripcion.bloqueado');
    }

    public function test_facturacion_sigue_accesible_sin_suscripcion(): void
    {
        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)
            ->get(route('admin.facturacion.index'))
            ->assertOk();
    }

    public function test_empresa_con_suscripcion_activa_accede_con_normalidad(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)
            ->get(route('admin.panel.index'))
            ->assertOk();
    }

    public function test_el_kiosco_bloquea_el_fichaje_si_no_hay_suscripcion(): void
    {
        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $empleado = User::factory()->for($empresa)->create();
        $pin = $empleado->generarNuevoPin();

        $this->get(route('kiosko.show', $empresa->kiosko_token))
            ->assertOk()
            ->assertViewIs('kiosko.bloqueado');

        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin])
            ->assertRedirect(route('kiosko.show', $empresa->kiosko_token));

        $this->assertDatabaseCount('fichajes', 0);
    }

    public function test_el_kiosco_funciona_con_suscripcion_activa(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        $pin = $empleado->generarNuevoPin();

        $this->get(route('kiosko.show', $empresa->kiosko_token))
            ->assertOk()
            ->assertViewIs('kiosko.show');

        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin])
            ->assertRedirect(route('kiosko.show', $empresa->kiosko_token));

        $this->assertDatabaseCount('fichajes', 1);
    }
}
