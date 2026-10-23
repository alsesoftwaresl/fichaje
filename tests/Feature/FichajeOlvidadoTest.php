<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Services\EstadoEquipoCalculador;
use App\Services\JornadasAgrupador;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Salidas olvidadas, doble toque y turnos que cruzan la medianoche al fichar.
 */
class FichajeOlvidadoTest extends TestCase
{
    use RefreshDatabase;

    private function fichajesDe(User $empleado)
    {
        Tenant::set($empleado->empresa_id);

        return Fichaje::where('user_id', $empleado->id)->orderBy('fecha_hora')->orderBy('id')->get();
    }

    public function test_si_olvido_la_salida_ayer_hoy_al_llegar_ficha_entrada(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        $pin = $empleado->generarNuevoPin();

        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin]);

        // Al día siguiente, sin haber fichado la salida.
        $this->travel(1)->days();

        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin])
            ->assertSessionHas('resultado', fn ($r) => $r['tipo'] === 'entrada' && str_contains($r['aviso'], 'quedó sin salida'));

        $this->assertSame(['entrada', 'entrada'], $this->fichajesDe($empleado)->pluck('tipo')->all());

        // Y por la tarde, la salida de hoy se empareja con la entrada de hoy, no con la de ayer.
        $this->travel(8)->hours();
        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin])
            ->assertSessionHas('resultado', fn ($r) => $r['tipo'] === 'salida' && $r['aviso'] === null);

        $jornadas = JornadasAgrupador::agrupar($this->fichajesDe($empleado)->load(['usuario', 'correcciones']));
        $this->assertSame([8.0, null], $jornadas->pluck('horas')->all());
    }

    public function test_un_turno_de_noche_se_cierra_normalmente_y_cuenta_como_trabajando(): void
    {
        $this->travelTo(now()->setTime(22, 0));
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->post(route('fichajes.store'));

        // Pasada la medianoche sigue trabajando.
        $this->travel(3)->hours();
        $estado = EstadoEquipoCalculador::delDia($empresa->id)->firstWhere('empleado.id', $empleado->id);
        $this->assertSame('trabajando', $estado['estado']);

        // A las 06:00 ficha la salida de la misma jornada.
        $this->travel(5)->hours();
        $this->actingAs($empleado)->post(route('fichajes.store'));

        $this->assertSame(['entrada', 'salida'], $this->fichajesDe($empleado)->pluck('tipo')->all());
    }

    public function test_una_entrada_olvidada_no_cuenta_como_trabajando(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        $this->actingAs($empleado)->post(route('fichajes.store'));

        $this->travel(1)->days();

        $estado = EstadoEquipoCalculador::delDia($empresa->id)->firstWhere('empleado.id', $empleado->id);
        $this->assertSame('fuera', $estado['estado']);
    }

    public function test_el_doble_toque_en_el_kiosco_no_crea_dos_fichajes(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        $pin = $empleado->generarNuevoPin();

        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin])->assertSessionHas('resultado');

        $this->travel(20)->seconds();
        $this->post(route('kiosko.fichar', $empresa->kiosko_token), ['pin' => $pin])
            ->assertSessionHasErrors(['pin' => 'Ya has fichado hace un momento. Espera un minuto para volver a fichar.']);

        $this->assertCount(1, $this->fichajesDe($empleado));
    }

    public function test_el_doble_clic_en_la_web_no_crea_dos_fichajes(): void
    {
        $empleado = User::factory()->for(Empresa::factory())->create();

        $this->actingAs($empleado)->post(route('fichajes.store'));
        $this->actingAs($empleado)->post(route('fichajes.store'))->assertSessionHasErrors('fichaje');

        $this->assertCount(1, $this->fichajesDe($empleado));
    }

    public function test_el_admin_anade_la_salida_olvidada_con_motivo(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();
        $this->actingAs($empleado)->post(route('fichajes.store'));
        $entrada = $this->fichajesDe($empleado)->first();

        $this->travel(1)->days();

        $this->actingAs($admin)->get(route('admin.fichajes.index'))->assertSee('Sin salida')->assertSee('Añadir salida');

        $this->actingAs($admin)
            ->post(route('admin.fichajes.salida.store', $entrada), [
                'fecha_hora' => $entrada->fecha_hora->copy()->setTime(17, 0)->format('Y-m-d\TH:i'),
                'motivo' => 'Olvidó fichar; confirmado con él.',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Salida añadida.');

        $salida = $this->fichajesDe($empleado)->last();
        $this->assertSame('salida', $salida->tipo);
        $this->assertSame('empresa', $salida->origen);
        $this->assertSame($admin->id, $salida->registrado_por);
        $this->assertSame(1, AuditLog::where('accion', 'salida_olvidada_anadida')->count());

        $jornada = JornadasAgrupador::agrupar($this->fichajesDe($empleado)->load(['usuario', 'correcciones']))->first();
        $this->assertSame(8.0, $jornada['horas']);
        $this->assertStringContainsString('Salida añadida por la empresa: Olvidó fichar', $jornada['notas']);

        // La jornada ya tiene salida: no se puede añadir otra.
        $this->actingAs($admin)
            ->post(route('admin.fichajes.salida.store', $entrada), ['fecha_hora' => $entrada->fecha_hora->copy()->setTime(18, 0)->format('Y-m-d\TH:i'), 'motivo' => 'x'])
            ->assertSessionHasErrors('fecha_hora');
    }

    public function test_la_salida_anadida_tiene_que_tener_sentido(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();
        $this->actingAs($empleado)->post(route('fichajes.store'));
        $entrada = $this->fichajesDe($empleado)->first();

        $intentar = fn ($hora) => $this->actingAs($admin)->post(route('admin.fichajes.salida.store', $entrada), [
            'fecha_hora' => $hora->format('Y-m-d\TH:i'),
            'motivo' => 'Olvido',
        ]);

        $intentar(now()->subHour())->assertSessionHasErrors(['fecha_hora' => 'La salida tiene que ser posterior a la entrada ('.$entrada->fecha_hora->format('d/m/Y H:i').').']);
        $intentar(now()->addHour())->assertSessionHasErrors(['fecha_hora' => 'La salida no puede ser una hora futura.']);

        $this->assertCount(1, $this->fichajesDe($empleado));
    }

    public function test_no_se_anade_salida_a_un_fichaje_de_otra_empresa(): void
    {
        $empleadoB = User::factory()->for(Empresa::factory())->create();
        $this->actingAs($empleadoB)->post(route('fichajes.store'));
        $entradaB = $this->fichajesDe($empleadoB)->first();
        Tenant::clear();

        $adminA = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        $this->actingAs($adminA)
            ->post(route('admin.fichajes.salida.store', $entradaB->id), ['fecha_hora' => now()->format('Y-m-d\TH:i'), 'motivo' => 'x'])
            ->assertNotFound();
    }

    public function test_los_datos_antiguos_con_la_salida_al_dia_siguiente_no_forman_una_jornada_de_24_horas(): void
    {
        $empleado = User::factory()->for(Empresa::factory())->create();
        Tenant::set($empleado->empresa_id);
        $ayer = now()->subDay()->setTime(9, 0);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'entrada', 'fecha_hora' => $ayer]);
        Fichaje::create(['user_id' => $empleado->id, 'tipo' => 'salida', 'fecha_hora' => $ayer->copy()->addDay()]);

        $jornadas = JornadasAgrupador::agrupar($this->fichajesDe($empleado)->load(['usuario', 'correcciones']));

        $this->assertCount(2, $jornadas);
        $this->assertSame([null, null], $jornadas->pluck('horas')->all());
    }

    public function test_el_empleado_ve_su_entrada_olvidada_como_sin_salida(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $empleado = User::factory()->for(Empresa::factory())->create();
        $this->actingAs($empleado)->post(route('fichajes.store'));

        $this->actingAs($empleado)->get(route('fichajes.mis'))->assertSee('En curso')->assertDontSee('Sin salida');

        $this->travel(1)->days();
        $this->actingAs($empleado)->get(route('fichajes.mis'))->assertSee('Sin salida')->assertSee('Fichar entrada');
    }
}
