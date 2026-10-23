<?php

namespace App\Notifications;

use App\Models\Nomina;
use App\Support\Avisos;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al empleado: tiene una nómina nueva. El correo no lleva el PDF ni
 * ningún dato salarial: solo un enlace para entrar y descargarla.
 */
class NominaDisponible extends Notification implements ShouldQueue
{
    // En cola si hay un worker (QUEUE_CONNECTION=database/redis); con 'sync' se envía al momento.
    use Queueable;

    /**
     * Nómina recién subida para el empleado.
     */
    public function __construct(protected Nomina $nomina) {}

    /** Solo por correo (no se guarda en la base de datos). */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Correo al empleado avisando de que tiene una nómina nueva (sin adjuntarla: se
     * descarga entrando).
     */
    public function toMail(object $notifiable): MailMessage
    {
        return Avisos::mensaje($notifiable)
            ->subject('Tienes una nómina nueva')
            ->line('Ya tienes disponible tu nómina de '.$this->nomina->titulo().'.')
            ->action('Ver mis nóminas', route('nominas.mis'))
            ->salutation('— Achrono');
    }
}
