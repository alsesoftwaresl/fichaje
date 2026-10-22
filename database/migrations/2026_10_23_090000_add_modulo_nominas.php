<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            // Interruptor global. Apagado: Nóminas va incluido para todas las empresas (como hasta ahora).
            // Encendido: es un módulo opcional con cuota fija mensual por empresa (IVA incluido).
            $table->boolean('nominas_modulo_activo')->default(false);
            $table->decimal('nominas_precio_mensual', 8, 2)->default(5.00);
            $table->string('stripe_price_nominas_id')->nullable();
        });

        Schema::table('empresas', function (Blueprint $table) {
            // La empresa ha activado el módulo (se le cobra en su suscripción).
            $table->boolean('modulo_nominas')->default(false);
        });

        Schema::table('licencias', function (Blueprint $table) {
            // Si las empresas que canjean este código tienen Nóminas incluido.
            $table->boolean('incluye_nominas')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('licencias', fn (Blueprint $t) => $t->dropColumn('incluye_nominas'));
        Schema::table('empresas', fn (Blueprint $t) => $t->dropColumn('modulo_nominas'));
        Schema::table('tarifas', fn (Blueprint $t) => $t->dropColumn(['nominas_modulo_activo', 'nominas_precio_mensual', 'stripe_price_nominas_id']));
    }
};
