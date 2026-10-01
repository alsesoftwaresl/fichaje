<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function crearFichajeParaEmpresa(Empresa $empresa, User $empleado): Fichaje
    {
        Tenant::set($empresa->id);

        $fichaje = Fichaje::create([
            'user_id' => $empleado->id,
            'tipo' => 'entrada',
            'fecha_hora' => now(),
            'origen' => 'web',
        ]);

        Tenant::clear();

        return $fichaje;
    }

    public function test_admin_de_empresa_a_no_ve_fichajes_de_empresa_b(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();

        $adminA = User::factory()->adminEmpresa()->for($empresaA)->create();
        $empleadoA = User::factory()->for($empresaA)->create();
        $empleadoB = User::factory()->for($empresaB)->create();

        $fichajeA = $this->crearFichajeParaEmpresa($empresaA, $empleadoA);
        $fichajeB = $this->crearFichajeParaEmpresa($empresaB, $empleadoB);

        $response = $this->actingAs($adminA)->get(route('admin.fichajes.index'));

        $response->assertOk();
        $response->assertSee($empleadoA->name);
        $response->assertDontSee($empleadoB->name);

        // Acceder a un fichaje de otra empresa por ID directo devuelve 404, no 403.
        $this->actingAs($adminA)
            ->post(route('admin.fichajes.correcciones.store', $fichajeB), [
                'fecha_hora_corregida' => now()->toDateTimeString(),
                'motivo' => 'intento de acceso cruzado',
            ])
            ->assertNotFound();
    }

    public function test_admin_de_empresa_a_no_puede_desactivar_empleado_de_empresa_b(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();

        $adminA = User::factory()->adminEmpresa()->for($empresaA)->create();
        $empleadoB = User::factory()->for($empresaB)->create();

        $this->actingAs($adminA)
            ->patch(route('admin.empleados.desactivar', $empleadoB))
            ->assertNotFound();

        $this->assertTrue($empleadoB->fresh()->activo);
    }
}
