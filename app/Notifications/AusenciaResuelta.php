<?php

namespace App\Notifications;

use App\Models\Ausencia;
use App\Support\Avisos;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al empleado: su solicitud de ausencia ha sido aprobada o rechazada
 * (con el motivo, si el admin lo escribió).
 */
class AusenciaResuelta extends Notification
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
        $aprobada = $a->estado === 'aprobada';
        $rango = $a->fecha_inicio->format('d/m/Y').' al '.$a->fecha_fin->format('d/m/Y');

        $mensaje = Avisos::mensaje($notifiable)
            ->subject($aprobada ? 'Tu solicitud ha sido aprobada' : 'Tu solicitud ha sido rechazada')
            ->line($aprobada
                ? "Tu solicitud del {$rango} ha sido aprobada."
                : "Tu solicitud del {$rango} ha sido rechazada.");

        if (! $aprobada && $a->motivo_rechazo) {
            $mensaje->line('Motivo: '.$a->motivo_rechazo);
        }

        return $mensaje
            ->action('Ver mis ausencias', route('ausencias.mis'))
            ->salutation('— Achrono');
    }
}
