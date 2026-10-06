<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mensajes del formulario público de contacto. Se guardan además de
        // enviarse por correo, para que no se pierda ninguno si el correo falla.
        Schema::create('contactos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('email', 255);
            $table->string('empresa', 160)->nullable();
            $table->string('asunto', 40);
            $table->text('mensaje');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('leido_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contactos');
    }
};
