<?php

namespace App\Providers;

use App\Models\Empresa;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

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
    }
}
