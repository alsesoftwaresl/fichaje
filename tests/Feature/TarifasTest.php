<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarifasTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_el_precio_mensual_con_empleados_extra(): void
    {
        $tarifa = new Tarifa([
            'precio_base_mensual' => 29,
            'empleados_incluidos' => 5,
            'precio_empleado_extra' => 1,
        ]);

        $this->assertSame(29.0, $tarifa->calcularPrecioMensual(3));
        $this->assertSame(29.0, $tarifa->calcularPrecioMensual(5));
        $this->assertSame(36.0, $tarifa->calcularPrecioMensual(12));
    }

    public function test_solo_super_admin_puede_ver_y_editar_tarifas(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)->get(route('super-admin.tarifas.edit'))->assertForbidden();
        $this->actingAs($admin)->patch(route('super-admin.tarifas.update'), [
            'precio_base_mensual' => 99,
            'empleados_incluidos' => 1,
            'precio_empleado_extra' => 5,
        ])->assertForbidden();
    }

    public function test_super_admin_puede_actualizar_las_tarifas(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.tarifas.update'), [
                'precio_base_mensual' => 49.90,
                'empleados_incluidos' => 10,
                'precio_empleado_extra' => 2.50,
            ])
            ->assertRedirect(route('super-admin.tarifas.edit'));

        $tarifa = Tarifa::actual();
        $this->assertEquals(49.90, $tarifa->precio_base_mensual);
        $this->assertSame(10, $tarifa->empleados_incluidos);
        $this->assertEquals(2.50, $tarifa->precio_empleado_extra);
        $this->assertSame($superAdmin->id, $tarifa->actualizado_por);
    }

    public function test_no_admite_precios_negativos(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.tarifas.update'), [
                'precio_base_mensual' => -10,
                'empleados_incluidos' => 5,
                'precio_empleado_extra' => 1,
            ])
            ->assertSessionHasErrors('precio_base_mensual');
    }
}
