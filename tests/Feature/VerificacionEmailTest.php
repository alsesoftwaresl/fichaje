<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerificacionEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_empresa_sin_verificar_es_redirigido_a_verificar_email(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->unverified()->create();

        $this->actingAs($admin)
            ->get(route('admin.facturacion.index'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($admin)
            ->get(route('fichajes.mis'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_empleado_sin_email_nunca_pasa_por_la_verificacion(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create(['email' => null, 'email_verified_at' => null]);

        $this->actingAs($empleado)
            ->get(route('fichajes.mis'))
            ->assertOk();
    }

    public function test_el_enlace_de_verificacion_firmado_marca_el_email_como_verificado(): void
    {
        Event::fake();

        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $admin->id, 'hash' => sha1($admin->email)]
        );

        $this->actingAs($admin)->get($url)->assertRedirect(route('dashboard'));

        $this->assertNotNull($admin->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    public function test_admin_verificado_accede_con_normalidad(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)
            ->get(route('admin.facturacion.index'))
            ->assertOk();
    }
}
