<?php

namespace Tests\Feature;

use App\Models\Contacto;
use App\Models\Empresa;
use App\Models\User;
use App\Notifications\ContactoRecibido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactoTest extends TestCase
{
    use RefreshDatabase;

    protected function datos(array $extra = []): array
    {
        return $extra + [
            'nombre' => 'Marta Ruiz',
            'email' => 'marta@taller.test',
            'empresa' => 'Taller Ruiz',
            'asunto' => 'soporte',
            'mensaje' => 'No consigo que mis empleados fichen en la tablet.',
            'acepta_privacidad' => '1',
        ];
    }

    public function test_la_pagina_de_contacto_muestra_el_correo_y_la_atencion_en_espanol(): void
    {
        $this->get('/contacto')
            ->assertOk()
            ->assertSee('info@achrono.es')
            ->assertSee('atención al cliente en español', false)
            ->assertSee('¿Es una emergencia?')
            ->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_el_enlace_con_asunto_urgente_llega_preseleccionado(): void
    {
        $this->get('/contacto?asunto=urgente')->assertOk()->assertSee('value="urgente" selected', false);
    }

    public function test_enviar_el_formulario_guarda_el_mensaje_y_avisa_a_info(): void
    {
        Notification::fake();

        $this->post('/contacto', $this->datos())->assertRedirect(route('contacto.create'))->assertSessionHas('enviado');

        $contacto = Contacto::firstOrFail();
        $this->assertSame('marta@taller.test', $contacto->email);
        $this->assertSame('soporte', $contacto->asunto);
        $this->assertNull($contacto->leido_en);

        Notification::assertSentOnDemand(ContactoRecibido::class, fn ($n, $canales, $notifiable) => $notifiable->routes['mail'] === 'info@achrono.es');
    }

    public function test_un_mensaje_urgente_se_marca_en_el_asunto_del_correo(): void
    {
        $contacto = Contacto::create($this->datos(['asunto' => 'urgente']) + ['ip_address' => '127.0.0.1']);

        $correo = (new ContactoRecibido($contacto))->toMail(new \Illuminate\Notifications\AnonymousNotifiable);

        $this->assertStringStartsWith('[URGENTE]', $correo->subject);
        $this->assertSame([['marta@taller.test', 'Marta Ruiz']], $correo->replyTo);
    }

    public function test_si_falla_el_correo_el_mensaje_queda_guardado_igualmente(): void
    {
        Notification::shouldReceive('route')->andThrow(new \RuntimeException('SMTP caído'));

        $this->post('/contacto', $this->datos())->assertRedirect(route('contacto.create'))->assertSessionHas('enviado');

        $this->assertSame(1, Contacto::count());
    }

    public function test_validacion_de_campos_y_consentimiento(): void
    {
        $this->post('/contacto', $this->datos(['email' => 'no-es-un-correo', 'mensaje' => 'corto', 'asunto' => 'otra-cosa', 'acepta_privacidad' => null]))
            ->assertSessionHasErrors(['email', 'mensaje', 'asunto', 'acepta_privacidad']);

        $this->assertSame(0, Contacto::count());
    }

    public function test_el_campo_trampa_de_bots_descarta_el_mensaje_sin_avisar(): void
    {
        Notification::fake();

        $this->post('/contacto', $this->datos(['web' => 'http://spam.test']))->assertRedirect(route('contacto.create'))->assertSessionHas('enviado');

        $this->assertSame(0, Contacto::count());
        Notification::assertNothingSent();
    }

    public function test_el_formulario_tiene_limite_de_envios(): void
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/contacto', $this->datos())->assertRedirect();
        }

        $this->post('/contacto', $this->datos())->assertStatus(429);
    }

    public function test_el_super_admin_ve_y_marca_los_mensajes_y_los_demas_no(): void
    {
        $contacto = Contacto::create($this->datos(['asunto' => 'urgente']) + ['ip_address' => '127.0.0.1']);
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get(route('super-admin.mensajes.index'))
            ->assertOk()->assertSee('Marta Ruiz')->assertSee('Incidencia urgente');

        $this->actingAs($super)->patch(route('super-admin.mensajes.toggle', $contacto))->assertRedirect();
        $this->assertNotNull($contacto->fresh()->leido_en);

        $admin = User::factory()->adminEmpresa()->for(Empresa::factory()->create())->create();
        $this->actingAs($admin)->get(route('super-admin.mensajes.index'))->assertForbidden();
    }

    public function test_el_contacto_esta_en_el_sitemap_y_en_la_portada(): void
    {
        $this->get('/sitemap.xml')->assertSee(route('contacto.create'));

        $this->get('/')
            ->assertOk()
            ->assertSee('Atención personal, en español')
            ->assertSee('mailto:info@achrono.es', false)
            ->assertSee('"contactPoint"', false);
    }

    public function test_el_formulario_esta_en_la_portada_y_vuelve_a_ella_al_enviar(): void
    {
        Notification::fake();

        $this->get('/')->assertOk()->assertSee('action="'.route('contacto.store').'"', false)->assertSee('name="origen" value="home"', false);

        $this->post('/contacto', $this->datos(['origen' => 'home']))
            ->assertRedirect(route('home').'#atencion')
            ->assertSessionHas('enviado');

        $this->assertSame(1, Contacto::count());
    }

    public function test_si_falla_la_validacion_desde_la_portada_se_vuelve_a_su_seccion(): void
    {
        $this->post('/contacto', $this->datos(['origen' => 'home', 'email' => 'mal']))
            ->assertRedirect(route('home').'#atencion')
            ->assertSessionHasErrors('email');
    }
}

