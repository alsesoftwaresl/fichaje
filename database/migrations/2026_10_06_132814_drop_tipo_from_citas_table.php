<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El aviso ya no lleva "tipo" (médico / otra): el empleado explica con
     * sus palabras el motivo. Una base de datos nueva ya crea la tabla sin
     * esa columna; esta migración solo quita la columna donde ya existía.
     */
    public function up(): void
    {
        if (Schema::hasColumn('citas', 'tipo')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }
    }

    public function down(): void
    {
        // Irreversible a propósito: el tipo ya no existe en la aplicación.
    }
};
