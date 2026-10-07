<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProbarCorreoTest extends TestCase
{
    public function test_con_el_mailer_log_avisa_de_que_no_se_envia_nada(): void
    {
        config(['mail.default' => 'log']);

        $this->artisan('correo:probar')
            ->expectsOutputToContain('El mailer es "log"')
            ->assertFailed();
    }

    public function test_envia_el_correo_de_prueba_al_correo_de_contacto_por_defecto(): void
    {
        config(['mail.default' => 'array']);

        $this->artisan('correo:probar')
            ->expectsOutputToContain('Destino:  info@alsesoftware.com')
            ->expectsOutputToContain('Enviado.')
            ->assertSuccessful();
    }

    public function test_si_el_smtp_falla_muestra_el_motivo_y_no_la_contrasena(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.username' => 'no-reply@achrono.es',
            'mail.mailers.smtp.password' => 'secreto-que-no-debe-salir',
        ]);

        $this->artisan('correo:probar')
            ->expectsOutputToContain('NO SE PUDO ENVIAR')
            ->doesntExpectOutputToContain('secreto-que-no-debe-salir')
            ->assertFailed();
    }

    public function test_se_puede_lanzar_desde_la_ruta_de_despliegue(): void
    {
        config(['deploy.token' => 'el-token-correcto', 'mail.default' => 'array']);

        $this->get('/deploy/ejecutar?token=el-token-correcto&cmd=correo:probar')
            ->assertOk()
            ->assertSee('Enviado.');
    }
}
