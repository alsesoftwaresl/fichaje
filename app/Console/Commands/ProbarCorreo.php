<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Envía un correo de prueba para comprobar la configuración SMTP del servidor.
 * Sin argumento escribe al correo de contacto de la empresa (LEGAL_EMAIL), así que
 * se puede lanzar sin riesgo desde /deploy/ejecutar. Si falla, muestra el motivo.
 */
class ProbarCorreo extends Command
{
    protected $signature = 'correo:probar {destino? : Correo al que enviar (por defecto, el de contacto)}';

    protected $description = 'Envía un correo de prueba y muestra la configuración SMTP (sin contraseña)';

    public function handle(): int
    {
        $destino = $this->argument('destino') ?: config('legal.titular.email');

        $this->line('Mailer:   '.config('mail.default'));
        $this->line('Servidor: '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').' (esquema: '.(config('mail.mailers.smtp.scheme') ?: 'automático').')');
        $this->line('Usuario:  '.(config('mail.mailers.smtp.username') ?: '(sin usuario)'));
        $this->line('Remitente: '.config('mail.from.address'));
        $this->line('Destino:  '.$destino);

        if (config('mail.default') === 'log') {
            $this->warn('El mailer es "log": no se envía nada, solo se escribe en storage/logs. Pon MAIL_MAILER=smtp en el .env.');

            return self::FAILURE;
        }

        try {
            Mail::raw(
                "Este es un correo de prueba de ".config('app.name').".\n\nSi lo recibes, el envío por SMTP funciona.",
                fn ($mensaje) => $mensaje->to($destino)->subject('Prueba de correo — '.config('app.name'))
            );
        } catch (\Throwable $e) {
            $this->error('NO SE PUDO ENVIAR: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Enviado. Revisa la bandeja de '.$destino.' (y la carpeta de spam).');

        return self::SUCCESS;
    }
}
