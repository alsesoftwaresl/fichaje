<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla append-only: corregir un fichaje nunca modifica el original,
        // crea un registro nuevo trazable (quién, cuándo, por qué).
        Schema::create('fichaje_correcciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('fichaje_original_id')->constrained('fichajes')->restrictOnDelete();
            $table->timestamp('fecha_hora_corregida');
            $table->text('motivo');
            $table->foreignId('corregido_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['empresa_id', 'fichaje_original_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichaje_correcciones');
    }
};
