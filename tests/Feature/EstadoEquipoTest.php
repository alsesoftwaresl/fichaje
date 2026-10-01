<?php

namespace Tests\Feature;

use App\Models\Ausencia;
use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Services\EstadoEquipoCalculador;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadoEquipoTest extends TestCase
{
    use RefreshDatabase;

    public function test_detecta_trabajando_vacaciones_baja_y_fuera(): void
    {
        $empresa = Empresa::factory()->create();

        $trabajando = User::factory()->for($empresa)->create(['name' => 'Trabajando']);
        $vacaciones = User::factory()->for($empresa)->create(['name' => 'De vacaciones']);
        $baja = User::factory()->for($empresa)->create(['name' => 'De baja']);
        $fuera = User::factory()->for($empresa)->create(['name' => 'Fuera']);

        Tenant::set($empresa->id);

        Fichaje::create(['user_id' => $trabajando->id, 'tipo' => 'entrada', 'fecha_hora' => now(), 'origen' => 'web']);

        Ausencia::create([
            'user_id' => $vacaciones->id,
            'tipo' => 'vacaciones',
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDay(),
            'estado' => 'aprobada',
        ]);

        Ausencia::create([
            'user_id' => $baja->id,
            'tipo' => 'baja_medica',
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDay(),
            'estado' => 'aprobada',
        ]);

        // Ausencia pendiente: no debe contar como "de vacaciones" todavía.
        Ausencia::create([
            'user_id' => $fuera->id,
            'tipo' => 'vacaciones',
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDay(),
            'estado' => 'pendiente',
        ]);

        Tenant::clear();

        $estados = EstadoEquipoCalculador::delDia($empresa->id)->keyBy(fn ($item) => $item['empleado']->id);

        $this->assertSame('trabajando', $estados[$trabajando->id]['estado']);
        $this->assertSame('vacaciones', $estados[$vacaciones->id]['estado']);
        $this->assertSame('baja', $estados[$baja->id]['estado']);
        $this->assertSame('fuera', $estados[$fuera->id]['estado']);
    }
}
