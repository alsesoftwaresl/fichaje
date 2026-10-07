<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanAsesoriasTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_valores_por_defecto_reflejan_el_ejemplo_de_la_asesoria(): void
    {
        $tarifa = Tarifa::actual();

        $this->assertEquals(6.00, $tarifa->partner_precio_licencia);
        $this->assertSame(5, $tarifa->partner_empleados_incluidos);
        $this->assertSame(5, $tarifa->partner_licencias_minimas);
        $this->assertEquals(9.90, $tarifa->partner_pvp_recomendado);
        // 5 licencias × 6 € = 30 € que paga la asesoría; margen 3,90 € por licencia.
        $this->assertSame(30.0, $tarifa->partnerPagoMensual(5));
        $this->assertSame(3.9, $tarifa->partnerMargenPorLicencia());
    }

    public function test_el_super_admin_edita_todos_los_precios_de_asesorias(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->patch(route('super-admin.tarifas.update'), [
            'precio_base_mensual' => 29,
            'empleados_incluidos' => 5,
            'precio_empleado_extra' => 1,
            'partner_precio_licencia' => 7.5,
            'partner_empleados_incluidos' => 8,
            'partner_precio_empleado_extra' => 0.8,
            'partner_licencias_minimas' => 10,
            'partner_pvp_recomendado' => 12.9,
        ])->assertRedirect(route('super-admin.tarifas.edit'));

        $tarifa = Tarifa::actual();
        $this->assertEquals(7.5, $tarifa->partner_precio_licencia);
        $this->assertSame(8, $tarifa->partner_empleados_incluidos);
        $this->assertEquals(0.8, $tarifa->partner_precio_empleado_extra);
        $this->assertSame(10, $tarifa->partner_licencias_minimas);
        $this->assertEquals(12.9, $tarifa->partner_pvp_recomendado);

        $this->actingAs($super)->get(route('super-admin.tarifas.edit'))
            ->assertOk()->assertSee('Plan para asesorías')->assertSee('Margen de la asesoría');
    }

    public function test_no_admite_valores_invalidos_en_el_plan_de_asesorias(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->patch(route('super-admin.tarifas.update'), [
            'precio_base_mensual' => 29,
            'empleados_incluidos' => 5,
            'precio_empleado_extra' => 1,
            'partner_precio_licencia' => -1,
            'partner_empleados_incluidos' => 0,
            'partner_licencias_minimas' => 0,
        ])->assertSessionHasErrors(['partner_precio_licencia', 'partner_empleados_incluidos', 'partner_licencias_minimas']);
    }

    public function test_un_admin_de_empresa_no_puede_cambiar_los_precios_de_asesorias(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create();

        $this->actingAs($admin)->patch(route('super-admin.tarifas.update'), [
            'precio_base_mensual' => 29, 'empleados_incluidos' => 5, 'precio_empleado_extra' => 1,
            'partner_precio_licencia' => 0,
        ])->assertForbidden();

        $this->assertEquals(6.00, Tarifa::actual()->partner_precio_licencia);
    }

    public function test_los_codigos_nuevos_proponen_los_empleados_de_la_tarifa_y_guardan_el_tope(): void
    {
        $super = User::factory()->superAdmin()->create();
        Tarifa::actual()->update(['partner_empleados_incluidos' => 7]);

        $this->actingAs($super)->get(route('super-admin.licencias.index'))
            ->assertOk()->assertSee('value="7"', false);

        $this->actingAs($super)->post(route('super-admin.licencias.store'), ['max_usos' => 5, 'max_empleados' => 7, 'meses' => 12])
            ->assertRedirect();

        $this->assertSame(7, Licencia::firstOrFail()->max_empleados);
    }

    public function test_una_empresa_con_licencia_no_puede_superar_su_tope_de_empleados(): void
    {
        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $licencia = Licencia::create(['codigo' => 'ACH-AAAA-BBBB', 'max_usos' => 1, 'max_empleados' => 2]);
        $empresa->forceFill(['licencia_id' => $licencia->id])->save();

        // El admin ya cuenta como 1 usuario activo; cabe 1 empleado más.
        $this->actingAs($admin)->post(route('admin.empleados.store'), ['name' => 'Ana', 'dni_nie' => '12345678Z'])
            ->assertRedirect(route('admin.empleados.index'));

        $this->actingAs($admin)->post(route('admin.empleados.store'), ['name' => 'Luis', 'dni_nie' => '87654321X'])
            ->assertSessionHasErrors('name');

        $this->assertSame(2, $empresa->usuarios()->where('activo', true)->count());
    }

    public function test_sin_tope_en_la_licencia_o_con_suscripcion_no_hay_limite(): void
    {
        $sinTope = Empresa::factory()->sinSuscripcion()->create();
        $licencia = Licencia::create(['codigo' => 'ACH-CCCC-DDDD', 'max_usos' => 1, 'max_empleados' => null]);
        $sinTope->forceFill(['licencia_id' => $licencia->id])->save();
        $this->assertNull($sinTope->limiteEmpleadosPorLicencia());

        // Empresa con suscripción (la fábrica la crea suscrita): el tope de la licencia no aplica.
        $conSuscripcion = Empresa::factory()->create();
        $conTope = Licencia::create(['codigo' => 'ACH-EEEE-FFFF', 'max_usos' => 1, 'max_empleados' => 1]);
        $conSuscripcion->forceFill(['licencia_id' => $conTope->id])->save();
        $this->assertNull($conSuscripcion->limiteEmpleadosPorLicencia());
    }
}
