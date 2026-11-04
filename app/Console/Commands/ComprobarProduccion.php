<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;

/**
 * Revisa que la configuración del servidor es la correcta para producción (.env, base de
 * datos, migraciones, correo, Stripe...) y dice qué falta. Nunca muestra claves ni
 * contraseñas: solo si están puestas y si tienen el valor esperado. Se puede lanzar sin
 * terminal con /deploy/ejecutar?cmd=produccion:comprobar.
 */
class ComprobarProduccion extends Command
{
    protected $signature = 'produccion:comprobar';

    protected $description = 'Comprueba la configuración de producción sin mostrar claves';

    /** Número de comprobaciones que fallan (las de aviso no cuentan). */
    protected int $fallos = 0;

    /**
     * Hace todas las comprobaciones y termina con error si alguna falla.
     */
    public function handle(Migrator $migrator): int
    {
        $this->line('Comprobación de producción ('.now()->format('d/m/Y H:i').')');
        $this->newLine();

        $this->comprobar(app()->isProduction(), 'APP_ENV=production', 'APP_ENV es "'.app()->environment().'": pon APP_ENV=production');
        $this->comprobar(! config('app.debug'), 'APP_DEBUG=false', 'APP_DEBUG está activado: pon APP_DEBUG=false (si no, los errores enseñan detalles internos)');
        $this->comprobar(str_starts_with((string) config('app.url'), 'https://'), 'APP_URL empieza por https://', 'APP_URL no es https: pon APP_URL=https://achrono.es');
        $this->comprobar(strlen((string) config('app.key')) > 40, 'APP_KEY puesta', 'Falta APP_KEY');

        $cola = config('queue.default');
        $this->comprobar($cola === 'sync', 'QUEUE_CONNECTION=sync (los correos salen al momento)', null, 'QUEUE_CONNECTION es "'.$cola.'": los correos salen con la tarea programada cada minuto; mejor QUEUE_CONNECTION=sync');

        $this->comprobar((bool) config('session.encrypt'), 'Sesiones cifradas', 'Pon SESSION_ENCRYPT=true');
        $this->comprobar((bool) config('session.secure'), 'Cookie de sesión solo por https', 'Pon SESSION_SECURE_COOKIE=true');

        $this->comprobar(config('mail.default') === 'smtp', 'Correo por SMTP', 'MAIL_MAILER no es smtp: los correos no saldrán');
        $this->comprobar(filled(config('mail.from.address')) && ! str_ends_with((string) config('mail.from.address'), 'example.com'), 'Remitente del correo: '.config('mail.from.address'), 'Pon MAIL_FROM_ADDRESS (p. ej. info@achrono.es)');

        try {
            DB::connection()->getPdo();
            $this->comprobar(true, 'Conexión a la base de datos', null);
        } catch (\Throwable) {
            $this->comprobar(false, '', 'No se puede conectar a la base de datos: revisa DB_*');
        }

        try {
            $pendientes = count(array_diff(
                array_keys($migrator->getMigrationFiles([database_path('migrations')])),
                $migrator->getRepository()->getRan()
            ));
            $this->comprobar($pendientes === 0, 'Migraciones al día', $pendientes.' migraciones pendientes: lanza cmd=migrate');
        } catch (\Throwable) {
            // Con un usuario de base de datos restringido puede no leer la tabla de migraciones.
            $this->comprobar(false, '', null, 'No se han podido comprobar las migraciones (permisos de la tabla migrations)');
        }

        $secreto = (string) config('cashier.secret');
        $this->comprobar(filled($secreto), 'Stripe configurado ('.(str_starts_with($secreto, 'sk_live_') ? 'modo REAL' : 'modo prueba').')', null, 'Falta STRIPE_SECRET: no se podrá cobrar');
        $this->comprobar(filled(config('cashier.webhook.secret')), 'Webhook de Stripe con su clave', null, 'Falta STRIPE_WEBHOOK_SECRET: los pagos no se confirmarán solos');
        $this->comprobar(filled(config('services.google.client_id')), 'Acceso con Google configurado', null, 'Falta GOOGLE_CLIENT_ID: el botón de Google no funcionará');

        $this->comprobar(! config('legal.borrador'), 'Textos legales marcados como revisados', null, 'Los textos legales muestran "documento plantilla" (LEGAL_BORRADOR): quítalo cuando los revise un profesional');
        $this->comprobar(blank(config('deploy.token')), 'Ruta de despliegue cerrada', null, 'DEPLOY_TOKEN está puesto: vacíalo cuando no estés desplegando');

        $this->newLine();
        $this->line($this->fallos === 0 ? 'Todo lo imprescindible está bien.' : "Hay {$this->fallos} cosas que corregir (marcadas con FALLO).");

        return $this->fallos === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Escribe OK si se cumple; si no, FALLO (imprescindible) o AVISO (recomendable).
     */
    protected function comprobar(bool $cumple, string $bien, ?string $fallo, ?string $aviso = null): void
    {
        if ($cumple) {
            $this->line('OK     '.$bien);
        } elseif ($fallo !== null) {
            $this->fallos++;
            $this->line('FALLO  '.$fallo);
        } else {
            $this->line('AVISO  '.$aviso);
        }
    }
}
