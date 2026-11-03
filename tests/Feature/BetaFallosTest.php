<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fallos encontrados en la prueba beta (recorrido completo como cliente nuevo), para que no
 * vuelvan a aparecer.
 */
class BetaFallosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_codigo_de_licencia_vale_en_minusculas_con_espacios_o_sin_guiones(): void
    {
        $this->assertSame('ACH-5GH2-P3R6', Licencia::normalizar('ach 5gh2 p3r6'));
        $this->assertSame('ACH-5GH2-P3R6', Licencia::normalizar('ACH5GH2P3R6'));
        $this->assertSame('ACH-5GH2-P3R6', Licencia::normalizar(' ach-5gh2-p3r6 '));

        $licencia = Licencia::create(['max_usos' => 1, 'codigo' => 'ACH-5GH2-P3R6']);
        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)->post(route('admin.facturacion.licencia'), ['codigo' => 'ach 5gh2 p3r6'])
            ->assertSessionHasNoErrors();

        $this->assertSame($licencia->id, $empresa->fresh()->licencia_id);
    }

    public function test_con_licencia_activa_no_se_ofrece_ni_se_deja_pagar(): void
    {
        Tarifa::actual()->update(['stripe_price_base_id' => 'price_base', 'stripe_price_extra_id' => 'price_extra', 'iva_porcentaje' => 0]);
        $licencia = Licencia::create(['max_usos' => 1, 'codigo' => 'ACH-AAAA-BBBB']);
        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $empresa->forceFill(['licencia_id' => $licencia->id, 'licencia_hasta' => null])->save();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)->get(route('admin.facturacion.index'))
            ->assertSee('no necesitas suscribirte')
            ->assertSee('Precio si te suscribes')
            ->assertDontSee('Empezar prueba gratis');

        $this->actingAs($admin)->post(route('admin.facturacion.suscribir'))
            ->assertSessionHas('status', 'Tu empresa ya usa Achrono con una licencia: no necesitas suscribirte.');
    }

    public function test_el_admin_puede_escribir_el_dni_con_puntos_y_guion(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        $this->actingAs($admin)->post(route('admin.empleados.store'), ['name' => 'Ana', 'dni_nie' => '12.345.678-z'])
            ->assertSessionHasNoErrors();

        $this->assertSame('12345678Z', User::conDni('12345678Z')->first()->dni_nie);
    }

    public function test_las_casillas_legales_no_se_desmarcan_si_falla_otro_campo(): void
    {
        $this->post(route('registro.store'), [
            'nombre' => 'Empresa', 'email_contacto' => 'e@example.com', 'admin_name' => 'A',
            'admin_email' => 'a@example.com', 'admin_password' => 'corta', 'admin_password_confirmation' => 'corta',
            'acepta_terminos' => '1', 'acepta_encargo' => '1',
        ])->assertSessionHasErrors('admin_password');

        $html = $this->get(route('registro.create'))->getContent();
        $this->assertMatchesRegularExpression('/name="acepta_terminos" value="1" checked/', $html);
        $this->assertMatchesRegularExpression('/name="acepta_encargo" value="1" checked/', $html);
    }

    public function test_la_tarea_programada_envia_los_correos_que_queden_en_cola(): void
    {
        $comandos = collect(app(Schedule::class)->events())->pluck('command')->implode("\n");

        $this->assertStringContainsString('queue:work --stop-when-empty', $comandos);
        $this->assertStringContainsString('contactos:purgar', $comandos);
    }
}
