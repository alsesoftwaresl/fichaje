<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\LegalAceptacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistroPublicoTest extends TestCase
{
    use RefreshDatabase;

    protected function datosRegistro(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Mi Empresa SL',
            'email_contacto' => 'contacto@mi-empresa.test',
            'admin_name' => 'Persona Admin',
            'admin_email' => 'admin@mi-empresa.test',
            'admin_password' => 'password-seguro',
            'admin_password_confirmation' => 'password-seguro',
        ], $overrides);
    }

    public function test_la_pagina_de_registro_se_puede_ver_sin_iniciar_sesion(): void
    {
        $this->get(route('registro.create'))->assertOk();
    }

    public function test_cualquiera_puede_registrar_su_empresa_sin_ser_super_admin(): void
    {
        $response = $this->post(route('registro.store'), $this->datosRegistro([
            'acepta_terminos' => '1',
            'acepta_encargo' => '1',
        ]));

        $empresa = Empresa::where('nombre', 'Mi Empresa SL')->firstOrFail();

        // Aterriza en la pantalla de verificación, no en Facturación — hasta
        // que no confirme el email no puede hacer nada más (ver
        // RequireEmailVerificado).
        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'empresa_id' => $empresa->id,
            'email' => 'admin@mi-empresa.test',
            'rol' => 'admin_empresa',
            'email_verified_at' => null,
        ]);
        $this->assertSame(2, LegalAceptacion::where('empresa_id', $empresa->id)->count());
    }

    public function test_el_registro_sin_aceptar_legales_falla(): void
    {
        $this->post(route('registro.store'), $this->datosRegistro())
            ->assertSessionHasErrors(['acepta_terminos', 'acepta_encargo']);

        $this->assertDatabaseCount('empresas', 0);
        $this->assertGuest();
    }

    public function test_no_se_puede_registrar_con_un_email_ya_usado(): void
    {
        $this->post(route('registro.store'), $this->datosRegistro([
            'acepta_terminos' => '1',
            'acepta_encargo' => '1',
        ]))->assertRedirect(route('verification.notice'));

        $this->postJson(route('registro.store'), $this->datosRegistro([
            'nombre' => 'Otra Empresa SL',
            'acepta_terminos' => '1',
            'acepta_encargo' => '1',
        ]))->assertJsonValidationErrors(['admin_email']);

        $this->assertSame(1, Empresa::count());
    }
}
