<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EmpleadosBajaTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_existe_ninguna_ruta_delete_de_empleados(): void
    {
        $tieneRutaDelete = collect(Route::getRoutes())
            ->contains(fn ($route) => in_array('DELETE', $route->methods())
                && str_starts_with($route->uri(), 'admin/empleados'));

        $this->assertFalse($tieneRutaDelete);
    }

    public function test_desactivar_empleado_no_borra_su_fila_ni_sus_fichajes(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->adminEmpresa()->for($empresa)->create();
        $empleado = User::factory()->for($empresa)->create();

        $this->actingAs($admin)
            ->patch(route('admin.empleados.desactivar', $empleado))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $empleado->id, 'activo' => false]);
    }

    public function test_empleado_inactivo_no_puede_iniciar_sesion(): void
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->inactivo()->for($empresa)->create([
            'email' => 'baja@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post(route('login'), [
            'login' => 'baja@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }
}
