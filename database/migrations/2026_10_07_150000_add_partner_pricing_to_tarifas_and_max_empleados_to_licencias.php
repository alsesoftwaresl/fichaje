<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Precios del plan para asesorías (partners), editables por el super admin.
        Schema::table('tarifas', function (Blueprint $table) {
            // Lo que paga la asesoría por cada licencia (empresa cliente) al mes.
            $table->decimal('partner_precio_licencia', 8, 2)->default(6.00);
            // Empleados que incluye cada licencia.
            $table->unsignedInteger('partner_empleados_incluidos')->default(5);
            // Precio por empleado a partir de los incluidos.
            $table->decimal('partner_precio_empleado_extra', 8, 2)->default(1.00);
            // Mínimo de licencias que compra una asesoría.
            $table->unsignedInteger('partner_licencias_minimas')->default(5);
            // Precio de venta recomendado al cliente final (solo informativo).
            $table->decimal('partner_pvp_recomendado', 8, 2)->default(9.90);
        });

        // Tope de empleados activos de una empresa con esta licencia (null = sin tope).
        Schema::table('licencias', function (Blueprint $table) {
            $table->unsignedInteger('max_empleados')->nullable()->after('max_usos');
        });
    }

    public function down(): void
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->dropColumn('max_empleados');
        });

        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn([
                'partner_precio_licencia',
                'partner_empleados_incluidos',
                'partner_precio_empleado_extra',
                'partner_licencias_minimas',
                'partner_pvp_recomendado',
            ]);
        });
    }
};
