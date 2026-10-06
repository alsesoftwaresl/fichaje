<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Nomina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NominasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Nomina::DISCO);
    }

    protected function pdf(string $nombre = 'nomina.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
    }

    protected function subir(User $quien, User $paraQuien, array $extra = [])
    {
        return $this->actingAs($quien)->post(route('nominas.gestion.store'), $extra + [
            'user_id' => $paraQuien->id,
            'mes' => '2026-09',
            'archivo' => $this->pdf(),
        ]);
    }

    public function test_el_admin_sube_una_nomina_y_el_empleado_la_ve_y_la_descarga(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create(['name' => 'Ana Lopez']);

        $this->subir($admin, $empleado, ['descripcion' => 'Paga extra'])->assertRedirect(route('nominas.gestion.index'));

        $nomina = Nomina::firstOrFail();
        Storage::disk(Nomina::DISCO)->assertExists($nomina->ruta);
        $this->assertStringStartsWith('nominas/'.$empresa->id.'/'.$empleado->id.'/', $nomina->ruta);
        $this->assertNull($nomina->descargada_en);

        $this->actingAs($empleado)->get(route('nominas.mis'))
            ->assertOk()->assertSee('Septiembre 2026')->assertSee('Paga extra')->assertSee('Nueva');

        $this->actingAs($empleado)->get(route('nominas.descargar', $nomina))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->assertNotNull($nomina->fresh()->descargada_en);
    }

    public function test_un_empleado_no_puede_ver_las_nominas_de_otro(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $ana = User::factory()->for($empresa)->create();
        $luis = User::factory()->for($empresa)->create();
        $this->subir($admin, $ana);
        $nominaDeAna = Nomina::firstOrFail();

        $this->actingAs($luis)->get(route('nominas.descargar', $nominaDeAna))->assertForbidden();
        $this->actingAs($luis)->get(route('nominas.mis'))->assertOk()->assertDontSee('Septiembre 2026');
    }

    public function test_una_nomina_de_otra_empresa_da_404(): void
    {
        $a = Empresa::factory()->create();
        $b = Empresa::factory()->create();
        $adminB = User::factory()->adminEmpresa()->for($b)->create();
        $empleadoB = User::factory()->for($b)->create();
        $this->subir($adminB, $empleadoB);
        $nominaB = Nomina::firstOrFail();

        $adminA = User::factory()->adminEmpresa()->for($a)->create();

        $this->actingAs($adminA)->get(route('nominas.descargar', $nominaB))->assertNotFound();
    }

    public function test_el_admin_no_puede_subir_una_nomina_a_un_empleado_de_otra_empresa(): void
    {
        $a = Empresa::factory()->create();
        $b = Empresa::factory()->create();
        $adminA = User::factory()->adminEmpresa()->for($a)->create();
        $empleadoB = User::factory()->for($b)->create();

        $this->subir($adminA, $empleadoB)->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('nominas', 0);
    }

    public function test_un_empleado_sin_permiso_no_puede_gestionar_nominas(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($empleado)->get(route('nominas.gestion.index'))->assertForbidden();
        $this->subir($empleado, $empleado)->assertForbidden();
        $this->assertDatabaseCount('nominas', 0);
    }

    public function test_un_contable_sin_ser_admin_puede_subir_y_descargar_cualquiera_de_la_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $contable = User::factory()->for($empresa)->create(['gestiona_nominas' => true]);
        $empleado = User::factory()->for($empresa)->create();

        $this->subir($contable, $empleado)->assertRedirect();
        $nomina = Nomina::firstOrFail();

        $this->actingAs($contable)->get(route('nominas.descargar', $nomina))->assertOk();

        // Que la abra quien la gestiona no cuenta como "vista por el empleado".
        $this->assertNull($nomina->fresh()->descargada_en);
    }

    public function test_solo_se_admiten_pdf_de_hasta_5_mb(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->subir($admin, $empleado, ['archivo' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream')])
            ->assertSessionHasErrors('archivo');
        $this->subir($admin, $empleado, ['archivo' => UploadedFile::fake()->create('grande.pdf', 6000, 'application/pdf')])
            ->assertSessionHasErrors('archivo');

        $this->assertDatabaseCount('nominas', 0);
    }

    public function test_al_eliminar_se_borra_tambien_el_archivo(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();
        $this->subir($admin, $empleado);
        $nomina = Nomina::firstOrFail();

        $this->actingAs($admin)->delete(route('nominas.gestion.destroy', $nomina))->assertRedirect();

        $this->assertDatabaseCount('nominas', 0);
        Storage::disk(Nomina::DISCO)->assertMissing($nomina->ruta);
    }

    public function test_el_empleado_sigue_viendo_sus_nominas_aunque_la_empresa_no_tenga_suscripcion(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();
        $this->subir($admin, $empleado);
        $nomina = Nomina::firstOrFail();

        $empresa->subscriptions()->delete();

        $this->actingAs($empleado)->get(route('nominas.mis'))->assertOk()->assertSee('Septiembre 2026');
        $this->actingAs($empleado)->get(route('nominas.descargar', $nomina))->assertOk();
        // ... pero gestionar (subir) sí requiere suscripción.
        $this->actingAs($admin->fresh())->get(route('nominas.gestion.index'))->assertRedirect(route('admin.facturacion.index'));
    }

    public function test_se_puede_dar_permiso_de_contable_al_editar_un_empleado(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create(['dni_nie' => '12345678Z']);

        $this->actingAs($admin)->patch(route('admin.empleados.update', $empleado), [
            'name' => $empleado->name, 'dni_nie' => '12345678Z', 'gestiona_nominas' => '1',
        ])->assertRedirect();

        $this->assertTrue($empleado->fresh()->gestiona_nominas);
    }
}
