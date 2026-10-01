<?php

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
            'tenant' => \App\Http\Middleware\IdentifyTenant::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'suscripcion' => \App\Http\Middleware\RequireSuscripcion::class,
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
