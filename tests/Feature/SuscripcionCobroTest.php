<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Reglas para no regalar ni cobrar de más al suscribirse: una sola prueba gratis, nunca dos
 * suscripciones y el módulo de Nóminas solo si se paga.
 */
class SuscripcionCobroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Sin clave, cualquier llamada a Stripe falla al momento sin salir a internet.
        config(['cashier.secret' => null]);

        Tarifa::actual()->update([
            'stripe_price_base_id' => 'price_base',
            'stripe_price_extra_id' => 'price_extra',
            'iva_porcentaje' => 0,
        ]);
    }

    /** Empresa que fue cliente y canceló: su suscripción ya terminó. */
    private function exCliente(): Empresa
    {
        $empresa = Empresa::factory()->create();
        $empresa->subscriptions()->update(['stripe_status' => 'canceled', 'ends_at' => now()->subDay()]);

        return $empresa->fresh();
    }

    public function test_quien_ya_esta_suscrito_no_puede_volver_a_suscribirse(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        $this->actingAs($admin)->post(route('admin.facturacion.suscribir'))
            ->assertRedirect(route('admin.facturacion.index'))
            ->assertSessionHas('status', 'Tu empresa ya tiene una suscripción activa.');
    }

    public function test_si_ya_hay_un_pago_abierto_se_reenvia_al_mismo(): void
    {
        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        Cache::put('checkout-abierto:'.$empresa->id, 'https://checkout.stripe.com/c/pay/abierto', now()->addMinutes(30));

        $this->actingAs($admin)->post(route('admin.facturacion.suscribir'))
            ->assertRedirect('https://checkout.stripe.com/c/pay/abierto');
    }

    public function test_la_prueba_gratis_es_solo_la_primera_vez(): void
    {
        $nueva = Empresa::factory()->sinSuscripcion()->create();
        $this->assertTrue($nueva->puedeUsarPrueba());

        $exCliente = $this->exCliente();
        $this->assertFalse($exCliente->puedeUsarPrueba());

        $admin = User::factory()->adminEmpresa()->for($exCliente)->create();
        $this->actingAs($admin)->get(route('admin.facturacion.index'))
            ->assertOk()
            ->assertSee('Ya disfrutaste de la prueba gratis')
            ->assertSee('Suscribirme')
            ->assertDontSee('Empezar prueba gratis');

        $adminNueva = User::factory()->adminEmpresa()->for($nueva)->create();
        $this->actingAs($adminNueva)->get(route('admin.facturacion.index'))
            ->assertSee('Empezar prueba gratis de 15 días');
    }

    public function test_al_volver_a_suscribirse_el_modulo_de_nominas_no_queda_activado_gratis(): void
    {
        Tarifa::actual()->update(['nominas_modulo_activo' => true, 'nominas_precio_mensual' => 10]);
        $empresa = $this->exCliente();
        $empresa->forceFill(['modulo_nominas' => true])->save();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        // El pago no llega a abrirse (sin clave de Stripe), pero la marca ya se ha quitado.
        $this->actingAs($admin)->post(route('admin.facturacion.suscribir'))
            ->assertRedirect(route('admin.facturacion.index'));

        $this->assertFalse($empresa->fresh()->modulo_nominas);
    }
}
