<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\FichajeCorreccion;
use App\Models\User;
use App\Services\JornadasAgrupador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FichajeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_flujo_completo_fichar_corregir_exportar(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();

        // 1. El empleado ficha entrada (toggle automático: no tenía fichajes previos).
        $this->actingAs($empleado)
            ->post(route('fichajes.store'))
            ->assertRedirect(route('fichajes.mis'));

        $this->assertDatabaseHas('fichajes', ['user_id' => $empleado->id, 'tipo' => 'entrada']);

        // 2. Ficha salida (toggle automático: el último fue entrada).
        $this->actingAs($empleado)->post(route('fichajes.store'));

        $this->assertDatabaseHas('fichajes', ['user_id' => $empleado->id, 'tipo' => 'salida']);
        $this->assertSame(2, Fichaje::where('user_id', $empleado->id)->count());

        $fichajeEntrada = Fichaje::where('user_id', $empleado->id)->where('tipo', 'entrada')->first();

        // 3. El admin filtra por empleado y ve el fichaje.
        $this->actingAs($admin)
            ->get(route('admin.fichajes.index', ['user_id' => $empleado->id]))
            ->assertOk()
            ->assertSee($empleado->name);

        // 4. El admin corrige el fichaje de entrada.
        $nuevaFecha = now()->subMinutes(10);

        $this->actingAs($admin)
            ->post(route('admin.fichajes.correcciones.store', $fichajeEntrada), [
                'fecha_hora_corregida' => $nuevaFecha->format('Y-m-d H:i:s'),
                'motivo' => 'El empleado fichó tarde por un fallo de red.',
            ])
            ->assertRedirect();

        $this->assertSame(1, FichajeCorreccion::where('fichaje_original_id', $fichajeEntrada->id)->count());

        // El fichaje original permanece intacto.
        $fichajeEntrada->refresh();
        $this->assertNotEquals($nuevaFecha->format('Y-m-d H:i:s'), $fichajeEntrada->fecha_hora->format('Y-m-d H:i:s'));

        // 5. El admin exporta a CSV.
        $response = $this->actingAs($admin)->get(route('admin.fichajes.exportar', ['formato' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($empleado->name, $response->streamedContent());
    }

    public function test_entrada_y_salida_se_muestran_como_una_sola_jornada(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->post(route('fichajes.store')); // entrada
        $this->actingAs($empleado)->post(route('fichajes.store')); // salida

        // "Mis fichajes" del propio empleado: una fila con entrada y salida.
        $this->actingAs($empleado)
            ->get(route('fichajes.mis'))
            ->assertOk()
            ->assertSee('Entrada', false)
            ->assertSeeInOrder(['Fecha', 'Entrada', 'Salida', 'Horas'], false);

        // Panel de admin: también una sola fila por jornada.
        $response = $this->actingAs($admin)->get(route('admin.fichajes.index'));
        $response->assertOk();
        $response->assertSeeInOrder(['Empleado', 'Fecha', 'Entrada', 'Salida', 'Horas'], false);
    }

    public function test_las_horas_trabajadas_reflejan_la_correccion(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->post(route('fichajes.store')); // entrada
        $this->actingAs($empleado)->post(route('fichajes.store')); // salida

        $fichajeEntrada = Fichaje::where('user_id', $empleado->id)->where('tipo', 'entrada')->first();
        $fichajeSalida = Fichaje::where('user_id', $empleado->id)->where('tipo', 'salida')->first();

        $horasAntes = JornadasAgrupador::agrupar(
            Fichaje::with(['usuario', 'correcciones'])->where('user_id', $empleado->id)->get()
        )->first()['horas'];

        // Corrige la entrada a una hora bastante anterior, para que el
        // cambio en las horas trabajadas sea inequívoco.
        $this->actingAs($admin)->post(route('admin.fichajes.correcciones.store', $fichajeEntrada), [
            'fecha_hora_corregida' => $fichajeEntrada->fecha_hora->copy()->subHour()->format('Y-m-d H:i:s'),
            'motivo' => 'Fichó con una hora de retraso por un fallo del lector.',
        ]);

        $horasDespues = JornadasAgrupador::agrupar(
            Fichaje::with(['usuario', 'correcciones'])->where('user_id', $empleado->id)->get()
        )->first()['horas'];

        $this->assertEqualsWithDelta($horasAntes + 1, $horasDespues, 0.01);

        // El fichaje de salida original sigue intacto (solo se corrigió la entrada).
        $this->assertSame($fichajeSalida->fecha_hora->toDateTimeString(), $fichajeSalida->fresh()->fecha_hora->toDateTimeString());
    }

    public function test_exportar_excel_y_pdf_funcionan(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->post(route('fichajes.store'));
        $this->actingAs($empleado)->post(route('fichajes.store'));

        $excel = $this->actingAs($admin)->get(route('admin.fichajes.exportar', ['formato' => 'excel']));
        $excel->assertOk();
        $excel->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $pdf = $this->actingAs($admin)->get(route('admin.fichajes.exportar', ['formato' => 'pdf']));
        $pdf->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');
    }
}
