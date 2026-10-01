<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioEmpleadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_puede_dar_de_alta_un_empleado_con_horario(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)->post(route('admin.empleados.store'), [
            'name' => 'Con Horario',
            'dni_nie' => '12345678z',
            'email' => 'conhorario@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'hora_entrada_esperada' => '09:00',
            'hora_salida_esperada' => '17:00',
            // Strings a propósito: así es como llegan de verdad los
            // checkboxes de un formulario HTML real, no como enteros PHP.
            'dias_laborables' => ['1', '2', '3', '4', '5'],
        ])->assertRedirect(route('admin.empleados.index'));

        $empleado = User::where('email', 'conhorario@example.com')->first();

        $this->assertNotNull($empleado);
        $this->assertSame('12345678Z', $empleado->dni_nie);
        $this->assertSame('09:00:00', $empleado->hora_entrada_esperada);
        $this->assertSame('17:00:00', $empleado->hora_salida_esperada);
        $this->assertSame([1, 2, 3, 4, 5], $empleado->dias_laborables);
        $this->assertTrue($empleado->tieneHorario());

        // La comparación estricta de trabajaEnDia() debe funcionar con
        // enteros de verdad, no con los strings que mandó el formulario.
        $lunesQueViene = now()->next(\Illuminate\Support\Carbon::MONDAY);
        $this->assertTrue($empleado->trabajaEnDia($lunesQueViene));
    }

    public function test_el_admin_puede_editar_el_horario_despues(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create(['name' => 'Juan', 'dni_nie' => '87654321X']);

        $this->actingAs($admin)
            ->patch(route('admin.empleados.update', $empleado), [
                'name' => 'Juan',
                'dni_nie' => $empleado->dni_nie,
                'email' => $empleado->email,
                'hora_entrada_esperada' => '08:00',
                'hora_salida_esperada' => '16:00',
                'dias_laborables' => [1, 2, 3],
            ])
            ->assertRedirect(route('admin.empleados.index'));

        $empleado->refresh();
        $this->assertSame('08:00:00', $empleado->hora_entrada_esperada);
        $this->assertSame([1, 2, 3], $empleado->dias_laborables);
    }

    public function test_se_puede_dar_de_alta_un_empleado_solo_con_dni_sin_email_ni_contrasena(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();

        $this->actingAs($admin)->post(route('admin.empleados.store'), [
            'name' => 'Solo Kiosco',
            'dni_nie' => '11223344b',
        ])->assertRedirect(route('admin.empleados.index'));

        $empleado = User::where('dni_nie', '11223344B')->first();

        $this->assertNotNull($empleado);
        $this->assertNull($empleado->email);
        $this->assertNotNull($empleado->password);
    }

    public function test_admin_no_puede_editar_empleado_de_otra_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $adminA = User::factory()->adminEmpresa()->for($empresaA)->create();
        $empleadoB = User::factory()->for($empresaB)->create();

        $this->actingAs($adminA)
            ->get(route('admin.empleados.edit', $empleadoB))
            ->assertNotFound();
    }
}
