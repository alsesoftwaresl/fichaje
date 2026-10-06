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
        'storage:link',
        'config:clear',
        'route:clear',
        'view:clear',
        'cache:clear',
        'optimize:clear',
    ];

    public function ejecutar(Request $request): Response
    {
        $token = config('deploy.token');

        abort_if(! $token, 404);
        abort_unless(hash_equals($token, (string) $request->query('token')), 404);

        $comando = (string) $request->query('cmd', 'migrate');

        abort_unless(in_array($comando, self::COMANDOS_PERMITIDOS, true), 422, 'Comando no permitido.');

        $opciones = $comando === 'migrate' ? ['--force' => true] : [];

        Artisan::call($comando, $opciones);

        return response(Artisan::output(), 200)->header('Content-Type', 'text/plain');
    }
}
