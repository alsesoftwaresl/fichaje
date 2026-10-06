<?php

namespace Tests\Feature;

use App\Models\Ausencia;
use App\Models\Empresa;
use App\Models\Nomina;
use App\Support\Tenant;
use App\Models\User;
use App\Notifications\AusenciaResuelta;
use App\Notifications\AusenciaSolicitada;
use App\Notifications\NominaDisponible;
use App\Notifications\ResumenIncidencias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvisosEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Crea una solicitud pendiente (no hay factory de Ausencia) fijando la empresa a mano. */
    protected function ausenciaPendiente(User $empleado): Ausencia
    {
        Tenant::set($empleado->empresa_id);
        $ausencia = Ausencia::create([
            'user_id' => $empleado->id,
            'tipo' => 'vacaciones',
            'fecha_inicio' => '2026-12-01',
            'fecha_fin' => '2026-12-05',
            'estado' => 'pendiente',
        ]);
        Tenant::clear();

        return $ausencia;
    }

    public function test_pedir_una_ausencia_avisa_a_los_admins_y_no_al_empleado(): void
    {
        Notification::fake();
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create(['email' => 'ana@empresa.test']);

        $this->actingAs($empleado)->post(route('ausencias.store'), [
            'tipo' => 'vacaciones',
            'fecha_inicio' => '2026-12-01',
            'fecha_fin' => '2026-12-05',
        ])->assertRedirect(route('ausencias.mis'));

        Notification::assertSentTo($admin, AusenciaSolicitada::class);
        Notification::assertNotSentTo($empleado, AusenciaSolicitada::class);
    }

    public function test_el_admin_que_pide_su_propia_ausencia_no_se_avisa_a_si_mismo(): void
    {
        Notification::fake();
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create();

        $this->actingAs($admin)->post(route('ausencias.store'), [
            'tipo' => 'otro',
            'fecha_inicio' => '2026-12-01',
            'fecha_fin' => '2026-12-01',
        ]);

        Notification::assertNothingSent();
    }

    public function test_aprobar_o_rechazar_avisa_al_empleado(): void
    {
        Notification::fake();
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create(['email' => 'ana@empresa.test']);
        $ausencia = $this->ausenciaPendiente($empleado);

        $this->actingAs($admin)->patch(route('admin.ausencias.aprobar', $ausencia))->assertRedirect();
        Notification::assertSentToTimes($empleado, AusenciaResuelta::class, 1);

        $this->actingAs($admin)->patch(route('admin.ausencias.rechazar', $ausencia), ['motivo_rechazo' => 'Fechas de cierre'])->assertRedirect();
        Notification::assertSentToTimes($empleado, AusenciaResuelta::class, 2);
    }

    public function test_un_empleado_sin_email_no_rompe_nada(): void
    {
        Notification::fake();
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create(['email' => null]);
        $ausencia = $this->ausenciaPendiente($empleado);

        $this->actingAs($admin)->patch(route('admin.ausencias.aprobar', $ausencia))->assertRedirect();

        Notification::assertNothingSent();
        $this->assertSame('aprobada', $ausencia->fresh()->estado);
    }

    public function test_subir_una_nomina_avisa_al_empleado(): void
    {
        Notification::fake();
        Storage::fake(Nomina::DISCO);
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create(['email' => 'ana@empresa.test']);

        $this->actingAs($admin)->post(route('nominas.gestion.store'), [
            'user_id' => $empleado->id,
            'mes' => '2026-09',
            'archivo' => UploadedFile::fake()->createWithContent('nomina.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"),
        ])->assertRedirect(route('nominas.gestion.index'));

        Notification::assertSentTo($empleado, NominaDisponible::class);
    }

    public function test_el_resumen_diario_solo_se_envia_a_empresas_con_incidencias_y_acceso(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $conIncidencia = Empresa::factory()->create();
        $adminA = User::factory()->adminEmpresa()->for($conIncidencia)->create();
        // Horario de hoy a las 9:00 y sin fichar a las 12:00 -> "sin fichar".
        User::factory()->for($conIncidencia)->create([
            'hora_entrada_esperada' => '09:00:00',
            'hora_salida_esperada' => '17:00:00',
            'dias_laborables' => [now()->dayOfWeekIso],
        ]);

        $sinIncidencias = Empresa::factory()->create();
        $adminB = User::factory()->adminEmpresa()->for($sinIncidencias)->create();

        $this->artisan('incidencias:resumen')->assertSuccessful();

        Notification::assertSentTo($adminA, ResumenIncidencias::class);
        Notification::assertNotSentTo($adminB, ResumenIncidencias::class);
    }

    public function test_el_resumen_diario_no_escribe_a_empresas_sin_suscripcion(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $empresa = Empresa::factory()->sinSuscripcion()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        User::factory()->for($empresa)->create([
            'hora_entrada_esperada' => '09:00:00',
            'hora_salida_esperada' => '17:00:00',
            'dias_laborables' => [now()->dayOfWeekIso],
        ]);

        $this->artisan('incidencias:resumen')->assertSuccessful();

        Notification::assertNotSentTo($admin, ResumenIncidencias::class);
    }
    public function test_los_correos_se_generan_sin_errores(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create(['name' => 'Admin']);
        $empleado = User::factory()->for($empresa)->create(['name' => 'Ana', 'email' => 'ana@empresa.test']);
        $ausencia = $this->ausenciaPendiente($empleado);
        $ausencia->update(['estado' => 'rechazada', 'motivo_rechazo' => 'Fechas de cierre']);

        $this->assertStringContainsString('Ana', (new AusenciaSolicitada($ausencia))->toMail($admin)->render());
        $this->assertStringContainsString('Fechas de cierre', (new AusenciaResuelta($ausencia))->toMail($empleado)->render());
    }
}
