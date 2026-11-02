<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verificación en dos pasos (ver App\Support\Totp): el secreto y los códigos de
     * recuperación se guardan cifrados; confirmada_en indica que ya está activa (hasta
     * confirmar un primer código, el secreto es solo una propuesta).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('dos_pasos_secreto')->nullable()->after('pin_hash');
            $table->text('dos_pasos_recuperacion')->nullable()->after('dos_pasos_secreto');
            $table->dateTime('dos_pasos_confirmada_en')->nullable()->after('dos_pasos_recuperacion');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dos_pasos_secreto', 'dos_pasos_recuperacion', 'dos_pasos_confirmada_en']);
        });
    }
};
