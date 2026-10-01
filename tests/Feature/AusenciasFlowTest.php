<?php

namespace Tests\Feature;

use App\Models\Ausencia;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AusenciasFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_empleado_solicita_y_admin_aprueba(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->post(route('ausencias.store'), [
            'tipo' => 'vacaciones',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-10',
            'motivo' => 'Viaje familiar',
        ])->assertRedirect(route('ausencias.mis'));

        $ausencia = Ausencia::first();
        $this->assertSame('pendiente', $ausencia->estado);
        $this->assertSame($empleado->id, $ausencia->user_id);

        // El admin la ve en su listado.
        $this->actingAs($admin)
            ->get(route('admin.ausencias.index'))
            ->assertOk()
            ->assertSee($empleado->name);

        $this->actingAs($admin)
            ->patch(route('admin.ausencias.aprobar', $ausencia))
            ->assertRedirect();

        $ausencia->refresh();
        $this->assertSame('aprobada', $ausencia->estado);
        $this->assertSame($admin->id, $ausencia->resuelto_por);
        $this->assertNotNull($ausencia->resuelto_en);

        $this->assertDatabaseHas('audit_logs', ['accion' => 'ausencia_solicitada']);
        $this->assertDatabaseHas('audit_logs', ['accion' => 'ausencia_aprobada']);

        // El empleado ve el estado actualizado.
        $this->actingAs($empleado)
            ->get(route('ausencias.mis'))
            ->assertOk()
            ->assertSee('Aprobada');
    }

    public function test_admin_rechaza_con_motivo(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->post(route('ausencias.store'), [
            'tipo' => 'otro',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-02',
        ]);

        $ausencia = Ausencia::first();

        $this->actingAs($admin)
            ->patch(route('admin.ausencias.rechazar', $ausencia), [
                'motivo_rechazo' => 'Fechas no disponibles por carga de trabajo.',
            ])
            ->assertRedirect();

        $ausencia->refresh();
        $this->assertSame('rechazada', $ausencia->estado);
        $this->assertSame('Fechas no disponibles por carga de trabajo.', $ausencia->motivo_rechazo);
        $this->assertDatabaseHas('audit_logs', ['accion' => 'ausencia_rechazada']);
    }

    public function test_fecha_fin_no_puede_ser_anterior_a_fecha_inicio(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->post(route('ausencias.store'), [
            'tipo' => 'vacaciones',
            'fecha_inicio' => '2026-11-10',
            'fecha_fin' => '2026-11-01',
        ])->assertSessionHasErrors('fecha_fin');

        $this->assertDatabaseCount('ausencias', 0);
    }

    public function test_empresa_a_no_ve_ausencias_de_empresa_b(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $adminA = User::factory()->adminEmpresa()->for($empresaA)->create();
        $empleadoB = User::factory()->for($empresaB)->create();

        $this->actingAs($empleadoB)->post(route('ausencias.store'), [
            'tipo' => 'vacaciones',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-05',
        ]);

        $ausenciaB = Ausencia::first();

        $response = $this->actingAs($adminA)->get(route('admin.ausencias.index'));
        $response->assertOk();
        $response->assertDontSee($empleadoB->name);

        // Intentar aprobar la ausencia de otra empresa por ID directo: 404.
        $this->actingAs($adminA)
            ->patch(route('admin.ausencias.aprobar', $ausenciaB))
            ->assertNotFound();
    }
}
