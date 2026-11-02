<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * En MySQL/MariaDB sin "explicit_defaults_for_timestamp" (lo habitual en hosting
     * compartido), la primera columna TIMESTAMP NOT NULL de una tabla se crea con
     * "ON UPDATE CURRENT_TIMESTAMP": cualquier UPDATE de la fila la pone a la hora actual.
     * En estas tres columnas eso borraría una prueba legal (la hora del fichaje, la de la
     * corrección y la de la aceptación de los textos legales), así que pasan a DATETIME,
     * que nunca cambia sola. El valor que muestran se conserva tal cual.
     *
     * Solo hace falta en MySQL/MariaDB; SQLite (pruebas) no tiene ese comportamiento.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE fichajes MODIFY fecha_hora DATETIME NOT NULL');
        DB::statement('ALTER TABLE fichaje_correcciones MODIFY fecha_hora_corregida DATETIME NOT NULL');
        DB::statement('ALTER TABLE legal_aceptaciones MODIFY aceptado_at DATETIME NOT NULL');
    }

    /**
     * Vuelta a TIMESTAMP sin la actualización automática (nunca se recupera ese
     * comportamiento, que es justo el problema).
     */
    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE fichajes MODIFY fecha_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE fichaje_correcciones MODIFY fecha_hora_corregida TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE legal_aceptaciones MODIFY aceptado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
};
