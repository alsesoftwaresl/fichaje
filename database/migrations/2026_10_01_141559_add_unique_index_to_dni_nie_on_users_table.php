<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normaliza a mayúsculas antes de indexar como único: el mutator del
        // modelo ya lo hace para las filas nuevas, pero las existentes
        // podrían tener minúsculas y chocar al crear el índice.
        DB::statement('UPDATE users SET dni_nie = UPPER(dni_nie) WHERE dni_nie IS NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->unique('dni_nie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['dni_nie']);
        });
    }
};
