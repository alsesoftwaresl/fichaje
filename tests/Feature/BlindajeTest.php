<?php

namespace Tests\Feature;

use App\Http\Controllers\KioskoController;
use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
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
    protected function avisoStripe(string $clave): TestResponse
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
        $this->assertStringNotContainsString('
=HYPERLINK', $csv);
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

    public function test_en_produccion_no_se_crea_el_super_admin_con_la_contrasena_de_desarrollo(): void
    {
        config(['plataforma.super_admin.email' => 'super@ejemplo.test', 'plataforma.super_admin.password' => 'password']);
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true]);

        $this->assertDatabaseMissing('users', ['email' => 'super@ejemplo.test']);
    }

    public function test_el_admin_puede_cambiar_el_enlace_del_kiosco_y_el_viejo_deja_de_valer(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $viejo = $empresa->kiosko_token;

        $this->actingAs($admin)->patch(route('admin.kiosko.enlace'))->assertRedirect();

        $nuevo = $empresa->fresh()->kiosko_token;
        $this->assertNotSame($viejo, $nuevo);
        $this->get(route('kiosko.show', $viejo))->assertNotFound();
        $this->get(route('kiosko.show', $nuevo))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['empresa_id' => $empresa->id, 'accion' => 'kiosko_enlace_cambiado']);
    }

    public function test_un_empleado_no_puede_cambiar_el_enlace_del_kiosco(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        $viejo = $empresa->kiosko_token;

        $this->actingAs($empleado)->patch(route('admin.kiosko.enlace'))->assertForbidden();
        $this->assertSame($viejo, $empresa->fresh()->kiosko_token);
    }

    public function test_cambiar_de_conexion_no_sirve_para_probar_pin_sin_fin(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        $pin = $empleado->generarNuevoPin();
        $incorrecto = $pin === '111111' ? '222222' : '111111';

        // Cada intento desde una IP distinta: el freno por IP no salta nunca.
        for ($i = 1; $i <= KioskoController::FALLOS_POR_HORA_EMPRESA; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.'.intdiv($i, 250).'.'.($i % 250)])
                ->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $incorrecto]);
        }

        // Ahora ni con el PIN bueno desde otra conexión, hasta que pase el rato.
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.50.1'])
            ->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin])
            ->assertSessionHasErrors(['pin' => 'Demasiados intentos. Espera unos minutos y vuelve a probar.']);
        $this->assertSame(0, Fichaje::where('user_id', $empleado->id)->count());
    }

    public function test_en_produccion_el_super_admin_necesita_la_verificacion_en_dos_pasos(): void
    {
        $super = User::factory()->create(['empresa_id' => null, 'rol' => 'super_admin']);
        $this->app['env'] = 'production';

        $this->actingAs($super)->get(route('super-admin.empresas.index'))->assertRedirect(route('profile.edit'));
        // Su perfil sí lo puede abrir, para activarla.
        $this->actingAs($super)->get(route('profile.edit'))->assertOk();

        $super->forceFill(['dos_pasos_secreto' => Totp::secreto(), 'dos_pasos_confirmada_en' => now()])->save();
        $this->actingAs($super->fresh())->get(route('super-admin.empresas.index'))->assertOk();
    }

    public function test_no_se_puede_probar_la_contrasena_actual_sin_limite(): void
    {
        $usuario = User::factory()->for(Empresa::factory())->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($usuario)->put(route('password.update'), [
                'current_password' => 'no-es-esta-'.$i, 'password' => 'Nueva1234', 'password_confirmation' => 'Nueva1234',
            ]);
        }

        $this->actingAs($usuario)->put(route('password.update'), [
            'current_password' => 'password', 'password' => 'Nueva1234', 'password_confirmation' => 'Nueva1234',
        ])->assertTooManyRequests();
    }
}
