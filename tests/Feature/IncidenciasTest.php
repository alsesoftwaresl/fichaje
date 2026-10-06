<?php

namespace Tests\Feature;

use App\Models\Ausencia;
use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\FichajeCorreccion;
use App\Models\User;
use App\Services\IncidenciasCalculador;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IncidenciasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Hora fija (mediodía de hoy): sin esto, "hoy" cambiaría de resultado
        // según la hora a la que se ejecute la prueba (sin fichar / sin cerrar
        // dependen de si ya pasó la hora esperada).
        Carbon::setTestNow(Carbon::now()->setTime(12, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function crearEmpleadoConHorario(Empresa $empresa, Carbon $hoy): User
    {
        return User::factory()->for($empresa)->create([
            'hora_entrada_esperada' => '09:00:00',
            'hora_salida_esperada' => '17:00:00',
            'dias_laborables' => [$hoy->dayOfWeekIso],
        ]);
    }

    public function test_marca_retraso_fuera_de_margen(): void
    {
        $empresa = Empresa::factory()->create();
        $hoy = now();
        $empleado = $this->crearEmpleadoConHorario($empresa, $hoy);

        Tenant::set($empresa->id);
        Fichaje::create([
            'user_id' => $empleado->id,
            'tipo' => 'entrada',
            // 20 minutos tarde: fuera del margen de 10 minutos.
            'fecha_hora' => $hoy->copy()->setTime(9, 20),
            'origen' => 'web',
        ]);
        Tenant::clear();

        $incidencias = IncidenciasCalculador::delDia($empresa->id, $hoy);

        $this->assertTrue($incidencias->contains(fn ($i) => $i['tipo'] === 'retraso' && $i['empleado']->id === $empleado->id));
    }

    public function test_dentro_del_margen_no_genera_incidencia(): void
    {
        $empresa = Empresa::factory()->create();
        $hoy = now();
        $empleado = $this->crearEmpleadoConHorario($empresa, $hoy);

        Tenant::set($empresa->id);
        Fichaje::create([
            'user_id' => $empleado->id,
            'tipo' => 'entrada',
            // 5 minutos tarde: dentro del margen de 10 minutos.
            'fecha_hora' => $hoy->copy()->setTime(9, 5),
            'origen' => 'web',
        ]);
        Tenant::clear();

        $incidencias = IncidenciasCalculador::delDia($empresa->id, $hoy);

        $this->assertFalse($incidencias->contains(fn ($i) => $i['empleado']->id === $empleado->id));
    }

    public function test_marca_horas_de_mas(): void
    {
        $empresa = Empresa::factory()->create();
        $hoy = now();
        $empleado = $this->crearEmpleadoConHorario($empresa, $hoy);

        Tenant::set($empresa->id);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'entrada', 'fecha_hora' => $hoy->copy()->setTime(9, 0), 'origen' => 'web']);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'salida', 'fecha_hora' => $hoy->copy()->setTime(18, 30), 'origen' => 'web']);
        Tenant::clear();

        $incidencias = IncidenciasCalculador::delDia($empresa->id, $hoy);

        $this->assertTrue($incidencias->contains(fn ($i) => $i['tipo'] === 'horas_de_mas' && $i['empleado']->id === $empleado->id));
    }

    public function test_empleado_sin_horario_no_genera_incidencias(): void
    {
        $empresa = Empresa::factory()->create();
        $hoy = now();
        $empleado = User::factory()->for($empresa)->create(); // sin horario

        Tenant::set($empresa->id);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'entrada', 'fecha_hora' => $hoy->copy()->setTime(12, 0), 'origen' => 'web']);
        Tenant::clear();

        $incidencias = IncidenciasCalculador::delDia($empresa->id, $hoy);

        $this->assertTrue($incidencias->isEmpty());
    }

    public function test_la_correccion_de_un_fichaje_recalcula_la_incidencia(): void
    {
        $empresa = Empresa::factory()->create();
        $hoy = now();
        $empleado = $this->crearEmpleadoConHorario($empresa, $hoy);

        Tenant::set($empresa->id);
        $entrada = Fichaje::create([
            'user_id' => $empleado->id,
            'tipo' => 'entrada',
            'fecha_hora' => $hoy->copy()->setTime(9, 30), // 30 min tarde -> incidencia
            'origen' => 'web',
        ]);
        Tenant::clear();

        $this->assertTrue(
            IncidenciasCalculador::delDia($empresa->id, $hoy)->contains(fn ($i) => $i['tipo'] === 'retraso')
        );

        Tenant::set($empresa->id);
        FichajeCorreccion::create([
            'fichaje_original_id' => $entrada->id,
            'fecha_hora_corregida' => $hoy->copy()->setTime(9, 2), // corregido: dentro de margen
            'motivo' => 'El reloj del fichaje físico iba adelantado.',
            'corregido_por' => $empleado->id,
        ]);
        Tenant::clear();

        $this->assertFalse(
            IncidenciasCalculador::delDia($empresa->id, $hoy)->contains(fn ($i) => $i['tipo'] === 'retraso')
        );
    }

    public function test_marca_sin_fichar_pasada_la_hora_de_entrada(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = $this->crearEmpleadoConHorario($empresa, now());

        $incidencias = IncidenciasCalculador::delDia($empresa->id);

        $this->assertTrue($incidencias->contains(fn ($i) => $i['tipo'] === 'sin_fichar' && $i['empleado']->id === $empleado->id));
    }

    public function test_no_marca_sin_fichar_antes_de_la_hora_de_entrada(): void
    {
        $empresa = Empresa::factory()->create();
        $this->crearEmpleadoConHorario($empresa, now());

        Carbon::setTestNow(Carbon::now()->setTime(8, 0));

        $this->assertTrue(IncidenciasCalculador::delDia($empresa->id)->isEmpty());
    }

    public function test_quien_tiene_una_ausencia_aprobada_no_genera_incidencias(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = $this->crearEmpleadoConHorario($empresa, now());

        Tenant::set($empresa->id);
        Ausencia::create([
            'user_id' => $empleado->id,
            'tipo' => 'vacaciones',
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDay(),
            'estado' => 'aprobada',
        ]);
        Tenant::clear();

        $this->assertTrue(IncidenciasCalculador::delDia($empresa->id)->isEmpty());
    }

    public function test_marca_fichaje_sin_cerrar_de_un_dia_pasado(): void
    {
        $empresa = Empresa::factory()->create();
        $ayer = now()->subDay();
        $empleado = User::factory()->for($empresa)->create(['created_at' => now()->subMonth()]); // sin horario: también cuenta

        Tenant::set($empresa->id);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'entrada', 'fecha_hora' => $ayer->copy()->setTime(9, 0), 'origen' => 'web']);
        Tenant::clear();

        $incidencias = IncidenciasCalculador::delDia($empresa->id, $ayer);

        $this->assertTrue($incidencias->contains(fn ($i) => $i['tipo'] === 'sin_cerrar' && $i['empleado']->id === $empleado->id));
    }

    public function test_un_turno_que_cruza_la_medianoche_no_cuenta_como_sin_cerrar(): void
    {
        $empresa = Empresa::factory()->create();
        $ayer = now()->subDay();
        $empleado = User::factory()->for($empresa)->create(['created_at' => now()->subMonth()]);

        Tenant::set($empresa->id);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'entrada', 'fecha_hora' => $ayer->copy()->setTime(22, 0), 'origen' => 'web']);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'salida', 'fecha_hora' => now()->setTime(6, 0), 'origen' => 'web']);
        Tenant::clear();

        $this->assertFalse(
            IncidenciasCalculador::delDia($empresa->id, $ayer)->contains(fn ($i) => $i['tipo'] === 'sin_cerrar')
        );
    }

    public function test_hoy_el_fichaje_abierto_solo_es_incidencia_pasada_la_hora_de_salida(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = $this->crearEmpleadoConHorario($empresa, now());

        Tenant::set($empresa->id);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'entrada', 'fecha_hora' => now()->setTime(9, 0), 'origen' => 'web']);
        Tenant::clear();

        // A las 12:00 es normal seguir fichado.
        $this->assertFalse(IncidenciasCalculador::delDia($empresa->id)->contains(fn ($i) => $i['tipo'] === 'sin_cerrar'));

        // A las 18:00 ya debería haber salido.
        Carbon::setTestNow(Carbon::now()->setTime(18, 0));
        $this->assertTrue(IncidenciasCalculador::delDia($empresa->id)->contains(fn ($i) => $i['tipo'] === 'sin_cerrar'));
    }

    public function test_el_informe_por_periodo_resume_retrasos_y_horas_extra(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $ayer = now()->subDay();
        $empleado = User::factory()->for($empresa)->create([
            'created_at' => now()->subMonth(),
            'name' => 'Persona Con Retraso',
            'hora_entrada_esperada' => '09:00:00',
            'hora_salida_esperada' => '17:00:00',
            'dias_laborables' => [1, 2, 3, 4, 5, 6, 7],
        ]);

        Tenant::set($empresa->id);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'entrada', 'fecha_hora' => $ayer->copy()->setTime(9, 30), 'origen' => 'web']);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'salida', 'fecha_hora' => $ayer->copy()->setTime(18, 30), 'origen' => 'web']);
        Tenant::clear();

        $periodo = IncidenciasCalculador::delPeriodo($empresa->id, $ayer, $ayer);
        $this->assertSame(30, $periodo->firstWhere('tipo', 'retraso')['minutos']);

        $this->actingAs($admin)
            ->get(route('admin.incidencias.index', ['desde' => $ayer->toDateString(), 'hasta' => $ayer->toDateString()]))
            ->assertOk()
            ->assertSee('Persona Con Retraso')
            ->assertSee('Retraso')
            ->assertSee('Horas de más');
    }

    public function test_un_empleado_nuevo_no_genera_incidencias_de_dias_anteriores_a_su_alta(): void
    {
        $empresa = Empresa::factory()->create();
        $this->crearEmpleadoConHorario($empresa, now()->subDay()); // alta hoy

        $ayer = now()->subDay();
        User::where('empresa_id', $empresa->id)->update(['dias_laborables' => [1, 2, 3, 4, 5, 6, 7]]);

        $this->assertTrue(IncidenciasCalculador::delDia($empresa->id, $ayer)->isEmpty());
    }
}
