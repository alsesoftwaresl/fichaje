<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\LegalAceptacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalAceptacionTest extends TestCase
{
    use RefreshDatabase;

    protected function datosEmpresa(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Empresa de Prueba SL',
            'email_contacto' => 'contacto@empresa-prueba.test',
            'admin_name' => 'Admin Prueba',
            'admin_email' => 'admin@empresa-prueba.test',
            'admin_password' => 'password-seguro',
            'admin_password_confirmation' => 'password-seguro',
        ], $overrides);
    }

    public function test_alta_de_empresa_sin_aceptar_legales_falla(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.empresas.store'), $this->datosEmpresa())
            ->assertSessionHasErrors(['acepta_terminos', 'acepta_encargo']);

        $this->assertDatabaseCount('empresas', 0);
    }

    public function test_alta_de_empresa_con_aceptacion_crea_empresa_admin_y_registros_legales(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.empresas.store'), $this->datosEmpresa([
                'acepta_terminos' => '1',
                'acepta_encargo' => '1',
            ]))
            ->assertRedirect();

        $empresa = Empresa::where('nombre', 'Empresa de Prueba SL')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'empresa_id' => $empresa->id,
            'email' => 'admin@empresa-prueba.test',
            'rol' => 'admin_empresa',
        ]);

        $this->assertSame(2, LegalAceptacion::where('empresa_id', $empresa->id)->count());
        $this->assertDatabaseHas('legal_aceptaciones', [
            'empresa_id' => $empresa->id,
            'documento' => 'terminos_condiciones',
        ]);
        $this->assertDatabaseHas('legal_aceptaciones', [
            'empresa_id' => $empresa->id,
            'documento' => 'encargo_tratamiento',
        ]);
    }
}
