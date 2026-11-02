<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Empresa;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

/**
 * Verificación en dos pasos (TOTP) y cierre de sesiones al cambiar la contraseña.
 */
class DosPasosTest extends TestCase
{
    use RefreshDatabase;

    private function adminConDosPasos(): User
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory())->create();
        $admin->forceFill([
            'dos_pasos_secreto' => Totp::secreto(),
            'dos_pasos_confirmada_en' => now(),
            'dos_pasos_recuperacion' => ['AAAA-BBBB', 'CCCC-DDDD'],
        ])->save();

        return $admin;
    }

    public function test_cumple_los_vectores_de_prueba_del_rfc_6238(): void
    {
        $secreto = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // "12345678901234567890"

        $this->assertSame('287082', Totp::codigo($secreto, intdiv(59, 30)));
        $this->assertSame('081804', Totp::codigo($secreto, intdiv(1111111109, 30)));
        $this->assertSame('353130', Totp::codigo($secreto, intdiv(20000000000, 30)));
    }

    public function test_activar_desde_el_perfil_pide_un_primer_codigo_y_da_codigos_de_recuperacion(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        $this->actingAs($admin)->post(route('dos-pasos.activar'));
        $admin->refresh();
        $this->assertNotNull($admin->dos_pasos_secreto);
        $this->assertFalse($admin->tieneDosPasos());

        $this->actingAs($admin)->get(route('profile.edit'))->assertSee('Clave para escribir a mano');

        $this->actingAs($admin)->post(route('dos-pasos.confirmar'), ['codigo' => '000000'])
            ->assertSessionHasErrorsIn('dosPasos', 'codigo');

        $this->actingAs($admin)->post(route('dos-pasos.confirmar'), ['codigo' => Totp::codigo($admin->dos_pasos_secreto)])
            ->assertSessionHas('codigos_recuperacion', fn ($c) => count($c) === 8);

        $this->assertTrue($admin->fresh()->tieneDosPasos());
        $this->assertSame(1, AuditLog::where('accion', 'dos_pasos_activada')->count());
    }

    public function test_el_secreto_se_guarda_cifrado(): void
    {
        $admin = $this->adminConDosPasos();

        $guardado = DB::table('users')->where('id', $admin->id)->value('dos_pasos_secreto');

        $this->assertNotSame($admin->dos_pasos_secreto, $guardado);
        $this->assertStringNotContainsString('AAAA-BBBB', DB::table('users')->where('id', $admin->id)->value('dos_pasos_recuperacion'));
    }

    public function test_con_dos_pasos_la_contrasena_sola_no_basta(): void
    {
        $admin = $this->adminConDosPasos();

        $this->post(route('login'), ['login' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dos-pasos.reto'));
        $this->assertGuest();

        $this->get(route('admin.panel.index'))->assertRedirect(route('login'));

        $this->post(route('dos-pasos.verificar'), ['codigo' => '123456'])->assertSessionHasErrors('codigo');
        $this->assertGuest();

        // Vuelve a la página que intentó abrir antes de entrar.
        $this->post(route('dos-pasos.verificar'), ['codigo' => Totp::codigo($admin->dos_pasos_secreto)])
            ->assertRedirect(route('admin.panel.index'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_el_mismo_codigo_no_vale_dos_veces(): void
    {
        $admin = $this->adminConDosPasos();
        $codigo = Totp::codigo($admin->dos_pasos_secreto);

        $this->post(route('login'), ['login' => $admin->email, 'password' => 'password']);
        $this->post(route('dos-pasos.verificar'), ['codigo' => $codigo]);
        $this->assertAuthenticated();

        $this->post(route('logout'));
        $this->post(route('login'), ['login' => $admin->email, 'password' => 'password']);
        $this->post(route('dos-pasos.verificar'), ['codigo' => $codigo])->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_un_codigo_de_recuperacion_sirve_una_sola_vez(): void
    {
        $admin = $this->adminConDosPasos();

        $this->post(route('login'), ['login' => $admin->email, 'password' => 'password']);
        $this->post(route('dos-pasos.verificar'), ['codigo' => 'aaaa bbbb'])->assertRedirect(route('dashboard'));
        $this->assertSame(['CCCC-DDDD'], $admin->fresh()->dos_pasos_recuperacion);
        $this->assertSame(1, AuditLog::where('accion', 'codigo_recuperacion_usado')->count());

        $this->post(route('logout'));
        $this->post(route('login'), ['login' => $admin->email, 'password' => 'password']);
        $this->post(route('dos-pasos.verificar'), ['codigo' => 'AAAA-BBBB'])->assertSessionHasErrors('codigo');
    }

    public function test_el_paso_pendiente_caduca(): void
    {
        $admin = $this->adminConDosPasos();

        $this->post(route('login'), ['login' => $admin->email, 'password' => 'password']);
        $this->travel(6)->minutes();

        $this->post(route('dos-pasos.verificar'), ['codigo' => Totp::codigo($admin->dos_pasos_secreto)])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_tras_cinco_fallos_se_frena(): void
    {
        $admin = $this->adminConDosPasos();
        $this->post(route('login'), ['login' => $admin->email, 'password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('dos-pasos.verificar'), ['codigo' => '000000']);
        }

        $this->post(route('dos-pasos.verificar'), ['codigo' => Totp::codigo($admin->dos_pasos_secreto)])
            ->assertSessionHasErrors(['codigo' => 'Demasiados intentos. Espera un minuto y vuelve a probar.']);
        $this->assertGuest();
    }

    public function test_desactivar_pide_la_contrasena(): void
    {
        $admin = $this->adminConDosPasos();

        $this->actingAs($admin)->post(route('dos-pasos.desactivar'), ['password' => 'mala'])
            ->assertSessionHasErrorsIn('dosPasos', 'password');
        $this->assertTrue($admin->fresh()->tieneDosPasos());

        $this->actingAs($admin)->post(route('dos-pasos.desactivar'), ['password' => 'password']);
        $this->assertFalse($admin->fresh()->tieneDosPasos());
    }

    public function test_cambiar_la_contrasena_cierra_las_demas_sesiones(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        // Sesión abierta en otro dispositivo: se guarda la huella de la contraseña de entonces.
        $this->actingAs($admin)->get(route('admin.panel.index'))->assertOk();

        // Cambia la contraseña desde otro sitio.
        $admin->forceFill(['password' => 'OtraClave2026'])->save();

        $this->actingAs($admin->fresh())->get(route('admin.panel.index'))->assertRedirect(route('login'));
    }

    public function test_google_no_deja_entrar_a_un_admin_de_baja(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory())->create(['activo' => false]);

        $usuarioGoogle = \Mockery::mock(\Laravel\Socialite\Two\User::class);
        $usuarioGoogle->shouldReceive('getEmail')->andReturn($admin->email);
        Socialite::shouldReceive('driver->user')->andReturn($usuarioGoogle);

        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
