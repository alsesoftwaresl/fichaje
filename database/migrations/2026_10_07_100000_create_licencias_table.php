<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Códigos de licencia gratuita que crea el super_admin y canjea una
        // empresa desde Facturación (sin pasar por Stripe).
        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 32)->unique();
            $table->string('nota')->nullable();
            // Meses de licencia desde el canje; null = sin caducidad.
            $table->unsignedSmallInteger('meses')->nullable();
            $table->unsignedInteger('max_usos')->default(1);
            $table->unsignedInteger('usos')->default(0);
            // Fecha límite para canjear el código (null = sin límite).
            $table->date('canjeable_hasta')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->foreignId('licencia_id')->nullable()->constrained('licencias')->nullOnDelete();
            // Último día de la licencia; null con licencia_id = sin caducidad.
            $table->date('licencia_hasta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('licencia_id');
            $table->dropColumn('licencia_hasta');
        });

        Schema::dropIfExists('licencias');
    }
};
