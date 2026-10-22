<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Services\IncidenciasCalculador;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Vigila que los cálculos pesados no hagan más consultas según crecen los datos: si una
 * de estas pruebas falla, probablemente se ha vuelto a consultar dentro de un bucle.
 */
class RendimientoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 10, 30, 12, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Empresa con varios empleados con horario y fichajes repartidos en el mes. */
    protected function empresaConActividad(): Empresa
    {
        $empresa = Empresa::factory()->create();

        $empleados = User::factory()->count(4)->for($empresa)->create([
            'hora_entrada_esperada' => '09:00:00',
            'hora_salida_esperada' => '17:00:00',
            'dias_laborables' => [1, 2, 3, 4, 5],
            'created_at' => now()->subMonths(2),
        ]);

        Tenant::set($empresa->id);
        foreach ($empleados as $i => $empleado) {
            for ($dia = 1; $dia <= 28; $dia += 2) {
                $fecha = Carbon::create(2026, 10, $dia);
                Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'entrada', 'fecha_hora' => $fecha->copy()->setTime(9, 5 * $i), 'origen' => 'web']);
                Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'salida', 'fecha_hora' => $fecha->copy()->setTime(17, 30), 'origen' => 'web']);
            }
        }
        Tenant::clear();

        return $empresa;
    }

    public function test_las_incidencias_de_un_periodo_usan_un_numero_fijo_de_consultas(): void
    {
        $empresa = $this->empresaConActividad();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $mes = IncidenciasCalculador::delPeriodo($empresa->id, Carbon::create(2026, 10, 1), Carbon::create(2026, 10, 30));
        $consultasMes = count(DB::getQueryLog());

        DB::flushQueryLog();
        $semana = IncidenciasCalculador::delPeriodo($empresa->id, Carbon::create(2026, 10, 1), Carbon::create(2026, 10, 7));
        $consultasSemana = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertNotEmpty($mes);
        $this->assertNotEmpty($semana);
        // Un mes no cuesta más consultas que una semana, y son pocas.
        $this->assertSame($consultasSemana, $consultasMes);
        $this->assertLessThanOrEqual(8, $consultasMes);
    }

    public function test_calcular_el_periodo_de_una_vez_da_lo_mismo_que_dia_a_dia(): void
    {
        $empresa = $this->empresaConActividad();

        $periodo = IncidenciasCalculador::delPeriodo($empresa->id, Carbon::create(2026, 10, 1), Carbon::create(2026, 10, 15));

        $diaADia = collect();
        for ($d = 1; $d <= 15; $d++) {
            $diaADia = $diaADia->concat(IncidenciasCalculador::delDia($empresa->id, Carbon::create(2026, 10, $d)));
        }

        $resumen = fn ($i) => $i['fecha']->toDateString().'|'.$i['empleado']->id.'|'.$i['tipo'].'|'.$i['detalle'];

        $this->assertSame($diaADia->map($resumen)->sort()->values()->all(), $periodo->map($resumen)->sort()->values()->all());
    }
}
