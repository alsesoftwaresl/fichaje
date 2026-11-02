<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Nomina;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cifrado en reposo de los datos más sensibles: DNI/NIE y PDF de las nóminas.
 */
class CifradoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_dni_se_guarda_cifrado_y_se_lee_normalizado(): void
    {
        $empleado = User::factory()->for(Empresa::factory())->create(['dni_nie' => ' 12.345.678-z ']);

        $guardado = DB::table('users')->where('id', $empleado->id)->value('dni_nie');

        $this->assertStringNotContainsString('12345678', $guardado);
        $this->assertSame('12345678Z', $empleado->fresh()->dni_nie);
        $this->assertSame(User::huellaDni('12345678z'), DB::table('users')->where('id', $empleado->id)->value('dni_hash'));
    }

    public function test_se_busca_por_dni_aunque_este_cifrado(): void
    {
        $empleado = User::factory()->for(Empresa::factory())->create(['dni_nie' => 'X1234567L']);

        $this->assertTrue(User::conDni('x-1234567-l')->first()->is($empleado));
        $this->assertNull(User::conDni('Y1234567L')->first());
    }

    public function test_el_dni_no_se_repite_en_la_empresa_aunque_el_otro_este_de_baja(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        User::factory()->for($empresa)->create(['dni_nie' => '12345678Z'])->delete();

        $this->actingAs($admin)
            ->post(route('admin.empleados.store'), ['name' => 'Otra', 'dni_nie' => '12345678z'])
            ->assertSessionHasErrors(['dni_nie' => 'Ya hay otra persona con ese DNI/NIE en tu empresa.']);
    }

    public function test_la_huella_y_el_pin_no_salen_al_serializar(): void
    {
        $empleado = User::factory()->for(Empresa::factory())->create(['dni_nie' => '12345678Z']);
        $empleado->generarNuevoPin();

        $json = $empleado->fresh()->toArray();

        $this->assertArrayNotHasKey('dni_hash', $json);
        $this->assertArrayNotHasKey('pin_hash', $json);
    }

    public function test_la_nomina_se_guarda_cifrada_y_se_descarga_descifrada(): void
    {
        Storage::fake(Nomina::DISCO);
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();
        $pdf = "%PDF-1.4\nNómina de prueba\n%%EOF";

        $this->actingAs($admin)->post(route('nominas.gestion.store'), [
            'user_id' => $empleado->id,
            'mes' => '2026-10',
            'archivo' => UploadedFile::fake()->createWithContent('nomina-12345678Z.pdf', $pdf),
        ])->assertSessionHasNoErrors();

        Tenant::set($empresa->id);
        $nomina = Nomina::firstOrFail();
        Tenant::clear();

        $enDisco = Storage::disk(Nomina::DISCO)->get($nomina->ruta);
        $this->assertTrue($nomina->cifrada);
        $this->assertStringNotContainsString('%PDF', $enDisco);
        $this->assertStringNotContainsString('12345678Z', $nomina->ruta);

        $descarga = $this->actingAs($empleado)->get(route('nominas.descargar', $nomina));
        $descarga->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame($pdf, $descarga->streamedContent());
    }

    public function test_una_nomina_antigua_sin_cifrar_se_sigue_descargando(): void
    {
        Storage::fake(Nomina::DISCO);
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        Storage::disk(Nomina::DISCO)->put('nominas/vieja.pdf', '%PDF-1.4 vieja');

        Tenant::set($empresa->id);
        $nomina = Nomina::create([
            'user_id' => $empleado->id, 'periodo' => '2026-09-01', 'ruta' => 'nominas/vieja.pdf',
            'nombre_original' => 'vieja.pdf', 'tamano' => 14, 'subida_por' => $empleado->id, 'cifrada' => false,
        ]);
        Tenant::clear();

        $this->assertSame('%PDF-1.4 vieja', $this->actingAs($empleado)->get(route('nominas.descargar', $nomina))->streamedContent());
    }
}
