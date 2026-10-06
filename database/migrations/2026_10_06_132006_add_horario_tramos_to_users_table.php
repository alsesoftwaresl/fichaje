<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tramos del horario esperado para turnos partidos, p. ej.
     * [{"entrada":"09:00","salida":"14:00"},{"entrada":"16:00","salida":"19:00"}].
     * Solo se rellena cuando hay más de un tramo: con un único tramo se siguen
     * usando hora_entrada_esperada / hora_salida_esperada (que siempre guardan
     * la primera entrada y la última salida del día).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('horario_tramos')->nullable()->after('dias_laborables');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('horario_tramos');
        });
    }
};
