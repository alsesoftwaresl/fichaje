<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AccesoWebEmpleadoTest extends TestCase
{
    use RefreshDatabase;

    protected function altaConAcceso(User $admin, array $extra = [])
    {
        return $this->actingAs($admin)->post(route('admin.empleados.store'), $extra + [
            'name' => 'Ana Lopez',
            'dni_nie' => '12345678Z',
            'dar_acceso' => '1',
        ]);
    }

    public function test_al_dar_de_alta_con_acceso_se_genera_una_contrasena_temporal_visible_una_vez(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create();

        $respuesta = $this->altaConAcceso($admin)->assertRedirect(route('admin.empleados.index'));

        $acceso = session('acceso_generado');
        $this->assertSame('12345678Z', $acceso['dni']);
        $this->assertSame(10, strlen($acceso['password']));

        $empleado = User::where('dni_nie', '12345678Z')->firstOrFail();
        $this->assertTrue($empleado->debe_cambiar_password);

        $this->get(route('admin.empleados.index'))->assertSee($acceso['password']);
        $this->get(route('admin.empleados.index'))->assertDontSee($acceso['password']); // ya no
    }

    public function test_el_empleado_entra_con_dni_y_contrasena_temporal_y_debe_elegir_la_suya(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create();
        $this->altaConAcceso($admin);
        $temporal = session('acceso_generado')['password'];
        Auth::logout();

        $this->post(route('login'), ['login' => '12345678z', 'password' => $temporal])
            ->assertRedirect(route('dashboard'));

        // Cualquier otra página lo manda a elegir contraseña...
        $this->get(route('fichajes.mis'))->assertRedirect(route('password.forzar'));
        $this->get(route('nominas.mis'))->assertRedirect(route('password.forzar'));
        $this->get(route('password.forzar'))->assertOk();

        // ...pero no puede quedarse con la temporal.
        $this->post(route('password.forzar.store'), ['password' => $temporal, 'password_confirmation' => $temporal])
            ->assertSessionHasErrors('password');

        $this->post(route('password.forzar.store'), ['password' => 'MiClaveNueva9', 'password_confirmation' => 'MiClaveNueva9'])
            ->assertRedirect(route('dashboard'));

        $this->get(route('fichajes.mis'))->assertOk();
        $this->assertFalse(User::where('dni_nie', '12345678Z')->first()->debe_cambiar_password);

        // La temporal ya no vale; la nueva sí.
        Auth::logout();
        $this->post(route('login'), ['login' => '12345678Z', 'password' => $temporal]);
        $this->assertGuest();
        $this->post(route('login'), ['login' => '12345678Z', 'password' => 'MiClaveNueva9']);
        $this->assertAuthenticated();
    }

    public function test_quien_debe_cambiar_la_contrasena_si_puede_cerrar_sesion(): void
    {
        $empleado = User::factory()->for(Empresa::factory()->create())->create(['debe_cambiar_password' => true]);

        $this->actingAs($empleado)->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_sin_marcar_dar_acceso_solo_ficha_por_pin(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create();

        $this->altaConAcceso($admin, ['dar_acceso' => '0']);

        $this->assertNull(session('acceso_generado'));
        $empleado = User::where('dni_nie', '12345678Z')->firstOrFail();
        $this->assertFalse($empleado->debe_cambiar_password);
        $this->assertNotNull($empleado->pin_hash);
    }

    public function test_si_el_admin_escribe_la_contrasena_tambien_se_obliga_a_cambiarla(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create();

        $this->altaConAcceso($admin, ['password' => 'Provisional123', 'password_confirmation' => 'Provisional123']);

        $this->assertNull(session('acceso_generado')); // el admin ya la conoce
        $this->assertTrue(User::where('dni_nie', '12345678Z')->first()->debe_cambiar_password);
    }

    public function test_el_admin_puede_generar_otra_contrasena_temporal_desde_la_lista(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create(['dni_nie' => '87654321X', 'password' => 'ClaveVieja123']);

        $this->actingAs($admin)->patch(route('admin.empleados.generar-acceso', $empleado))
            ->assertRedirect(route('admin.empleados.index'));

        $nueva = session('acceso_generado')['password'];
        $empleado->refresh();
        $this->assertTrue($empleado->debe_cambiar_password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($nueva, $empleado->password));
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('ClaveVieja123', $empleado->password));
    }

    public function test_no_se_puede_generar_acceso_a_un_empleado_de_otra_empresa(): void
    {
        $admin = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create();
        $ajeno = User::factory()->for(Empresa::factory()->create())->create();

        $this->actingAs($admin)->patch(route('admin.empleados.generar-acceso', $ajeno))->assertNotFound();
    }
}
