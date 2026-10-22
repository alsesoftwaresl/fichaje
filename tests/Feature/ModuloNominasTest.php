<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuloNominasTest extends TestCase
{
    use RefreshDatabase;

    /** Enciende el cobro de Nóminas como módulo aparte (por defecto va incluido). */
    protected function cobrarNominas(float $precio = 7.50): Tarifa
    {
        $tarifa = Tarifa::actual();
        $tarifa->update([
            'nominas_modulo_activo' => true,
            'nominas_precio_mensual' => $precio,
            'stripe_price_nominas_id' => 'price_nominas_test',
        ]);

        return $tarifa->fresh();
    }

    protected function admin(Empresa $empresa): User
    {
        return User::factory()->adminEmpresa()->for($empresa)->create();
    }

    public function test_con_el_interruptor_apagado_nominas_va_incluido_para_todos(): void
    {
        $empresa = Empresa::factory()->create();

        $this->assertFalse(Tarifa::actual()->nominas_modulo_activo);
        $this->assertTrue($empresa->tieneModuloNominas());

        $this->actingAs($this->admin($empresa))->get(route('nominas.gestion.index'))->assertOk();
    }

    public function test_con_el_modulo_cobrado_aparte_una_empresa_suscrita_sin_el_no_puede_gestionar_nominas(): void
    {
        $this->cobrarNominas();
        $empresa = Empresa::factory()->create(); // suscripción activa, sin prueba y sin módulo
        $admin = $this->admin($empresa);

        $this->assertFalse($empresa->tieneModuloNominas());

        $this->actingAs($admin)->get(route('nominas.gestion.index'))
            ->assertRedirect(route('admin.facturacion.index'))
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'módulo de Nóminas'));

        // Un contable que no es admin recibe un 403.
        $contable = User::factory()->for($empresa)->create(['gestiona_nominas' => true]);
        $this->actingAs($contable)->get(route('nominas.gestion.index'))->assertForbidden();
    }

    public function test_los_empleados_siguen_viendo_sus_nominas_aunque_la_empresa_no_tenga_el_modulo(): void
    {
        $this->cobrarNominas();
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->get(route('nominas.mis'))->assertOk();
    }

    public function test_durante_la_prueba_gratis_el_modulo_esta_incluido(): void
    {
        $this->cobrarNominas();
        $empresa = Empresa::factory()->create();
        $empresa->subscriptions()->update(['trial_ends_at' => now()->addDays(10)]);

        $this->assertTrue($empresa->fresh()->tieneModuloNominas());
        $this->actingAs($this->admin($empresa))->get(route('nominas.gestion.index'))->assertOk();
    }

    public function test_una_licencia_puede_incluir_o_no_el_modulo(): void
    {
        $this->cobrarNominas();

        $con = Licencia::create(['codigo' => 'ACH-AAAA-BBBB', 'max_usos' => 1, 'incluye_nominas' => true]);
        $sin = Licencia::create(['codigo' => 'ACH-CCCC-DDDD', 'max_usos' => 1, 'incluye_nominas' => false]);

        $empresaCon = Empresa::factory()->sinSuscripcion()->create();
        $empresaCon->forceFill(['licencia_id' => $con->id])->save();
        $empresaSin = Empresa::factory()->sinSuscripcion()->create();
        $empresaSin->forceFill(['licencia_id' => $sin->id])->save();

        $this->assertTrue($empresaCon->fresh()->tieneModuloNominas());
        $this->assertFalse($empresaSin->fresh()->tieneModuloNominas());
    }

    public function test_el_admin_activa_y_quita_el_modulo_y_el_precio_entra_en_el_total(): void
    {
        $this->cobrarNominas(7.50);
        $empresa = Empresa::factory()->create();
        $admin = $this->admin($empresa);

        $this->actingAs($admin)->get(route('admin.facturacion.index'))
            ->assertOk()->assertSee('Activar el módulo de Nóminas')->assertSee('7.50€/mes');

        $this->actingAs($admin)->post(route('admin.facturacion.nominas.activar'))->assertRedirect();
        $this->assertTrue($empresa->fresh()->modulo_nominas);
        $this->assertTrue($empresa->fresh()->tieneModuloNominas());

        $this->actingAs($admin->fresh())->get(route('admin.facturacion.index'))
            ->assertOk()->assertSee('Módulo Nóminas:')->assertSee('Quitar el módulo');

        $this->actingAs($admin->fresh())->delete(route('admin.facturacion.nominas.desactivar'))->assertRedirect();
        $this->assertFalse($empresa->fresh()->modulo_nominas);
        $this->assertFalse($empresa->fresh()->tieneModuloNominas());
    }

    public function test_no_se_activa_sin_suscripcion_ni_sin_precio_sincronizado_ni_si_va_incluido(): void
    {
        $sinSuscripcion = Empresa::factory()->sinSuscripcion()->create();
        $admin = $this->admin($sinSuscripcion);

        // Interruptor apagado: no hay nada que activar.
        $this->actingAs($admin)->post(route('admin.facturacion.nominas.activar'))
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'incluido'));

        // Cobrado aparte pero sin suscripción.
        $this->cobrarNominas();
        $this->actingAs($admin)->post(route('admin.facturacion.nominas.activar'))
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'suscripción activa'));
        $this->assertFalse($sinSuscripcion->fresh()->modulo_nominas);

        // Con suscripción pero el precio aún no está en Stripe.
        Tarifa::actual()->update(['stripe_price_nominas_id' => null]);
        $suscrita = Empresa::factory()->create();
        $this->actingAs($this->admin($suscrita))->post(route('admin.facturacion.nominas.activar'))
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'sincronizado'));
        $this->assertFalse($suscrita->fresh()->modulo_nominas);
    }

    public function test_el_super_admin_configura_el_modulo_y_se_valida(): void
    {
        $super = User::factory()->superAdmin()->create();
        $base = ['precio_base_mensual' => 29, 'empleados_incluidos' => 5, 'precio_empleado_extra' => 1];

        $this->actingAs($super)->patch(route('super-admin.tarifas.update'), $base + ['nominas_modulo_activo' => '1', 'nominas_precio_mensual' => 9.9])->assertRedirect();
        $tarifa = Tarifa::actual();
        $this->assertTrue($tarifa->nominas_modulo_activo);
        $this->assertEquals(9.90, $tarifa->nominas_precio_mensual);

        $this->actingAs($super)->patch(route('super-admin.tarifas.update'), $base + ['nominas_modulo_activo' => '0', 'nominas_precio_mensual' => 9.9])->assertRedirect();
        $this->assertFalse(Tarifa::actual()->nominas_modulo_activo);

        $this->actingAs($super)->patch(route('super-admin.tarifas.update'), $base + ['nominas_precio_mensual' => -1])
            ->assertSessionHasErrors('nominas_precio_mensual');
    }

    public function test_los_codigos_de_licencia_guardan_si_incluyen_nominas(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->post(route('super-admin.licencias.store'), ['max_usos' => 1, 'incluye_nominas' => '0'])->assertRedirect();
        $this->assertFalse(Licencia::firstOrFail()->incluye_nominas);

        $this->actingAs($super)->get(route('super-admin.licencias.index'))->assertOk()->assertSee('Nóminas');
    }

    public function test_la_web_solo_habla_del_modulo_si_se_cobra_aparte(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Módulo Nóminas (opcional)')->assertDontSee('¿Las nóminas se pagan aparte?');
        $this->get('/llms-full.txt')->assertDontSee('Módulo opcional de Nóminas');

        $this->cobrarNominas(7.5);

        $this->get('/')->assertOk()->assertSee('Módulo Nóminas (opcional)')->assertSee('¿Las nóminas se pagan aparte?')->assertSee('7,50 € al mes por empresa');
        $this->get('/llms-full.txt')->assertSee('Módulo opcional de Nóminas', false);
        $this->get('/funciones')->assertSee('Es un módulo opcional');
    }
}
