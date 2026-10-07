<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorreosEnEspanolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('es');
    }

    public function test_el_correo_de_restablecer_contrasena_sale_en_espanol(): void
    {
        $usuario = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create(['name' => 'Ana']);

        $correo = (new ResetPassword('token-de-prueba'))->toMail($usuario);
        $html = $correo->render()->toHtml();

        $this->assertSame('Restablecer contraseña', $correo->subject);
        $this->assertStringContainsString('Hola, Ana', $html);
        $this->assertStringContainsString('Empresa:', $html);
        $this->assertStringContainsString('Saludos,', $html);
        $this->assertStringContainsString('un producto de ALSE SOFTWARE, S.L.', $html);
        $this->assertStringContainsString('images/logo-claro.png', $html);
        $this->assertStringContainsString('caduca en 60 minutos', $html);
        $this->assertStringContainsString('Si no puedes pulsar el botón', $html);

        foreach (['Hello!', 'Regards,', 'If you did not request', 'All rights reserved', 'Laravel'] as $ingles) {
            $this->assertStringNotContainsString($ingles, $html);
        }
    }

    public function test_el_correo_de_verificacion_sale_en_espanol(): void
    {
        $usuario = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create();

        $correo = (new VerifyEmail)->toMail($usuario);
        $html = $correo->render()->toHtml();

        $this->assertSame('Verifica tu correo electrónico', $correo->subject);
        $this->assertStringContainsString('Pulsa el botón de abajo', $html);
        $this->assertStringNotContainsString('Please click the button', $html);
    }
}
