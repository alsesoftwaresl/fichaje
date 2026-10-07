<?php

namespace App\Providers;

use App\Support\Avisos;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use App\Models\Empresa;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

/**
 * Configuración de arranque de la aplicación (servicios y ajustes globales).
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lo facturable es la empresa (tenant), no un usuario individual —
        // Cashier asume User por defecto.
        Cashier::useCustomerModel(Empresa::class);

        // Los correos de contraseña y verificación llevan saludo personal y la empresa.
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false));
            $minutos = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return Avisos::mensaje($notifiable)
                ->subject(__('Reset Password Notification'))
                ->line(__('You are receiving this email because we received a password reset request for your account.'))
                ->action(__('Reset Password'), $url)
                ->line(__('This password reset link will expire in :count minutes.', ['count' => $minutos]))
                ->line(__('If you did not request a password reset, no further action is required.'));
        });

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return Avisos::mensaje($notifiable)
                ->subject(__('Verify Email Address'))
                ->line(__('Please click the button below to verify your email address.'))
                ->action(__('Verify Email Address'), $url)
                ->line(__('If you did not create an account, no further action is required.'));
        });
    }
}
