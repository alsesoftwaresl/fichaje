<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Token no adivinable que identifica la URL pública del kiosco de
            // esta empresa (no se expone el ID numérico para evitar enumeración).
            $table->string('kiosko_token', 40)->nullable()->unique()->after('activa');
        });

        Schema::table('users', function (Blueprint $table) {
            // Hash determinista (HMAC) del PIN de fichaje en kiosco: permite
            // búsqueda exacta indexada sin guardar el PIN en claro. Es un
            // mecanismo aparte de la contraseña (menor entropía, pensado solo
            // para identificarse en un dispositivo físico compartido).
            $table->string('pin_hash', 64)->nullable()->after('password');
            $table->unique(['empresa_id', 'pin_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'pin_hash']);
            $table->dropColumn('pin_hash');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('kiosko_token');
        });
    }
};
