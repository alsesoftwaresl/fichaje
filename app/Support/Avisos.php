<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Envío de avisos por correo sin riesgo para la petición que los provoca: si el
 * servidor de correo falla, se anota en el log y la acción (aprobar una ausencia,
 * subir una nómina...) sigue adelante igualmente.
 */
class Avisos
{
    /**
     * Envía la notificación a cada usuario que tenga email. Los empleados que
     * fichan solo con DNI (sin email) se omiten sin error.
     *
     * @param  iterable<User>  $usuarios
     */
    public static function enviar(iterable $usuarios, Notification $notificacion): void
    {
        foreach ($usuarios as $usuario) {
            if (! $usuario->email) {
                continue;
            }

            try {
                $usuario->notify($notificacion);
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar el aviso '.class_basename($notificacion).' a '.$usuario->id.': '.$e->getMessage());
            }
        }
    }

    /**
     * Admins activos de una empresa (los destinatarios de los avisos de gestión).
     *
     * @return Collection<int, User>
     */
    public static function adminsDeEmpresa(int $empresaId): Collection
    {
        return User::deEmpresa($empresaId)
            ->where('rol', 'admin_empresa')
            ->where('activo', true)
            ->whereNotNull('email')
            ->get();
    }
}
