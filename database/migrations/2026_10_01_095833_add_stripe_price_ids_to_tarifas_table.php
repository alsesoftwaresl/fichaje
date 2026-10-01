<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            // Se recrean en Stripe cada vez que se guardan tarifas nuevas
            // (los Price de Stripe son inmutables en el importe una vez
            // creados), así que aquí solo guardamos los IDs vigentes.
            $table->string('stripe_product_id')->nullable()->after('precio_empleado_extra');
            $table->string('stripe_price_base_id')->nullable()->after('stripe_product_id');
            $table->string('stripe_price_extra_id')->nullable()->after('stripe_price_base_id');
        });
    }

    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn(['stripe_product_id', 'stripe_price_base_id', 'stripe_price_extra_id']);
        });
    }
};
