<?php

namespace App\Notifications;

use App\Models\Nomina;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al empleado: tiene una nómina nueva. El correo no lleva el PDF ni
 * ningún dato salarial: solo un enlace para entrar y descargarla.
 */
class NominaDisponible extends Notification
{
    public function __construct(protected Nomina $nomina) {}

    /** Solo por correo (no se guarda en la base de datos). */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tienes una nómina nueva')
            ->greeting('Hola, '.$notifiable->name)
            ->line('Ya tienes disponible tu nómina de '.$this->nomina->titulo().'.')
            ->action('Ver mis nóminas', route('nominas.mis'))
            ->salutation('— Achrono');
    }
}
