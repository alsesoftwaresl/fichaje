<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // false mientras el admin no haya elegido cómo se ficha en el kiosco: se le
            // pregunta una vez en el panel. Cuenta como "elegido" guardar cualquier opción.
            $table->boolean('kiosko_modo_elegido')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('kiosko_modo_elegido');
        });
    }
};
