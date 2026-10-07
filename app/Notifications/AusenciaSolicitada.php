<?php

namespace App\Notifications;

use App\Models\Ausencia;
use App\Support\Avisos;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al admin de empresa: un empleado ha pedido una ausencia y espera su
 * aprobación.
 */
class AusenciaSolicitada extends Notification
{
    public function __construct(protected Ausencia $ausencia) {}

    /** Solo por correo (no se guarda en la base de datos). */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->ausencia;
        $tipo = match ($a->tipo) {
            'vacaciones' => 'vacaciones',
            'baja_medica' => 'una baja médica',
            default => 'una ausencia',
        };

        $mensaje = Avisos::mensaje($notifiable)
            ->subject($a->usuario->name.' ha solicitado '.$tipo)
            ->line($a->usuario->name.' ha solicitado '.$tipo.' del '.$a->fecha_inicio->format('d/m/Y').' al '.$a->fecha_fin->format('d/m/Y').'.');

        if ($a->motivo) {
            $mensaje->line('Motivo: '.$a->motivo);
        }

        return $mensaje
            ->action('Revisar solicitud', route('admin.ausencias.index', ['estado' => 'pendiente']))
            ->salutation('— Achrono');
    }
}
