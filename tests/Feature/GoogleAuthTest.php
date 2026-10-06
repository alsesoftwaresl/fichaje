<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function simularGoogle(string $email, string $nombre = 'Persona de Prueba'): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($nombre);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_un_admin_empresa_existente_entra_con_google_por_email(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create(['email' => 'admin@empresa-existente.test']);

        $this->simularGoogle('admin@empresa-existente.test');

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_un_email_nuevo_manda_a_completar_el_registro(): void
    {
        $this->simularGoogle('nuevo@empresa-nueva.test', 'Persona Nueva');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('registro.completar'));
        $this->assertSame(
            ['nombre' => 'Persona Nueva', 'email' => 'nuevo@empresa-nueva.test'],
            session('registro_google')
        );
    }

    public function test_un_email_que_ya_es_de_otra_cuenta_no_intenta_crear_empresa(): void
    {
        User::factory()->create(['rol' => 'super_admin', 'empresa_id' => null, 'email' => 'super@achrono.test']);

        $this->simularGoogle('super@achrono.test');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
        $this->assertNull(session('registro_google'));
    }

    public function test_completar_registro_sin_haber_pasado_por_google_redirige_al_registro_normal(): void
    {
        $this->get(route('registro.completar'))->assertRedirect(route('registro.create'));
    }

    public function test_completar_registro_crea_la_empresa_ya_verificada_y_sin_pedir_contrasena(): void
    {
        $this->simularGoogle('nuevo@empresa-nueva.test', 'Persona Nueva');
        $this->get(route('auth.google.callback'));

        $response = $this->post(route('registro.completar.store'), [
            'nombre' => 'Empresa Google SL',
            'email_contacto' => 'contacto@empresa-google.test',
            'acepta_terminos' => '1',
            'acepta_encargo' => '1',
        ]);

        $response->assertRedirect(route('admin.facturacion.index'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@empresa-nueva.test',
            'name' => 'Persona Nueva',
            'rol' => 'admin_empresa',
        ]);

        $admin = User::where('email', 'nuevo@empresa-nueva.test')->firstOrFail();
        $this->assertNotNull($admin->email_verified_at);

        $this->assertNull(session('registro_google'));
    }

    public function test_el_email_de_contacto_de_empresa_viene_prerrellenado_con_el_de_google(): void
    {
        $this->simularGoogle('nuevo@empresa-nueva.test', 'Persona Nueva');
        $this->get(route('auth.google.callback'));

        $this->get(route('registro.completar'))
            ->assertSee('nuevo@empresa-nueva.test');
    }
}
