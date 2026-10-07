<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * Sustituto de "php artisan ..." para hosting sin SSH (p. ej. Loading.es,
 * que es hosting compartido con Plesk y no da terminal). Tras subir código
 * nuevo por FTP, se visita esta URL con el token y el comando que haga
 * falta en vez de conectarse por SSH.
 *
 * Inerte por defecto: si DEPLOY_TOKEN no está configurado en .env (nunca
 * debería estarlo en local ni en un hosting con SSH), la ruta da 404 pase
 * lo que pase. Solo expone comandos de una lista cerrada, nunca
 * "artisan:call" genérico, para no abrir una puerta a ejecutar cualquier
 * cosa aunque alguien adivinara el token.
 */
class DeployController extends Controller
{
    protected const COMANDOS_PERMITIDOS = [
        'migrate',
        // Crea el super_admin inicial (SUPER_ADMIN_EMAIL / SUPER_ADMIN_PASSWORD
        // del .env del servidor); es idempotente, se puede repetir sin duplicar.
        'db:seed',
        'storage:link',
        'config:clear',
        'route:clear',
        'view:clear',
        'cache:clear',
        'optimize:clear',
        // Modo mantenimiento: "down" exige una clave secreta (parametro
        // "secreto") para poder seguir entrando tu mientras el resto ve el 503.
        'down',
        'up',
        // Para la tarea programada de Plesk: ejecuta lo que toque (p. ej. el
        // resumen diario de incidencias). Se llama cada minuto.
        'schedule:run',
        // Envía un correo de prueba al correo de contacto y muestra el error si el SMTP falla.
        'correo:probar',
    ];

    /**
     * Ejecuta uno de los comandos permitidos si el token es correcto (404 en caso
     * contrario) y devuelve la salida como texto plano.
     */
    public function ejecutar(Request $request): Response
    {
        $token = config('deploy.token');

        abort_if(! $token, 404);
        abort_unless(hash_equals($token, (string) $request->query('token')), 404);

        $comando = (string) $request->query('cmd', 'migrate');

        abort_unless(in_array($comando, self::COMANDOS_PERMITIDOS, true), 422, 'Comando no permitido.');

        $opciones = match ($comando) {
            'migrate', 'db:seed' => ['--force' => true],
            'down' => ['--secret' => $this->secretoDeMantenimiento($request)],
            default => [],
        };

        Artisan::call($comando, $opciones);

        $salida = Artisan::output();

        if ($comando === 'down') {
            $salida .= "\nWeb en mantenimiento. Para entrar tú y probar, abre UNA vez en tu navegador:\n"
                .url($opciones['--secret'])."\n\n"
                ."Para reabrirla (con esa cookie ya puesta en tu navegador):\n"
                .url('/deploy/ejecutar').'?token=TU_TOKEN&cmd=up'."\n";
        }

        return response($salida, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Valida la clave secreta del modo mantenimiento: 12-64 caracteres seguros para ir en
     * una URL.
     */
    protected function secretoDeMantenimiento(Request $request): string
    {
        $secreto = (string) $request->query('secreto');

        // Va en la URL de acceso: solo caracteres seguros y lo bastante largo
        // para que no se pueda adivinar.
        abort_unless(preg_match('/^[A-Za-z0-9_-]{12,64}$/', $secreto) === 1, 422, 'Falta "secreto" (12-64 letras, numeros, - o _).');

        return $secreto;
    }
}
