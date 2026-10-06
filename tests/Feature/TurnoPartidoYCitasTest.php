<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Services\IncidenciasCalculador;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TurnoPartidoYCitasTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function ahoraA(int $hora, int $minuto = 0): void
    {
        Carbon::setTestNow(Carbon::now()->setTime($hora, $minuto));
    }

    protected function empleado(Empresa $empresa, array $tramos): User
    {
        return User::factory()->for($empresa)->create([
            'created_at' => now()->subMonth(),
            'hora_entrada_esperada' => $tramos[0]['entrada'].':00',
            'hora_salida_esperada' => end($tramos)['salida'].':00',
            'horario_tramos' => count($tramos) > 1 ? $tramos : null,
            'dias_laborables' => [1, 2, 3, 4, 5, 6, 7],
        ]);
    }

    protected function fichar(Empresa $empresa, User $u, string $tipo, int $h, int $m = 0): void
    {
        Tenant::set($empresa->id);
        Fichaje::create(['user_id' => $u->id, 'tipo' => $tipo, 'fecha_hora' => now()->setTime($h, $m), 'origen' => 'web']);
        Tenant::clear();
    }

    protected function cita(Empresa $empresa, User $u, string $desde, string $hasta, array $extra = []): Cita
    {
        Tenant::set($empresa->id);
        $cita = Cita::create($extra + [
            'user_id' => $u->id, 'motivo' => 'Médico', 'fecha' => now()->toDateString(),
            'hora_inicio' => $desde, 'hora_fin' => $hasta,
        ]);
        Tenant::clear();

        return $cita;
    }

    protected function tipos(Empresa $empresa): array
    {
        return IncidenciasCalculador::delDia($empresa->id)->pluck('tipo')->all();
    }

    public function test_se_guarda_un_turno_partido_con_dos_tramos(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)->post(route('admin.empleados.store'), [
            'name' => 'Turno Partido',
            'dni_nie' => '11111111H',
            'tramos' => [
                ['entrada' => '09:00', 'salida' => '14:00'],
                ['entrada' => '16:00', 'salida' => '19:00'],
                ['entrada' => '', 'salida' => ''], // fila vacía: se ignora
            ],
            'dias_laborables' => ['1', '2', '3', '4', '5'],
        ])->assertRedirect(route('admin.empleados.index'));

        $e = User::where('dni_nie', '11111111H')->firstOrFail();

        $this->assertSame('09:00:00', $e->hora_entrada_esperada);
        $this->assertSame('19:00:00', $e->hora_salida_esperada);
        $this->assertSame([
            ['entrada' => '09:00', 'salida' => '14:00'],
            ['entrada' => '16:00', 'salida' => '19:00'],
        ], $e->tramos());
    }

    public function test_los_tramos_solapados_o_incompletos_se_rechazan(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)->post(route('admin.empleados.store'), [
            'name' => 'Solapado', 'dni_nie' => '22222222J',
            'tramos' => [['entrada' => '09:00', 'salida' => '14:00'], ['entrada' => '13:00', 'salida' => '18:00']],
        ])->assertSessionHasErrors('tramos.1');

        $this->actingAs($admin)->post(route('admin.empleados.store'), [
            'name' => 'Incompleto', 'dni_nie' => '33333333P',
            'tramos' => [['entrada' => '09:00', 'salida' => '']],
        ])->assertSessionHasErrors('tramos.0');

        $this->assertDatabaseCount('users', 1); // solo el admin
    }

    public function test_turno_partido_sin_incidencias_si_ficha_bien(): void
    {
        $empresa = Empresa::factory()->create();
        $u = $this->empleado($empresa, [['entrada' => '09:00', 'salida' => '14:00'], ['entrada' => '16:00', 'salida' => '19:00']]);

        foreach ([['entrada', 9], ['salida', 14], ['entrada', 16], ['salida', 19]] as [$t, $h]) {
            $this->fichar($empresa, $u, $t, $h);
        }
        $this->ahoraA(20);

        // 8 h trabajadas de 8 esperadas: no hay "horas de más" falsas por la pausa.
        $this->assertSame([], $this->tipos($empresa));
    }

    public function test_turno_partido_detecta_la_vuelta_tarde_de_la_pausa(): void
    {
        $empresa = Empresa::factory()->create();
        $u = $this->empleado($empresa, [['entrada' => '09:00', 'salida' => '14:00'], ['entrada' => '16:00', 'salida' => '19:00']]);

        foreach ([['entrada', 9], ['salida', 14]] as [$t, $h]) {
            $this->fichar($empresa, $u, $t, $h);
        }
        $this->fichar($empresa, $u, 'entrada', 16, 30);
        $this->fichar($empresa, $u, 'salida', 19);
        $this->ahoraA(20);

        $retraso = IncidenciasCalculador::delDia($empresa->id)->firstWhere('tipo', 'retraso');

        $this->assertNotNull($retraso);
        $this->assertSame(30, $retraso['minutos']);
        $this->assertStringContainsString('Volvió a las 16:30', $retraso['detalle']);
    }

    public function test_turno_partido_avisa_si_no_ficha_la_vuelta_pero_no_si_no_salio_a_la_pausa(): void
    {
        $empresa = Empresa::factory()->create();
        $sale = $this->empleado($empresa, [['entrada' => '09:00', 'salida' => '14:00'], ['entrada' => '16:00', 'salida' => '19:00']]);
        $sigueDentro = $this->empleado($empresa, [['entrada' => '09:00', 'salida' => '14:00'], ['entrada' => '16:00', 'salida' => '19:00']]);

        $this->fichar($empresa, $sale, 'entrada', 9);
        $this->fichar($empresa, $sale, 'salida', 14);
        $this->fichar($empresa, $sigueDentro, 'entrada', 9);
        $this->ahoraA(17);

        $inc = IncidenciasCalculador::delDia($empresa->id);

        $this->assertTrue($inc->contains(fn ($i) => $i['tipo'] === 'sin_fichar' && $i['empleado']->id === $sale->id));
        $this->assertFalse($inc->contains(fn ($i) => $i['empleado']->id === $sigueDentro->id));
    }

    public function test_una_cita_al_inicio_mueve_la_entrada_esperada_a_la_vuelta(): void
    {
        $empresa = Empresa::factory()->create();
        $u = $this->empleado($empresa, [['entrada' => '09:00', 'salida' => '17:00']]);
        $this->cita($empresa, $u, '09:00', '11:00');

        $this->ahoraA(10); // aún en la cita: nada que avisar
        $this->assertSame([], $this->tipos($empresa));

        $this->ahoraA(12); // ya debería haber vuelto y no ha fichado
        $inc = IncidenciasCalculador::delDia($empresa->id)->firstWhere('tipo', 'sin_fichar');
        $this->assertStringContainsString('11:00', $inc['detalle']);

        $this->fichar($empresa, $u, 'entrada', 11, 5);
        $this->assertSame([], $this->tipos($empresa)); // dentro del margen
    }

    public function test_si_vuelve_tarde_de_la_cita_el_retraso_cuenta_desde_la_hora_de_vuelta(): void
    {
        $empresa = Empresa::factory()->create();
        $u = $this->empleado($empresa, [['entrada' => '09:00', 'salida' => '17:00']]);
        $this->cita($empresa, $u, '09:00', '11:00');
        $this->fichar($empresa, $u, 'entrada', 11, 30);
        $this->ahoraA(12);

        $retraso = IncidenciasCalculador::delDia($empresa->id)->firstWhere('tipo', 'retraso');

        $this->assertSame(30, $retraso['minutos']);
    }

    public function test_una_cita_anulada_no_justifica_nada(): void
    {
        $empresa = Empresa::factory()->create();
        $u = $this->empleado($empresa, [['entrada' => '09:00', 'salida' => '17:00']]);
        $this->cita($empresa, $u, '09:00', '11:00', ['anulada_en' => now()]);
        $this->fichar($empresa, $u, 'entrada', 11, 5);
        $this->ahoraA(12);

        $this->assertContains('retraso', $this->tipos($empresa));
    }

    public function test_el_empleado_avisa_de_una_cita_y_el_admin_la_ve_en_el_panel(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create(['name' => 'Persona Con Cita']);

        $this->actingAs($empleado)->post(route('citas.store'), [
            'fecha' => now()->toDateString(),
            'hora_inicio' => '11:00',
            'hora_fin' => '12:30',
            'motivo' => 'Revisión en el centro de salud',
        ])->assertRedirect(route('ausencias.mis'));

        $this->assertDatabaseHas('citas', ['user_id' => $empleado->id, 'motivo' => 'Revisión en el centro de salud', 'anulada_en' => null]);

        $this->actingAs($admin)->get(route('admin.panel.index'))
            ->assertOk()
            ->assertSee('Salidas avisadas para hoy')
            ->assertSee('Persona Con Cita')
            ->assertSee('11:00–12:30')
            ->assertSee('Revisión en el centro de salud');
    }

    public function test_la_cita_valida_las_horas_y_no_admite_fechas_pasadas(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->post(route('citas.store'), [
            'fecha' => now()->subDay()->toDateString(),
            'hora_inicio' => '12:00', 'hora_fin' => '11:00',
        ])->assertSessionHasErrors(['fecha', 'hora_fin', 'motivo']);

        $this->assertDatabaseCount('citas', 0);
    }

    public function test_solo_el_dueno_o_un_admin_pueden_anular_una_cita(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $dueno = User::factory()->for($empresa)->create();
        $otro = User::factory()->for($empresa)->create();
        $cita = $this->cita($empresa, $dueno, '11:00', '12:00');

        $this->actingAs($otro)->patch(route('citas.anular', $cita))->assertForbidden();
        $this->assertNull($cita->fresh()->anulada_en);

        $this->actingAs($dueno)->patch(route('citas.anular', $cita))->assertRedirect();
        $this->assertNotNull($cita->fresh()->anulada_en);

        $otraCita = $this->cita($empresa, $dueno, '15:00', '16:00');
        $this->actingAs($admin)->patch(route('citas.anular', $otraCita))->assertRedirect();
        $this->assertNotNull($otraCita->fresh()->anulada_en);
    }

    public function test_una_cita_de_otra_empresa_no_se_puede_anular(): void
    {
        $a = Empresa::factory()->create();
        $b = Empresa::factory()->create();
        $adminA = User::factory()->adminEmpresa()->for($a)->create();
        $empleadoB = User::factory()->for($b)->create();
        $citaB = $this->cita($b, $empleadoB, '11:00', '12:00');

        $this->actingAs($adminA)->patch(route('citas.anular', $citaB))->assertNotFound();
    }

    public function test_el_estado_del_equipo_marca_en_cita_mientras_dura(): void
    {
        $empresa = Empresa::factory()->create();
        $u = $this->empleado($empresa, [['entrada' => '09:00', 'salida' => '17:00']]);
        $this->cita($empresa, $u, '11:00', '12:30');
        $this->fichar($empresa, $u, 'entrada', 9);

        $this->ahoraA(11, 30);
        $estado = \App\Services\EstadoEquipoCalculador::delDia($empresa->id)->firstWhere(fn ($i) => $i['empleado']->id === $u->id);
        $this->assertSame('cita', $estado['estado']);
        $this->assertStringContainsString('12:30', $estado['detalle']);
        $this->assertStringContainsString('Médico', $estado['detalle']);

        $this->ahoraA(13);
        $estado = \App\Services\EstadoEquipoCalculador::delDia($empresa->id)->firstWhere(fn ($i) => $i['empleado']->id === $u->id);
        $this->assertSame('trabajando', $estado['estado']);
    }

    public function test_avisa_si_no_ha_vuelto_de_la_cita_a_la_hora_prevista(): void
    {
        $empresa = Empresa::factory()->create();
        $u = $this->empleado($empresa, [['entrada' => '09:00', 'salida' => '17:00']]);
        $this->cita($empresa, $u, '11:00', '12:00');
        $this->fichar($empresa, $u, 'entrada', 9);
        $this->fichar($empresa, $u, 'salida', 11);

        $this->ahoraA(11, 30); // aún dentro de la cita
        $this->assertNotContains('sin_volver', $this->tipos($empresa));

        $this->ahoraA(12, 30); // debía volver a las 12:00
        $this->assertContains('sin_volver', $this->tipos($empresa));

        $this->fichar($empresa, $u, 'entrada', 12, 5); // ya ha vuelto
        $this->assertNotContains('sin_volver', $this->tipos($empresa));
    }
}
