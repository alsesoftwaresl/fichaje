<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_un_nombre_con_formula_se_exporta_como_texto(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        // El empleado puede cambiar su nombre en el perfil; aquí intenta colar una fórmula.
        $empleado = User::factory()->for($empresa)->create(['name' => '=HYPERLINK("https://malo.example","Pulsa")']);

        $this->actingAs($empleado)->post(route('fichajes.store'));
        $this->travel(5)->minutes();
        $this->actingAs($empleado)->post(route('fichajes.store'));

        // Excel: la celda es texto, no fórmula.
        $excel = $this->actingAs($admin)->get(route('admin.fichajes.exportar', ['formato' => 'excel']));
        $hoja = IOFactory::load($excel->baseResponse->getFile()->getPathname())->getActiveSheet();
        $this->assertSame($empleado->name, $hoja->getCell('A2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $hoja->getCell('A2')->getDataType());

        // CSV: lleva un apóstrofo delante para que Excel no la ejecute.
        $csv = $this->actingAs($admin)->get(route('admin.fichajes.exportar', ['formato' => 'csv']))->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString("
=HYPERLINK", $csv);
    }

    public function test_al_dar_de_baja_a_un_empleado_pierde_la_sesion_abierta(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();

        // Entra de verdad (sesión y cookie de «Recuérdame»), no con actingAs.
        $this->post('/login', ['login' => $empleado->email, 'password' => 'password', 'remember' => 'on']);
        $this->get(route('nominas.mis'))->assertOk();
        $tokenAntes = $empleado->fresh()->remember_token;

        // La empresa lo da de baja mientras sigue con la sesión abierta.
        $empleado->forceFill(['activo' => false])->save();
        $this->app['auth']->forgetGuards(); // cada petición real vuelve a leer el usuario

        $this->get(route('nominas.mis'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNotSame($tokenAntes, $empleado->fresh()->remember_token);
    }

    public function test_si_se_desactiva_la_empresa_sus_usuarios_salen(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->post('/login', ['login' => $admin->email, 'password' => 'password']);
        $empresa->update(['activa' => false]);
        $this->app['auth']->forgetGuards();

        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
