<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Ausencia;
use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Services\InformeMensual;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Informe mensual del registro (arts. 34.9, 12.4.c y 35.5 ET) y exportación completa
 * para la Inspección.
 */
class InformeMensualTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    private User $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        // Un mes fijo: lunes 2 de marzo de 2026.
        $this->travelTo(Carbon::create(2026, 3, 31, 20, 0));

        $this->empresa = Empresa::factory()->create();
        $this->admin = User::factory()->adminEmpresa()->for($this->empresa)->create(['created_at' => '2026-01-01']);
        $this->empleado = User::factory()->for($this->empresa)->create([
            'name' => 'Ana García',
            'dni_nie' => '12345678Z',
            'hora_entrada_esperada' => '09:00:00',
            'hora_salida_esperada' => '17:00:00',
            'dias_laborables' => [1, 2, 3, 4, 5],
            'created_at' => '2026-01-01',
        ]);
    }

    private function fichar(string $tipo, string $cuando): Fichaje
    {
        Tenant::set($this->empresa->id);

        return Fichaje::create(['user_id' => $this->empleado->id, 'tipo' => $tipo, 'fecha_hora' => $cuando, 'origen' => 'kiosco']);
    }

    public function test_totaliza_el_mes_y_las_horas_por_encima_del_horario(): void
    {
        // Lunes 2: 8 h justas. Martes 3: 10 h (2 de más).
        $this->fichar('entrada', '2026-03-02 09:00:00');
        $this->fichar('salida', '2026-03-02 17:00:00');
        $this->fichar('entrada', '2026-03-03 08:00:00');
        $this->fichar('salida', '2026-03-03 18:00:00');

        Tenant::set($this->empresa->id);
        $informe = InformeMensual::deEmpleado($this->empleado, Carbon::create(2026, 3, 1));

        $this->assertSame(18.0, $informe['total']);
        // Marzo de 2026 tiene 22 días laborables de lunes a viernes: 176 h previstas.
        $this->assertSame(176.0, $informe['previstas']);
        $this->assertSame(0.0, $informe['exceso']);
        $this->assertSame(10.0, collect($informe['dias'])->firstWhere(fn ($d) => $d['fecha']->day === 3)['horas']);
    }

    public function test_una_ausencia_aprobada_no_cuenta_como_prevista_y_no_revela_si_es_baja(): void
    {
        Tenant::set($this->empresa->id);
        Ausencia::create([
            'user_id' => $this->empleado->id, 'tipo' => 'baja_medica', 'estado' => 'aprobada',
            'fecha_inicio' => '2026-03-02', 'fecha_fin' => '2026-03-06',
        ]);

        $informe = InformeMensual::deEmpleado($this->empleado, Carbon::create(2026, 3, 1));

        $this->assertSame(136.0, $informe['previstas']);
        $lunes = collect($informe['dias'])->firstWhere(fn ($d) => $d['fecha']->day === 2);
        $this->assertSame(['Ausencia justificada'], $lunes['notas']);
    }

    public function test_el_admin_ve_los_totales_y_descarga_el_pdf_queda_en_auditoria(): void
    {
        $this->fichar('entrada', '2026-03-02 09:00:00');
        $this->fichar('salida', '2026-03-02 17:00:00');

        $this->actingAs($this->admin)->get(route('admin.informes.index', ['mes' => '2026-03']))
            ->assertOk()
            ->assertSee('Ana García')
            ->assertSee('8,00 h');

        $this->actingAs($this->admin)->get(route('admin.informes.pdf', ['mes' => '2026-03']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->admin)->get(route('admin.informes.pdf', ['mes' => '2026-03', 'user_id' => $this->empleado->id]))
            ->assertOk()
            ->assertDownload('registro-jornada-2026-03-ana-garcia.pdf');

        $this->assertSame(2, AuditLog::where('accion', 'informe_mensual_descargado')->count());
    }

    public function test_el_admin_no_puede_pedir_el_informe_de_alguien_de_otra_empresa(): void
    {
        $ajeno = User::factory()->for(Empresa::factory())->create();

        $this->actingAs($this->admin)->get(route('admin.informes.pdf', ['mes' => '2026-03', 'user_id' => $ajeno->id]))
            ->assertNotFound();
    }

    public function test_el_empleado_descarga_su_propio_registro_aunque_la_empresa_no_pague(): void
    {
        $this->empresa->subscriptions()->delete();

        $this->actingAs($this->empleado)->get(route('fichajes.informe', ['mes' => '2026-03']))
            ->assertOk()
            ->assertDownload('mi-registro-de-jornada-2026-03.pdf');
    }

    public function test_un_empleado_no_entra_en_los_informes_de_la_empresa(): void
    {
        $this->actingAs($this->empleado)->get(route('admin.informes.index'))->assertForbidden();
    }

    public function test_la_exportacion_completa_lleva_hora_registrada_valida_correccion_y_sello(): void
    {
        $entrada = $this->fichar('entrada', '2026-03-02 09:12:00');
        $this->fichar('salida', '2026-03-02 17:00:00');
        Tenant::clear();

        $this->actingAs($this->admin)->post(route('admin.fichajes.correcciones.store', $entrada), [
            'fecha_hora_corregida' => '2026-03-02 09:00:00',
            'motivo' => 'Olvidó fichar al llegar',
        ])->assertSessionHasNoErrors();

        $csv = $this->actingAs($this->admin)
            ->get(route('admin.fichajes.exportar', ['formato' => 'csv']))
            ->streamedContent();

        $this->assertStringContainsString('Entrada registrada', $csv);
        $this->assertStringContainsString('02/03/2026 09:12:00', $csv);
        $this->assertStringContainsString('02/03/2026 09:00:00', $csv);
        $this->assertStringContainsString('por '.$this->admin->name, $csv);
        $this->assertStringContainsString('Olvidó fichar al llegar', $csv);
        $this->assertStringContainsString($entrada->fresh()->huella, $csv);
        $this->assertSame(1, AuditLog::where('accion', 'fichajes_exportados')->count());
    }
}
