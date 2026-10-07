<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Tarifa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StripeModoTest extends TestCase
{
    use RefreshDatabase;

    public function test_detecta_el_modo_de_stripe_por_la_clave_secreta(): void
    {
        config(['cashier.secret' => 'sk_test_abc']);
        $this->assertSame('test', Tarifa::modoStripe());

        config(['cashier.secret' => 'sk_live_abc']);
        $this->assertSame('live', Tarifa::modoStripe());

        config(['cashier.secret' => null]);
        $this->assertSame('test', Tarifa::modoStripe());
    }

    public function test_al_cambiar_de_modo_se_olvidan_los_datos_de_prueba_de_stripe(): void
    {
        $tarifa = Tarifa::actual();
        $tarifa->update([
            'stripe_product_id' => 'prod_test',
            'stripe_price_base_id' => 'price_base_test',
            'stripe_price_extra_id' => 'price_extra_test',
            'stripe_tax_rate_id' => 'txr_test',
            'stripe_modo' => 'test',
        ]);

        // La fábrica crea la empresa con suscripción; se le añade también el cliente de Stripe.
        $empresa = Empresa::factory()->create();
        $empresa->forceFill(['stripe_id' => 'cus_test', 'pm_type' => 'visa', 'pm_last_four' => '4242'])->save();
        $this->assertNotNull($empresa->fresh()->stripe_id);
        $this->assertGreaterThan(0, DB::table('subscriptions')->count());

        $tarifa->olvidarDatosDeStripe();
        $tarifa->save();

        $tarifa = $tarifa->fresh();
        $this->assertNull($tarifa->stripe_product_id);
        $this->assertNull($tarifa->stripe_price_base_id);
        $this->assertNull($tarifa->stripe_price_extra_id);
        $this->assertNull($tarifa->stripe_tax_rate_id);
        $this->assertNull($empresa->fresh()->stripe_id);
        $this->assertSame(0, DB::table('subscriptions')->count());
        $this->assertSame(0, DB::table('subscription_items')->count());
    }

    public function test_los_datos_de_la_tarifa_y_el_precio_no_se_tocan_al_olvidar_stripe(): void
    {
        $tarifa = Tarifa::actual();
        $tarifa->update(['precio_base_mensual' => 29, 'stripe_price_base_id' => 'price_x']);

        $tarifa->olvidarDatosDeStripe();
        $tarifa->save();

        $this->assertEquals(29, $tarifa->fresh()->precio_base_mensual);
    }
}
