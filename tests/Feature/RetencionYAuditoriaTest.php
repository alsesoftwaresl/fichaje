<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Contacto;
use App\Models\Empresa;
use App\Models\Nomina;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Plazos de conservación y registro de accesos que prometen los textos legales.
 */
class RetencionYAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_mensajes_de_contacto_se_borran_a_los_12_meses(): void
    {
        $viejo = Contacto::create(['nombre' => 'A', 'email' => 'a@example.com', 'asunto' => 'comercial', 'mensaje' => 'Hola']);
        $viejo->forceFill(['created_at' => now()->subMonths(13)])->save();
        Contacto::create(['nombre' => 'B', 'email' => 'b@example.com', 'asunto' => 'comercial', 'mensaje' => 'Hola']);

        $this->artisan('contactos:purgar')->assertSuccessful();

        $this->assertSame(['b@example.com'], Contacto::pluck('email')->all());
    }

    public function test_que_otra_persona_abra_una_nomina_queda_registrado(): void
    {
        Storage::fake(Nomina::DISCO);
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();
        Storage::disk(Nomina::DISCO)->put('nominas/x.pdf', '%PDF');

        Tenant::set($empresa->id);
        $nomina = Nomina::create([
            'user_id' => $empleado->id, 'periodo' => '2026-10-01', 'ruta' => 'nominas/x.pdf',
            'nombre_original' => 'x.pdf', 'tamano' => 4, 'subida_por' => $admin->id,
        ]);
        Tenant::clear();

        $this->actingAs($empleado)->get(route('nominas.descargar', $nomina))->assertOk();
        $this->assertSame(0, AuditLog::where('accion', 'nomina_descargada')->count());

        $this->actingAs($admin)->get(route('nominas.descargar', $nomina))->assertOk();
        $this->assertSame(1, AuditLog::where('accion', 'nomina_descargada')->count());
    }

    public function test_los_textos_legales_no_tienen_apartados_pendientes(): void
    {
        foreach (['legal.privacidad', 'legal.encargo-tratamiento', 'legal.terminos', 'legal.cookies', 'legal.aviso-legal'] as $ruta) {
            $this->get(route($ruta))->assertOk()->assertDontSee('[Pendiente');
        }

        $this->get(route('legal.encargo-tratamiento'))->assertSee('Subencargados')->assertSee('48 horas');
    }
}
