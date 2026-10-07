<?php

namespace App\Notifications;

use App\Services\IncidenciasCalculador;
use App\Support\Avisos;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Resumen diario al admin de empresa con las incidencias de hoy (retrasos,
 * faltas de fichaje, jornadas sin cerrar, horas de más). Solo se envía si hay
 * alguna.
 */
class ResumenIncidencias extends Notification
{
    /** @param  Collection<int, array>  $incidencias  Salida de IncidenciasCalculador::delDia(). */
    public function __construct(protected Collection $incidencias) {}

    /** Solo por correo (no se guarda en la base de datos). */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mensaje = Avisos::mensaje($notifiable)
            ->subject('Incidencias de hoy ('.$this->incidencias->count().')')
            ->line('Estas son las incidencias de control horario detectadas hoy:');

        foreach ($this->incidencias as $incidencia) {
            $etiqueta = IncidenciasCalculador::ETIQUETAS[$incidencia['tipo']][0] ?? $incidencia['tipo'];
            $mensaje->line('• '.$incidencia['empleado']->name.' — '.$etiqueta.': '.$incidencia['detalle']);
        }

        return $mensaje
            ->action('Ver incidencias', route('admin.incidencias.index'))
            ->salutation('— Achrono');
    }
}
