<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_empleado_no_accede_a_admin_ni_super_admin(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->get(route('admin.fichajes.index'))->assertForbidden();
        $this->actingAs($empleado)->get(route('super-admin.empresas.index'))->assertForbidden();
    }

    public function test_admin_empresa_no_accede_a_super_admin(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)->get(route('super-admin.empresas.index'))->assertForbidden();
    }

    public function test_super_admin_no_accede_a_rutas_de_tenant(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get(route('fichajes.mis'))->assertForbidden();
    }

    public function test_usuario_no_autenticado_es_redirigido_a_login(): void
    {
        $this->get(route('fichajes.mis'))->assertRedirect(route('login'));
        $this->get(route('admin.fichajes.index'))->assertRedirect(route('login'));
        $this->get(route('super-admin.empresas.index'))->assertRedirect(route('login'));
    }
}
