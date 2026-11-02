<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Derechos de acceso y portabilidad (arts. 15 y 20 RGPD).
 */
class DatosPersonalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_empleado_descarga_sus_datos_sin_secretos(): void
    {
        $empleado = User::factory()->for(Empresa::factory())->create(['name' => 'Ana García', 'dni_nie' => '12345678Z']);
        $empleado->generarNuevoPin();
        $this->actingAs($empleado)->post(route('fichajes.store'));

        $respuesta = $this->actingAs($empleado)->get(route('profile.datos'));

        $respuesta->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="mis-datos-'.now()->format('Y-m-d').'.json"')
            ->assertJsonPath('persona.nombre', 'Ana García')
            ->assertJsonPath('persona.dni_nie', '12345678Z')
            ->assertJsonPath('registro_de_jornada.0.tipo', 'entrada');

        $texto = $respuesta->getContent();
        foreach (['pin_hash', 'password', 'dos_pasos_secreto', 'dni_hash'] as $secreto) {
            $this->assertStringNotContainsString($secreto, $texto);
        }

        $this->assertSame(1, AuditLog::where('accion', 'datos_personales_descargados')->count());
    }

    public function test_el_admin_descarga_los_datos_de_un_empleado_de_su_empresa_y_no_de_otra(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create(['name' => 'Luis']);
        $ajeno = User::factory()->for(Empresa::factory())->create();

        $this->actingAs($admin)->get(route('admin.empleados.datos', $empleado))
            ->assertOk()
            ->assertJsonPath('persona.nombre', 'Luis');

        $this->actingAs($admin)->get(route('admin.empleados.datos', $ajeno))->assertNotFound();
        $this->actingAs($empleado)->get(route('admin.empleados.datos', $empleado))->assertForbidden();
    }
}
