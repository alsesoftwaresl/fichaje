<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Horario esperado simple: mismas horas todos los días marcados
            // como laborables. Nulo = sin horario configurado, no genera
            // incidencias de retraso/horas de más.
            $table->time('hora_entrada_esperada')->nullable()->after('dni_nie');
            $table->time('hora_salida_esperada')->nullable()->after('hora_entrada_esperada');
            // Array de días ISO (1=lunes .. 7=domingo), ej. [1,2,3,4,5].
            $table->json('dias_laborables')->nullable()->after('hora_salida_esperada');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['hora_entrada_esperada', 'hora_salida_esperada', 'dias_laborables']);
        });
    }
};
