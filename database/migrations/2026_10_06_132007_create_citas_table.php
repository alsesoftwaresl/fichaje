<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avisos de salida dentro de la jornada (médico, otra cita...): el
     * empleado avisa de que se ausenta de hora_inicio a hora_fin un día
     * concreto. Surten efecto al momento (no hay aprobación) y el admin puede
     * anularlos; una cita anulada deja de justificar nada.
     */
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->text('motivo')->nullable();
            $table->timestamp('anulada_en')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['empresa_id', 'fecha']);
            $table->index(['user_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
