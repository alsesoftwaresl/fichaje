<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Services\IncidenciasCalculador;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Turnos que acaban pasada la medianoche (hostelería, vigilancia...): horario de 22:00 a
 * 06:00 y jornadas que empiezan un día y acaban el siguiente.
 */
class TurnoNocheTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $empleado;

    /** Día en que empieza el turno de noche (ayer, para poder mirar el día siguiente). */
    private Carbon $dia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dia = now()->subDay()->startOfDay();
        $this->empresa = Empresa::factory()->create();
        $this->empleado = User::factory()->for($this->empresa)->create([
            'hora_entrada_esperada' => '22:00:00',
            'hora_salida_esperada' => '06:00:00',
            'dias_laborables' => [1, 2, 3, 4, 5, 6, 7],
            'created_at' => now()->subMonth(),
        ]);
    }

    private function fichar(string $tipo, Carbon $cuando): void
    {
        Tenant::set($this->empresa->id);
        Fichaje::create(['user_id' => $this->empleado->id, 'tipo' => $tipo, 'fecha_hora' => $cuando, 'origen' => 'kiosco']);
    }

    private function incidenciasDelDia(): array
    {
        return IncidenciasCalculador::delDia($this->empresa->id, $this->dia)
            ->map(fn ($i) => $i['tipo'].': '.$i['detalle'])
            ->all();
    }

    public function test_el_admin_puede_poner_un_horario_que_acaba_al_dia_siguiente(): void
    {
        $admin = User::factory()->adminEmpresa()->for($this->empresa)->create();

        $this->actingAs($admin)->post(route('admin.empleados.store'), [
            'name' => 'Vigilante',
            'dni_nie' => '11223344B',
            'tramos' => [['entrada' => '22:00', 'salida' => '06:00']],
            'dias_laborables' => ['5', '6'],
        ])->assertSessionHasNoErrors();

        $this->assertSame([['entrada' => '22:00', 'salida' => '06:00']], User::where('dni_nie', '11223344B')->first()->tramos());
    }

    public function test_solo_el_ultimo_tramo_puede_cruzar_la_medianoche(): void
    {
        $admin = User::factory()->adminEmpresa()->for($this->empresa)->create();

        $this->actingAs($admin)->post(route('admin.empleados.store'), [
            'name' => 'Partido raro',
            'dni_nie' => '11223344B',
            'tramos' => [['entrada' => '20:00', 'salida' => '02:00'], ['entrada' => '03:00', 'salida' => '05:00']],
            'dias_laborables' => ['5'],
        ])->assertSessionHasErrors(['tramos.0' => 'La salida tiene que ser posterior a la entrada. Solo el último tramo puede acabar pasada la medianoche.']);

        $this->actingAs($admin)->post(route('admin.empleados.store'), [
            'name' => 'Cero horas',
            'dni_nie' => '11223344B',
            'tramos' => [['entrada' => '09:00', 'salida' => '09:00']],
            'dias_laborables' => ['5'],
        ])->assertSessionHasErrors(['tramos.0' => 'La salida tiene que ser distinta de la entrada.']);
    }

    public function test_un_turno_de_noche_completo_no_da_incidencias(): void
    {
        $this->fichar('entrada', $this->dia->copy()->setTime(22, 0));
        $this->fichar('salida', $this->dia->copy()->addDay()->setTime(6, 0));

        $this->travelTo($this->dia->copy()->addDay()->setTime(12, 0));

        $this->assertSame([], $this->incidenciasDelDia());
    }

    public function test_las_horas_de_un_turno_de_noche_se_cuentan_enteras(): void
    {
        $this->fichar('entrada', $this->dia->copy()->setTime(22, 0));
        $this->fichar('salida', $this->dia->copy()->addDay()->setTime(9, 0));

        $this->travelTo($this->dia->copy()->addDay()->setTime(12, 0));

        $this->assertSame(['horas_de_mas: 11.00 h trabajadas (esperadas 8.00 h)'], $this->incidenciasDelDia());
    }

    public function test_un_retraso_en_el_turno_de_noche_se_detecta(): void
    {
        $this->fichar('entrada', $this->dia->copy()->setTime(22, 30));
        $this->fichar('salida', $this->dia->copy()->addDay()->setTime(6, 0));

        $this->travelTo($this->dia->copy()->addDay()->setTime(12, 0));

        $this->assertSame(['retraso: Entró a las 22:30 (esperado 22:00)'], $this->incidenciasDelDia());
    }

    public function test_de_madrugada_sigue_trabajando_y_no_es_un_fichaje_sin_cerrar(): void
    {
        $this->fichar('entrada', $this->dia->copy()->setTime(22, 0));

        $this->travelTo($this->dia->copy()->addDay()->setTime(3, 0));
        $this->assertSame([], $this->incidenciasDelDia());

        // Pasada la hora de salida (06:00 + margen) sin fichar, ya sí.
        $this->travelTo($this->dia->copy()->addDay()->setTime(7, 0));
        $this->assertSame(['sin_cerrar: Entrada a las 22:00 sin salida'], $this->incidenciasDelDia());
    }

    public function test_la_jornada_de_noche_no_se_cuela_en_el_dia_siguiente(): void
    {
        $this->fichar('entrada', $this->dia->copy()->setTime(22, 0));
        $this->fichar('salida', $this->dia->copy()->addDay()->setTime(6, 0));

        // El día siguiente, a mediodía: aún no le toca entrar (22:00) y la salida de las
        // 06:00 es de la jornada anterior, no un fichaje suelto de hoy.
        $this->travelTo($this->dia->copy()->addDay()->setTime(12, 0));

        $this->assertSame([], IncidenciasCalculador::delDia($this->empresa->id, $this->dia->copy()->addDay())->all());
    }

    public function test_el_informe_de_un_dia_incluye_la_salida_de_madrugada(): void
    {
        $admin = User::factory()->adminEmpresa()->for($this->empresa)->create();
        $this->fichar('entrada', $this->dia->copy()->setTime(22, 0));
        $this->fichar('salida', $this->dia->copy()->addDay()->setTime(6, 0));
        Tenant::clear();

        $csv = $this->actingAs($admin)
            ->get(route('admin.fichajes.exportar', ['formato' => 'csv', 'desde' => $this->dia->toDateString(), 'hasta' => $this->dia->toDateString()]))
            ->streamedContent();

        $filas = array_values(array_filter(explode("\n", trim($csv)), fn ($fila) => str_contains($fila, $this->empleado->name)));
        $this->assertCount(1, $filas);
        $this->assertStringContainsString('22:00:00,06:00:00,8', $filas[0]);

        // Y el día siguiente no muestra esa salida suelta.
        $this->actingAs($admin)
            ->get(route('admin.fichajes.index', ['desde' => $this->dia->copy()->addDay()->toDateString(), 'hasta' => $this->dia->copy()->addDay()->toDateString()]))
            ->assertDontSee($this->empleado->name.'</td>', false);
    }
}
