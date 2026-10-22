<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * El DNI/NIE es único dentro de cada empresa, no en toda la app: la misma persona puede
 * trabajar en dos empresas clientes.
 */
class DniVariasEmpresasTest extends TestCase
{
    use RefreshDatabase;

    private function empleado(Empresa $empresa, string $password, array $extra = []): User
    {
        return User::factory()->for($empresa)->create([
            'dni_nie' => '12345678Z',
            'password' => Hash::make($password),
            ...$extra,
        ]);
    }

    public function test_dos_empresas_pueden_dar_de_alta_el_mismo_dni(): void
    {
        $this->empleado(Empresa::factory()->create(), 'ClaveDeA123');

        $adminB = User::factory()->adminEmpresa()->for(Empresa::factory())->create();

        $this->actingAs($adminB)
            ->post(route('admin.empleados.store'), ['name' => 'Ana', 'dni_nie' => '12345678Z'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.empleados.index'));

        $this->assertSame(2, User::where('dni_nie', '12345678Z')->count());
    }

    public function test_en_la_misma_empresa_el_dni_no_se_repite(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $this->empleado($empresa, 'ClaveDeA123');

        $this->actingAs($admin)
            ->post(route('admin.empleados.store'), ['name' => 'Otra Ana', 'dni_nie' => '12345678Z'])
            ->assertSessionHasErrors('dni_nie');
    }

    public function test_editar_un_empleado_conserva_su_propio_dni(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = $this->empleado($empresa, 'ClaveDeA123');

        $this->actingAs($admin)
            ->patch(route('admin.empleados.update', $empleado), ['name' => 'Ana Editada', 'dni_nie' => '12345678Z'])
            ->assertSessionHasNoErrors();
    }

    public function test_con_el_dni_en_dos_empresas_entra_en_la_de_su_contrasena(): void
    {
        $this->empleado(Empresa::factory()->create(), 'ClaveDeA123');
        $deB = $this->empleado(Empresa::factory()->create(), 'ClaveDeB456');

        $this->post(route('login'), ['login' => '12345678z', 'password' => 'ClaveDeB456']);

        $this->assertAuthenticatedAs($deB);
    }

    public function test_si_la_contrasena_coincide_en_las_dos_se_le_explica_que_hacer(): void
    {
        $this->empleado(Empresa::factory()->create(), 'LaMisma123');
        $this->empleado(Empresa::factory()->create(), 'LaMisma123');

        $this->post(route('login'), ['login' => '12345678Z', 'password' => 'LaMisma123'])
            ->assertSessionHasErrors(['login' => 'Tu DNI está dado de alta en varias empresas con la misma contraseña. Pide a una de ellas que te dé una contraseña nueva, o entra con tu email.']);

        $this->assertGuest();
    }

    public function test_la_cuenta_de_baja_o_de_una_empresa_desactivada_no_cuenta(): void
    {
        $this->empleado(Empresa::factory()->create(), 'LaMisma123', ['activo' => false]);
        $this->empleado(Empresa::factory()->create(['activa' => false]), 'LaMisma123');
        $activa = $this->empleado(Empresa::factory()->create(), 'LaMisma123');

        $this->post(route('login'), ['login' => '12345678Z', 'password' => 'LaMisma123']);

        $this->assertAuthenticatedAs($activa);
    }

    public function test_contrasena_incorrecta_no_entra(): void
    {
        $this->empleado(Empresa::factory()->create(), 'ClaveDeA123');

        $this->post(route('login'), ['login' => '12345678Z', 'password' => 'otra'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }
}
