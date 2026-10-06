<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenciaTest extends TestCase
{
    use RefreshDatabase;

    protected function adminDeEmpresa(): User
    {
        return User::factory()->adminEmpresa()->for(Empresa::factory()->sinSuscripcion()->create())->create();
    }

    public function test_super_admin_crea_un_codigo(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->post(route('super-admin.licencias.store'), [
            'nota' => 'Bar Pepe',
            'meses' => 6,
            'max_usos' => 1,
        ])->assertRedirect(route('super-admin.licencias.index'));

        $licencia = Licencia::firstOrFail();
        $this->assertMatchesRegularExpression('/^ACH-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $licencia->codigo);
        $this->assertSame(6, $licencia->meses);

        $this->actingAs($super)->get(route('super-admin.licencias.index'))->assertOk()->assertSee($licencia->codigo);
    }

    public function test_un_admin_de_empresa_no_puede_gestionar_codigos(): void
    {
        $this->actingAs($this->adminDeEmpresa())->get(route('super-admin.licencias.index'))->assertForbidden();
        $this->actingAs($this->adminDeEmpresa())->post(route('super-admin.licencias.store'), ['max_usos' => 1])->assertForbidden();
    }

    public function test_canjear_un_codigo_da_acceso_sin_suscripcion(): void
    {
        $admin = $this->adminDeEmpresa();
        $licencia = Licencia::create(['codigo' => 'ACH-AAAA-BBBB', 'meses' => 3, 'max_usos' => 1]);

        $this->actingAs($admin)->get(route('admin.panel.index'))->assertRedirect(route('admin.facturacion.index'));

        $this->actingAs($admin)
            ->post(route('admin.facturacion.licencia'), ['codigo' => ' ach-aaaa-bbbb '])
            ->assertRedirect(route('admin.facturacion.index'));

        $empresa = $admin->empresa->fresh();
        $this->assertTrue($empresa->tieneLicenciaActiva());
        $this->assertSame(now()->addMonths(3)->toDateString(), $empresa->licencia_hasta->toDateString());
        $this->assertSame(1, $licencia->fresh()->usos);

        $this->actingAs($admin)->get(route('admin.panel.index'))->assertOk();
    }

    public function test_un_codigo_agotado_no_se_puede_canjear_dos_veces(): void
    {
        Licencia::create(['codigo' => 'ACH-AAAA-BBBB', 'max_usos' => 1]);
        $primera = $this->adminDeEmpresa();
        $segunda = $this->adminDeEmpresa();

        $this->actingAs($primera)->post(route('admin.facturacion.licencia'), ['codigo' => 'ACH-AAAA-BBBB']);
        $this->actingAs($segunda)->post(route('admin.facturacion.licencia'), ['codigo' => 'ACH-AAAA-BBBB'])
            ->assertSessionHasErrors('codigo');

        $this->assertFalse($segunda->empresa->fresh()->tieneLicenciaActiva());
    }

    public function test_codigos_invalidos_desactivados_o_caducados_se_rechazan(): void
    {
        $admin = $this->adminDeEmpresa();
        Licencia::create(['codigo' => 'ACH-OFFF-OFFF', 'max_usos' => 1, 'activa' => false]);
        Licencia::create(['codigo' => 'ACH-OLDD-OLDD', 'max_usos' => 1, 'canjeable_hasta' => now()->subDay()]);

        foreach (['ACH-NOEX-ISTE', 'ACH-OFFF-OFFF', 'ACH-OLDD-OLDD'] as $codigo) {
            $this->actingAs($admin)->post(route('admin.facturacion.licencia'), ['codigo' => $codigo])
                ->assertSessionHasErrors('codigo');
        }

        $this->assertFalse($admin->empresa->fresh()->tieneLicenciaActiva());
    }

    public function test_una_licencia_caducada_vuelve_a_bloquear(): void
    {
        $admin = $this->adminDeEmpresa();
        $licencia = Licencia::create(['codigo' => 'ACH-AAAA-BBBB', 'meses' => 1, 'max_usos' => 1]);
        $admin->empresa->forceFill(['licencia_id' => $licencia->id, 'licencia_hasta' => now()->subDay()->toDateString()])->save();

        $this->actingAs($admin)->get(route('admin.panel.index'))->assertRedirect(route('admin.facturacion.index'));
    }

    public function test_el_super_admin_puede_retirar_la_licencia(): void
    {
        $admin = $this->adminDeEmpresa();
        $licencia = Licencia::create(['codigo' => 'ACH-AAAA-BBBB', 'max_usos' => 1]);
        $admin->empresa->forceFill(['licencia_id' => $licencia->id])->save();
        $this->assertTrue($admin->empresa->fresh()->tieneLicenciaActiva());

        $this->actingAs(User::factory()->superAdmin()->create())
            ->patch(route('super-admin.empresas.quitar-licencia', $admin->empresa))
            ->assertRedirect();

        $this->assertFalse($admin->empresa->fresh()->tieneLicenciaActiva());
    }
}
