<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Cómo se identifican los empleados en el kiosco: "pin", "dni" o "ambos".
            $table->string('kiosko_modo', 8)->default('pin');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('kiosko_modo');
        });
    }
};
