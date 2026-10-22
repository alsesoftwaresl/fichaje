<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El DNI/NIE pasa de ser único en toda la app a serlo dentro de cada empresa: una
     * persona puede trabajar en dos empresas clientes, o cambiar de una a otra mientras la
     * primera la conserva de baja.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['empresa_id', 'dni_nie']);
            $table->dropUnique(['dni_nie']);
            // Para el login con DNI, que busca en todas las empresas.
            $table->index('dni_nie');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['dni_nie']);
            $table->unique('dni_nie');
            $table->dropUnique(['empresa_id', 'dni_nie']);
        });
    }
};
