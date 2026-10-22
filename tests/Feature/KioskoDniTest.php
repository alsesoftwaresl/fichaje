<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class KioskoDniTest extends TestCase
{
    use RefreshDatabase;

    protected function empresaConEmpleado(string $modo, string $dni = '12345678Z'): array
    {
        $empresa = Empresa::factory()->create();
        $empresa->forceFill(['kiosko_modo' => $modo])->save();

        $empleado = User::factory()->for($empresa)->create(['name' => 'Ana García', 'dni_nie' => $dni]);
        $pin = $empleado->generarNuevoPin();

        return [$empresa->fresh(), $empleado, $pin];
    }

    protected function fichar(Empresa $empresa, array $datos)
    {
        return $this->post(route('kiosko.fichar', $empresa->kiosko_token), $datos);
    }

    protected function totalFichajes(Empresa $empresa): int
    {
        Tenant::set($empresa->id);
        $total = Fichaje::count();
        Tenant::clear();

        return $total;
    }

    public function test_por_defecto_el_kiosco_solo_admite_pin_y_rechaza_el_dni(): void
    {
        [$empresa, , $pin] = $this->empresaConEmpleado('pin');

        $this->fichar($empresa, ['dni' => '12345678Z'])->assertSessionHasErrors('dni');
        $this->assertSame(0, $this->totalFichajes($empresa));

        $this->fichar($empresa, ['pin' => $pin])->assertSessionHas('resultado');
        $this->assertSame(1, $this->totalFichajes($empresa));
    }

    public function test_en_modo_dni_ficha_con_el_dni_sin_importar_mayusculas_ni_separadores(): void
    {
        [$empresa] = $this->empresaConEmpleado('dni');

        $this->fichar($empresa, ['dni' => ' 12.345.678-z '])
            ->assertRedirect(route('kiosko.show', $empresa->kiosko_token))
            ->assertSessionHas('resultado', fn ($r) => $r['nombre'] === 'Ana García' && $r['tipo'] === 'entrada');

        // El siguiente fichaje del mismo empleado es la salida.
        $this->fichar($empresa, ['dni' => '12345678Z'])
            ->assertSessionHas('resultado', fn ($r) => $r['tipo'] === 'salida');

        $this->assertSame(2, $this->totalFichajes($empresa));
    }

    public function test_funciona_tambien_con_un_nie(): void
    {
        [$empresa] = $this->empresaConEmpleado('dni', 'X1234567L');

        $this->fichar($empresa, ['dni' => 'x1234567l'])->assertSessionHas('resultado');
    }

    public function test_en_modo_dni_el_pin_no_vale(): void
    {
        [$empresa, , $pin] = $this->empresaConEmpleado('dni');

        $this->fichar($empresa, ['pin' => $pin])->assertSessionHasErrors('pin');
        $this->assertSame(0, $this->totalFichajes($empresa));
    }

    public function test_en_modo_ambos_valen_las_dos_formas(): void
    {
        [$empresa, , $pin] = $this->empresaConEmpleado('ambos');

        $this->fichar($empresa, ['pin' => $pin])->assertSessionHas('resultado');
        $this->fichar($empresa, ['dni' => '12345678Z'])->assertSessionHas('resultado');

        $this->assertSame(2, $this->totalFichajes($empresa));
    }

    public function test_un_dni_desconocido_o_mal_escrito_no_ficha_y_no_da_pistas(): void
    {
        [$empresa] = $this->empresaConEmpleado('dni');

        foreach (['99999999R', '1234', 'ABCDEFGHI', '12345678'] as $dni) {
            $this->fichar($empresa, ['dni' => $dni])
                ->assertSessionHasErrors(['dni' => 'No hemos encontrado ese DNI o NIE.']);
        }

        $this->assertSame(0, $this->totalFichajes($empresa));
    }

    public function test_un_empleado_dado_de_baja_no_puede_fichar_con_dni(): void
    {
        [$empresa, $empleado] = $this->empresaConEmpleado('dni');
        $empleado->update(['activo' => false]);

        $this->fichar($empresa, ['dni' => '12345678Z'])->assertSessionHasErrors('dni');
        $this->assertSame(0, $this->totalFichajes($empresa));
    }

    public function test_el_dni_de_otra_empresa_no_ficha_en_este_kiosco(): void
    {
        [$empresaA] = $this->empresaConEmpleado('dni', '12345678Z');
        [$empresaB] = $this->empresaConEmpleado('dni', '87654321X');

        $this->fichar($empresaA, ['dni' => '87654321X'])->assertSessionHasErrors('dni');
        $this->assertSame(0, $this->totalFichajes($empresaB));
    }

    public function test_tras_muchos_fallos_seguidos_se_frena_el_kiosco(): void
    {
        [$empresa] = $this->empresaConEmpleado('dni');
        RateLimiter::clear('kiosko-dni:'.$empresa->id.'|127.0.0.1');

        for ($i = 0; $i < 10; $i++) {
            $this->fichar($empresa, ['dni' => '99999999R']);
        }

        // Ni siquiera el DNI correcto pasa mientras dure el bloqueo.
        $this->fichar($empresa, ['dni' => '12345678Z'])
            ->assertSessionHasErrors(['dni' => 'Demasiados intentos. Espera unos minutos y vuelve a probar.']);
        $this->assertSame(0, $this->totalFichajes($empresa));
    }

    public function test_la_pantalla_del_kiosco_cambia_segun_el_modo(): void
    {
        [$soloPin] = $this->empresaConEmpleado('pin');
        $this->get(route('kiosko.show', $soloPin->kiosko_token))
            ->assertOk()->assertSee('Introduce tu PIN para fichar')->assertDontSee('Escribe tu DNI');

        [$soloDni] = $this->empresaConEmpleado('dni', '11111111H');
        $this->get(route('kiosko.show', $soloDni->kiosko_token))
            ->assertOk()->assertSee('Escribe tu DNI o NIE para fichar')->assertDontSee('Introduce tu PIN para fichar');

        [$ambos] = $this->empresaConEmpleado('ambos', '22222222J');
        $this->get(route('kiosko.show', $ambos->kiosko_token))
            ->assertOk()->assertSee('¿No recuerdas tu PIN? Ficha con tu DNI')->assertSee('Prefiero usar mi PIN');
    }

    public function test_el_admin_cambia_el_modo_del_kiosco_de_su_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $otra = Empresa::factory()->create();

        $this->actingAs($admin)->patch(route('admin.kiosko.modo'), ['kiosko_modo' => 'ambos'])->assertRedirect();

        $this->assertSame('ambos', $empresa->fresh()->kiosko_modo);
        $this->assertSame('pin', $otra->fresh()->kiosko_modo);

        $this->actingAs($admin)->patch(route('admin.kiosko.modo'), ['kiosko_modo' => 'huella'])
            ->assertSessionHasErrors('kiosko_modo');
    }

    public function test_un_empleado_no_puede_cambiar_el_modo_y_el_panel_lo_ofrece_al_admin(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($empleado)->patch(route('admin.kiosko.modo'), ['kiosko_modo' => 'dni'])->assertForbidden();
        $this->assertSame('pin', $empresa->fresh()->kiosko_modo);

        $this->actingAs($admin)->get(route('admin.empleados.index'))
            ->assertOk()->assertSee('¿Cómo ficha la gente en el kiosco?')->assertSee('podría fichar por él');
    }
}
