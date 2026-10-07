<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IvaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_iva_por_defecto_es_el_21_y_se_desglosa_del_precio_con_iva_incluido(): void
    {
        $tarifa = new Tarifa(['iva_porcentaje' => 21]);

        // 29 € con IVA incluido = 23,97 € de base + 5,03 € de IVA.
        $this->assertSame(23.97, $tarifa->baseImponible(29.0));
        $this->assertSame(5.03, $tarifa->ivaIncluido(29.0));
        $this->assertSame('21', $tarifa->ivaTexto());

        $tarifa->iva_porcentaje = 10.5;
        $this->assertSame('10,5', $tarifa->ivaTexto());
    }

    public function test_la_tarifa_nueva_trae_iva_del_21(): void
    {
        $this->assertEquals(21.00, Tarifa::actual()->iva_porcentaje);
    }

    public function test_el_super_admin_cambia_el_iva_y_se_valida_el_rango(): void
    {
        $super = User::factory()->superAdmin()->create();
        $base = ['precio_base_mensual' => 29, 'empleados_incluidos' => 5, 'precio_empleado_extra' => 1];

        $this->actingAs($super)->patch(route('super-admin.tarifas.update'), $base + ['iva_porcentaje' => 10])->assertRedirect();
        $this->assertEquals(10.00, Tarifa::actual()->iva_porcentaje);

        $this->actingAs($super)->patch(route('super-admin.tarifas.update'), $base + ['iva_porcentaje' => 150])
            ->assertSessionHasErrors('iva_porcentaje');
    }

    public function test_cashier_aplica_el_tipo_de_iva_de_stripe_a_las_suscripciones(): void
    {
        $empresa = Empresa::factory()->create();
        $this->assertSame([], $empresa->taxRates());

        Tarifa::actual()->update(['stripe_tax_rate_id' => 'txr_123']);

        $this->assertSame(['txr_123'], $empresa->taxRates());
    }

    public function test_no_se_deja_suscribir_si_falta_el_iva_en_stripe(): void
    {
        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        Tarifa::actual()->update([
            'stripe_price_base_id' => 'price_base',
            'stripe_price_extra_id' => 'price_extra',
            'iva_porcentaje' => 21,
            'stripe_tax_rate_id' => null,
        ]);

        $this->actingAs($admin)->post(route('admin.facturacion.suscribir'))
            ->assertRedirect(route('admin.facturacion.index'))
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'sincronizadas'));
    }

    public function test_la_web_y_facturacion_dicen_que_los_precios_llevan_iva(): void
    {
        $this->get('/')->assertOk()->assertSee('IVA incluido')->assertSee('de IVA (21 %)');

        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)->get(route('admin.facturacion.index'))
            ->assertOk()->assertSee('IVA incluido')->assertSee('de IVA (21 %)');

        $this->get('/llms.txt')->assertSee('IVA incluido');
    }
}
