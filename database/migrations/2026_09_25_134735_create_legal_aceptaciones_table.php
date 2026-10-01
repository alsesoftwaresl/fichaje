<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_aceptaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('documento', ['terminos_condiciones', 'encargo_tratamiento']);
            $table->string('version');
            $table->timestamp('aceptado_at');
            $table->string('ip_address', 45)->nullable();

            $table->index(['empresa_id', 'documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_aceptaciones');
    }
};
