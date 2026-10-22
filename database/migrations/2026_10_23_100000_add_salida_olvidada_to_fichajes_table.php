<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fichajes', function (Blueprint $table) {
            // Solo en fichajes que añade la empresa (origen "empresa"), como la salida que el
            // empleado olvidó fichar: quién lo añadió y por qué. Los del empleado van vacíos.
            $table->foreignId('registrado_por')->nullable()->after('origen')->constrained('users')->nullOnDelete();
            $table->string('motivo', 500)->nullable()->after('registrado_por');
        });
    }

    public function down(): void
    {
        Schema::table('fichajes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registrado_por');
            $table->dropColumn('motivo');
        });
    }
};
