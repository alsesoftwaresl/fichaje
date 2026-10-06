<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permiso "contable": quien lo tiene (además de los admin_empresa) puede
     * subir y gestionar las nóminas de la empresa, sin ser administrador.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('gestiona_nominas')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gestiona_nominas');
        });
    }
};
