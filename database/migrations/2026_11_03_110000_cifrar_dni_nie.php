<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cifra los DNI/NIE guardados y añade su huella (dni_hash) para buscarlos y para la
     * unicidad por empresa (ver User::dniNie()). Un TEXT cifrado no se puede indexar ni
     * comparar, así que los índices pasan de dni_nie a dni_hash.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'dni_nie']);
            $table->dropIndex(['dni_nie']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('dni_nie')->nullable()->change();
            $table->char('dni_hash', 64)->nullable()->after('dni_nie');
        });

        foreach (DB::table('users')->whereNotNull('dni_nie')->orderBy('id')->lazyById(200) as $fila) {
            $dni = User::normalizarDni($fila->dni_nie);

            DB::table('users')->where('id', $fila->id)->update([
                'dni_nie' => Crypt::encryptString($dni),
                'dni_hash' => User::huellaDni($dni),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['empresa_id', 'dni_hash']);
            $table->index('dni_hash');
        });
    }

    /**
     * Vuelve a guardar el DNI en claro (solo para deshacer en desarrollo).
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'dni_hash']);
            $table->dropIndex(['dni_hash']);
        });

        foreach (DB::table('users')->whereNotNull('dni_nie')->orderBy('id')->lazyById(200) as $fila) {
            DB::table('users')->where('id', $fila->id)->update(['dni_nie' => Crypt::decryptString($fila->dni_nie)]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dni_hash');
            $table->string('dni_nie')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['empresa_id', 'dni_nie']);
            $table->index('dni_nie');
        });
    }
};
