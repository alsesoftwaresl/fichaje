<?php

namespace Tests\Feature;

use App\Models\Ausencia;
use App\Models\Empresa;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpleadosEstadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_lista_de_empleados_muestra_vacaciones_baja_y_inactivo(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $vacaciones = User::factory()->for($empresa)->create(['name' => 'Persona Vacaciones']);
        $baja = User::factory()->for($empresa)->create(['name' => 'Persona Baja']);
        User::factory()->for($empresa)->inactivo()->create(['name' => 'Persona Desactivada']);

        Tenant::set($empresa->id);
        foreach ([[$vacaciones, 'vacaciones'], [$baja, 'baja_medica']] as [$u, $tipo]) {
            Ausencia::create([
                'user_id' => $u->id,
                'tipo' => $tipo,
                'fecha_inicio' => now()->subDay(),
                'fecha_fin' => now()->addDay(),
                'estado' => 'aprobada',
            ]);
        }
        Tenant::clear();

        $this->actingAs($admin)
            ->get(route('admin.empleados.index'))
            ->assertOk()
            ->assertSeeInOrder(['Persona Baja', 'Baja médica'])
            ->assertSeeInOrder(['Persona Vacaciones', 'Vacaciones'])
            ->assertSeeInOrder(['Persona Desactivada', 'Inactivo']);
    }
}
