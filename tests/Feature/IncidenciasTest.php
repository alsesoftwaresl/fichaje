<?php

namespace Tests\Feature;

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
}
