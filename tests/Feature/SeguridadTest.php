<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Cabeceras de seguridad y cambios de correo en el perfil.
 */
class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_respuestas_llevan_las_cabeceras_de_seguridad(): void
    {
        foreach (['/', '/login', '/no-existe-esta-pagina'] as $url) {
            $respuesta = $this->get($url);

            $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');
            $respuesta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
            $respuesta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
            $this->assertStringContainsString("frame-ancestors 'self'", $respuesta->headers->get('Content-Security-Policy'));
            $this->assertStringContainsString('https://checkout.stripe.com', $respuesta->headers->get('Content-Security-Policy'));
        }
    }

    public function test_hsts_solo_en_produccion_con_https(): void
    {
        config(['app.url' => 'https://achrono.es']);
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

        $this->app['env'] = 'production';
        $this->get('/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_un_empleado_sin_correo_puede_guardar_su_perfil(): void
    {
        $empleado = User::factory()->for(Empresa::factory())->create(['email' => null, 'dni_nie' => '12345678Z']);

        $this->actingAs($empleado)
            ->patch(route('profile.update'), ['name' => 'Nombre Nuevo', 'email' => ''])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nombre Nuevo', $empleado->fresh()->name);
        $this->assertNull($empleado->fresh()->email);
    }

    public function test_el_admin_que_cambia_de_correo_tiene_que_confirmarlo(): void
    {
        Notification::fake();
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        $this->actingAs($admin)
            ->patch(route('profile.update'), ['name' => $admin->name, 'email' => 'nuevo@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertNull($admin->fresh()->email_verified_at);
        Notification::assertSentTo($admin, VerifyEmail::class);

        // Hasta que lo confirme, el panel le lleva a la verificación.
        $this->actingAs($admin->fresh())->get(route('admin.panel.index'))->assertRedirect(route('verification.notice'));
    }

    public function test_guardar_el_perfil_sin_cambiar_el_correo_no_pide_verificar(): void
    {
        Notification::fake();
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        $this->actingAs($admin)->patch(route('profile.update'), ['name' => 'Otro nombre', 'email' => $admin->email]);

        $this->assertNotNull($admin->fresh()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_el_admin_no_puede_darse_de_baja_a_si_mismo(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        $this->actingAs($admin)->patch(route('admin.empleados.desactivar', $admin))
            ->assertSessionHas('status', 'No puedes darte de baja a ti mismo.');

        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_no_se_aceptan_contrasenas_sin_letras_o_sin_numeros(): void
    {
        $usuario = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        foreach (['aaaaaaaa', '12345678', 'abc1'] as $debil) {
            $this->actingAs($usuario)
                ->put(route('password.update'), ['current_password' => 'password', 'password' => $debil, 'password_confirmation' => $debil])
                ->assertSessionHasErrorsIn('updatePassword', 'password');
        }

        $this->actingAs($usuario)
            ->put(route('password.update'), ['current_password' => 'password', 'password' => 'Clave2026', 'password_confirmation' => 'Clave2026'])
            ->assertSessionHasNoErrors();
    }
}
