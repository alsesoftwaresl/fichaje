<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForzarCambioPassword;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\ModuloNominas;
use App\Http\Middleware\PuedeGestionarNominas;
use App\Http\Middleware\RequireEmailVerificado;
use App\Http\Middleware\RequireSuscripcion;
use App\Http\Middleware\SeoRobots;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => IdentifyTenant::class,
            'role' => EnsureRole::class,
            'suscripcion' => RequireSuscripcion::class,
            'verificado' => RequireEmailVerificado::class,
            'gestor.nominas' => PuedeGestionarNominas::class,
            'modulo.nominas' => ModuloNominas::class,
        ]);

        // En modo mantenimiento solo pasa quien tenga la clave secreta, salvo el
        // webhook de Stripe: no es una persona, y si recibiera un 503 Stripe
        // acumularía reintentos fallidos (y se perderían avisos de pago).
        $middleware->preventRequestsDuringMaintenance(except: [
            'stripe/webhook',
        ]);

        // Quien entra con una contraseña temporal no puede hacer nada más
        // hasta elegir la suya.
        $middleware->web(append: [
            ForzarCambioPassword::class,
            SeoRobots::class,
        ]);

        // Stripe llama a este endpoint directamente, sin pasar por el
        // navegador ni tener un token CSRF de sesión — la autenticidad la
        // garantiza la firma que comprueba VerifyWebhookSignature.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
