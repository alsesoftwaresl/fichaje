<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use App\Notifications\ContactoRecibido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Formulario público de contacto y atención al cliente. El mensaje se guarda en
 * la base de datos (para que no se pierda) y se avisa por correo a info@.
 */
class ContactoController extends Controller
{
    /**
     * Muestra el formulario. Con ?asunto=urgente llega ya marcado como incidencia urgente.
     */
    public function create(Request $request): View
    {
        return view('contacto', [
            'asuntos' => Contacto::ASUNTOS,
            'asuntoInicial' => array_key_exists($request->query('asunto', ''), Contacto::ASUNTOS) ? $request->query('asunto') : null,
        ]);
    }

    /**
     * Valida y registra el mensaje. El campo oculto "web" es una trampa para bots:
     * si viene relleno se responde "enviado" pero no se guarda nada.
     */
    public function store(Request $request): RedirectResponse
    {
        // El formulario también está en la portada: se vuelve allí, a su sección.
        $destino = $request->input('origen') === 'home' ? route('home').'#atencion' : route('contacto.create');

        try {
            $data = $request->validate([
                'nombre' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
                'empresa' => ['nullable', 'string', 'max:160'],
                'asunto' => ['required', 'in:'.implode(',', array_keys(Contacto::ASUNTOS))],
                'mensaje' => ['required', 'string', 'min:10', 'max:4000'],
                'acepta_privacidad' => ['accepted'],
            ], [
                'acepta_privacidad.accepted' => 'Tienes que aceptar el tratamiento de tus datos para poder contestarte.',
                'mensaje.min' => 'Cuéntanos un poco más (al menos 10 caracteres).',
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo($destino);
        }

        if ($request->filled('web')) {
            return redirect($destino)->with('enviado', true);
        }

        $contacto = Contacto::create([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'empresa' => $data['empresa'] ?? null,
            'asunto' => $data['asunto'],
            'mensaje' => $data['mensaje'],
            'ip_address' => $request->ip(),
        ]);

        // Si el correo falla, el mensaje ya está guardado y se ve en el panel.
        try {
            Notification::route('mail', config('legal.titular.email'))->notify(new ContactoRecibido($contacto));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el aviso de contacto '.$contacto->id.': '.$e->getMessage());
        }

        return redirect($destino)->with('enviado', true);
    }
}
