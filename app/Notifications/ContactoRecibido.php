<?php

namespace App\Notifications;

use App\Models\Contacto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo al equipo (info@) cuando alguien rellena el formulario de contacto.
 * Lleva "responder a" con el correo de quien escribe, para contestarle directamente.
 */
class ContactoRecibido extends Notification implements ShouldQueue
{
    // En cola si hay un worker (QUEUE_CONNECTION=database/redis); con 'sync' se envía al momento.
    use Queueable;

    /**
     * Mensaje que alguien ha enviado desde el formulario de contacto.
     */
    public function __construct(protected Contacto $contacto) {}

    /** Solo por correo (no se guarda en la base de datos). */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Correo al buzón de soporte con los datos y el mensaje; "Responder" va a quien
     * escribió.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $c = $this->contacto;

        return (new MailMessage)
            ->replyTo($c->email, $c->nombre)
            ->subject(($c->esUrgente() ? '[URGENTE] ' : '').'Contacto web: '.$c->asuntoTexto())
            ->line('Nuevo mensaje desde el formulario de contacto.')
            ->line('Nombre: '.$c->nombre)
            ->line('Correo: '.$c->email)
            ->line('Empresa: '.($c->empresa ?: '—'))
            ->line('Motivo: '.$c->asuntoTexto())
            ->line('Mensaje:')
            ->line($c->mensaje)
            ->salutation('— Achrono');
    }
}
