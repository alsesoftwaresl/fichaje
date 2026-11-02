<?php

use App\Services\IntegridadRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Huella de integridad en fichajes y correcciones (ver IntegridadRegistro) y sellado de
     * los que ya existen, en orden, para que la cadena quede completa desde el principio.
     *
     * Es la única vez que se escribe en filas ya creadas de estas tablas: se hace con
     * DB::table (sin los eventos que lo prohíben en los modelos) y solo rellena la huella.
     */
    public function up(): void
    {
        Schema::table('fichajes', function (Blueprint $table) {
            $table->char('huella', 64)->nullable()->after('user_agent');
        });

        Schema::table('fichaje_correcciones', function (Blueprint $table) {
            $table->char('huella', 64)->nullable()->after('corregido_por');
        });

        $anteriorPorEmpleado = [];

        foreach (DB::table('fichajes')->orderBy('id')->lazyById(500) as $fila) {
            $huella = IntegridadRegistro::huellaFichaje([
                'empresa_id' => $fila->empresa_id,
                'user_id' => $fila->user_id,
                'tipo' => $fila->tipo,
                'fecha_hora' => substr((string) $fila->fecha_hora, 0, 19),
                'origen' => $fila->origen,
                'registrado_por' => $fila->registrado_por,
                'motivo' => $fila->motivo,
                'ip_address' => $fila->ip_address,
            ], $anteriorPorEmpleado[$fila->user_id] ?? null);

            // La hora se reescribe con su propio valor a propósito: así ninguna base de datos
            // la cambia sola al actualizar la fila (ver la migración anterior).
            DB::table('fichajes')->where('id', $fila->id)->update(['huella' => $huella, 'fecha_hora' => DB::raw('fecha_hora')]);
            $anteriorPorEmpleado[$fila->user_id] = $huella;
        }

        $anteriorPorEmpresa = [];

        foreach (DB::table('fichaje_correcciones')->orderBy('id')->lazyById(500) as $fila) {
            $huella = IntegridadRegistro::huellaCorreccion([
                'empresa_id' => $fila->empresa_id,
                'fichaje_original_id' => $fila->fichaje_original_id,
                'fecha_hora_corregida' => substr((string) $fila->fecha_hora_corregida, 0, 19),
                'motivo' => $fila->motivo,
                'corregido_por' => $fila->corregido_por,
            ], $anteriorPorEmpresa[$fila->empresa_id] ?? null);

            DB::table('fichaje_correcciones')->where('id', $fila->id)->update(['huella' => $huella, 'fecha_hora_corregida' => DB::raw('fecha_hora_corregida')]);
            $anteriorPorEmpresa[$fila->empresa_id] = $huella;
        }
    }

    public function down(): void
    {
        Schema::table('fichajes', function (Blueprint $table) {
            $table->dropColumn('huella');
        });

        Schema::table('fichaje_correcciones', function (Blueprint $table) {
            $table->dropColumn('huella');
        });
    }
};
