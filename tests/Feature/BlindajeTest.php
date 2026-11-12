<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fallos de seguridad encontrados en la auditoría de noviembre de 2026.
 */
class BlindajeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Aviso de Stripe firmado con la clave indicada, como lo haría Stripe (o un atacante).
     */
    protected function avisoStripe(string $clave): \Illuminate\Testing\TestResponse
    {
        $cuerpo = json_encode(['id' => 'evt_prueba', 'type' => 'evento.de.prueba', 'data' => ['object' => []]]);
        $hora = time();
        $firma = hash_hmac('sha256', $hora.'.'.$cuerpo, $clave);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$hora},v1={$firma}",
            'CONTENT_TYPE' => 'application/json',
        ], $cuerpo);
    }

    public function test_sin_clave_del_webhook_no_se_acepta_ningun_aviso_de_stripe(): void
    {
        // Si se aceptara, cualquiera podría "firmar" con una clave vacía y activarse la suscripción.
        config(['cashier.webhook.secret' => null]);

        $this->avisoStripe('')->assertForbidden();
    }

    public function test_con_clave_solo_pasan_los_avisos_bien_firmados(): void
    {
        config(['cashier.webhook.secret' => 'whsec_prueba']);

        $this->avisoStripe('')->assertForbidden();
        $this->avisoStripe('otra_clave')->assertForbidden();
        $this->avisoStripe('whsec_prueba')->assertOk();
    }
}
