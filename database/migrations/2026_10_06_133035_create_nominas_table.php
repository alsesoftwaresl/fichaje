<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nóminas en PDF que sube la empresa (admin o contable) y que cada
     * empleado ve y descarga. El archivo vive en el disco privado (nunca
     * accesible por URL pública); aquí solo van los datos para localizarlo.
     */
    public function up(): void
    {
        Schema::create('nominas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('periodo'); // primer día del mes al que corresponde
            $table->string('descripcion')->nullable(); // p. ej. "Paga extra de Navidad"
            $table->string('ruta');
            $table->string('nombre_original');
            $table->unsignedInteger('tamano');
            $table->foreignId('subida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('descargada_en')->nullable(); // primera vez que la abrió el empleado
            $table->timestamps();

            $table->index(['user_id', 'periodo']);
            $table->index(['empresa_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nominas');
    }
};
