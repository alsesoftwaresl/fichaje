<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            // Con qué modo de Stripe ("test" o "live") se crearon los ids guardados. Los
            // productos, precios y clientes de un modo no existen en el otro.
            $table->string('stripe_modo', 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn('stripe_modo');
        });
    }
};
