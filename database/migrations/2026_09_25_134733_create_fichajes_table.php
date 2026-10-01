<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla append-only: el registro legal de jornada (RD-ley 8/2019) no se
        // edita ni se borra nunca. Las correcciones viven en fichaje_correcciones.
        Schema::create('fichajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('tipo', ['entrada', 'salida']);
            $table->timestamp('fecha_hora');
            $table->string('origen')->default('web');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['empresa_id', 'created_at']);
            $table->index(['empresa_id', 'user_id', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichajes');
    }
};
