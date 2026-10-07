<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            // Todos los precios de la tarifa llevan el IVA incluido. Este es el
            // porcentaje que se desglosa en facturas y en la web.
            $table->decimal('iva_porcentaje', 5, 2)->default(21.00);
            // Tipo de IVA (incluido en el precio) creado en Stripe para aplicarlo a las suscripciones.
            $table->string('stripe_tax_rate_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn(['iva_porcentaje', 'stripe_tax_rate_id']);
        });
    }
};
