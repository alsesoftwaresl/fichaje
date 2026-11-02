<?php

use App\Models\Nomina;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Marca de nómina cifrada y cifrado de los PDF que ya estaban subidos (ver
     * Nomina::guardarArchivo). Cada archivo se reescribe cifrado en su misma ruta; si alguno
     * falta en el disco se deja como está y se avisa en el log.
     */
    public function up(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            $table->boolean('cifrada')->default(false)->after('tamano');
        });

        $disco = Storage::disk(Nomina::DISCO);

        foreach (DB::table('nominas')->where('cifrada', false)->orderBy('id')->lazyById(100) as $fila) {
            if (! $disco->exists($fila->ruta)) {
                logger()->warning("Nómina {$fila->id}: no está el archivo {$fila->ruta}, no se cifra.");

                continue;
            }

            $disco->put($fila->ruta, Crypt::encryptString($disco->get($fila->ruta)));
            DB::table('nominas')->where('id', $fila->id)->update(['cifrada' => true]);
        }
    }

    /**
     * Descifra los archivos y quita la marca (solo para deshacer en desarrollo).
     */
    public function down(): void
    {
        $disco = Storage::disk(Nomina::DISCO);

        foreach (DB::table('nominas')->where('cifrada', true)->orderBy('id')->lazyById(100) as $fila) {
            if ($disco->exists($fila->ruta)) {
                $disco->put($fila->ruta, Crypt::decryptString($disco->get($fila->ruta)));
            }
        }

        Schema::table('nominas', function (Blueprint $table) {
            $table->dropColumn('cifrada');
        });
    }
};
