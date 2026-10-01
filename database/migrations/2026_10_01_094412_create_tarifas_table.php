<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de una sola fila (singleton): la tarifa es global para todo
        // el SaaS, no por empresa. Editable solo por super_admin.
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->decimal('precio_base_mensual', 8, 2);
            $table->unsignedInteger('empleados_incluidos');
            $table->decimal('precio_empleado_extra', 8, 2);
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Valores de PRUEBA: cámbialos desde "Tarifas" en el panel de super_admin
        // en cuanto tengas los precios reales.
        DB::table('tarifas')->insert([
            'precio_base_mensual' => 29.00,
            'empleados_incluidos' => 5,
            'precio_empleado_extra' => 1.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
