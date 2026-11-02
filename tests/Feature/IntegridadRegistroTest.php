<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Services\IntegridadRegistro;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sello de integridad del registro: detecta cambios y borrados hechos fuera de la app.
 */
class IntegridadRegistroTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    private User $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::factory()->create();
        $this->admin = User::factory()->adminEmpresa()->for($this->empresa)->create();
        $this->empleado = User::factory()->for($this->empresa)->create();
    }

    /** Tres fichajes del empleado y una corrección del admin, por las vías normales. */
    private function registroNormal(): void
    {
        foreach (range(1, 3) as $i) {
            $this->actingAs($this->empleado)->post(route('fichajes.store'));
            $this->travel(2)->hours();
        }

        Tenant::set($this->empresa->id);
        $primero = Fichaje::orderBy('id')->first();
        Tenant::clear();

        $this->actingAs($this->admin)->post(route('admin.fichajes.correcciones.store', $primero), [
            'fecha_hora_corregida' => $primero->fecha_hora->copy()->subMinutes(5)->format('Y-m-d H:i:s'),
            'motivo' => 'Llegó antes y fichó tarde.',
        ])->assertSessionHasNoErrors();
    }

    public function test_un_registro_normal_cuadra_y_cada_fichaje_lleva_su_huella(): void
    {
        $this->registroNormal();

        $resultado = IntegridadRegistro::verificar($this->empresa->id);

        $this->assertSame(3, $resultado['fichajes']);
        $this->assertSame(1, $resultado['correcciones']);
        $this->assertSame([], $resultado['problemas']);
        $this->assertSame(0, DB::table('fichajes')->whereNull('huella')->count());
    }

    public function test_cambiar_un_fichaje_en_la_base_de_datos_se_detecta(): void
    {
        $this->registroNormal();
        $segundo = DB::table('fichajes')->orderBy('id')->skip(1)->first();

        DB::table('fichajes')->where('id', $segundo->id)->update(['fecha_hora' => '2020-01-01 09:00:00']);

        $problemas = IntegridadRegistro::verificar($this->empresa->id)['problemas'];
        $this->assertSame([$segundo->id], array_column($problemas, 'id'));
    }

    public function test_borrar_un_fichaje_en_la_base_de_datos_se_detecta(): void
    {
        $this->registroNormal();
        [$primero, $segundo, $tercero] = DB::table('fichajes')->orderBy('id')->get()->all();

        DB::table('fichajes')->where('id', $segundo->id)->delete();

        // El siguiente apuntaba al borrado: deja de cuadrar.
        $problemas = IntegridadRegistro::verificar($this->empresa->id)['problemas'];
        $this->assertSame([$tercero->id], array_column($problemas, 'id'));
    }

    public function test_cambiar_una_correccion_en_la_base_de_datos_se_detecta(): void
    {
        $this->registroNormal();

        DB::table('fichaje_correcciones')->update(['motivo' => 'Otro motivo inventado']);

        $problemas = IntegridadRegistro::verificar($this->empresa->id)['problemas'];
        $this->assertSame(['corrección'], array_column($problemas, 'tipo'));
    }

    public function test_el_admin_comprueba_la_integridad_y_queda_anotado(): void
    {
        $this->registroNormal();

        $this->actingAs($this->admin)->get(route('admin.fichajes.integridad'))
            ->assertOk()
            ->assertSee('Correcto')
            ->assertSee('Se han comprobado 3 fichajes y 1 correcciones');

        $this->assertSame(1, AuditLog::where('accion', 'registro_verificado')->count());

        DB::table('fichajes')->limit(1)->update(['tipo' => 'salida']);

        $this->actingAs($this->admin)->get(route('admin.fichajes.integridad'))->assertSee('Revisar');
    }

    public function test_un_empleado_no_puede_ver_la_comprobacion(): void
    {
        $this->actingAs($this->empleado)->get(route('admin.fichajes.integridad'))->assertForbidden();
    }

    public function test_el_comando_falla_si_algo_no_cuadra(): void
    {
        $this->registroNormal();
        $this->artisan('registro:verificar')->assertSuccessful();

        DB::table('fichajes')->limit(1)->update(['ip_address' => '10.0.0.1']);
        $this->artisan('registro:verificar')->assertFailed();
    }

    public function test_cada_empleado_tiene_su_propia_cadena(): void
    {
        $otro = User::factory()->for($this->empresa)->create();

        $this->actingAs($this->empleado)->post(route('fichajes.store'));
        $this->travel(5)->minutes();
        $this->actingAs($otro)->post(route('fichajes.store'));
        $this->travel(5)->minutes();
        $this->actingAs($this->empleado)->post(route('fichajes.store'));

        $this->assertSame([], IntegridadRegistro::verificar($this->empresa->id)['problemas']);
    }
}
