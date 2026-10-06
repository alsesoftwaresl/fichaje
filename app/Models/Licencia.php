<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Código de licencia gratuita. Lo crea el super_admin y lo canjea el admin
 * de una empresa; mientras la licencia esté vigente la empresa usa la app sin
 * suscripción en Stripe.
 */
class Licencia extends Model
{
    protected $fillable = ['codigo', 'nota', 'meses', 'max_usos', 'canjeable_hasta', 'activa'];

    /**
     * Tipos de cada columna.
     */
    protected function casts(): array
    {
        return [
            'meses' => 'integer',
            'max_usos' => 'integer',
            'usos' => 'integer',
            'canjeable_hasta' => 'date',
            'activa' => 'boolean',
        ];
    }

    /**
     * Empresas que han canjeado este código.
     */
    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }

    /** Sin 0/O ni 1/I/L para que se pueda dictar o copiar sin errores. */
    public static function generarCodigo(): string
    {
        $alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $codigo = 'ACH';
            for ($grupo = 0; $grupo < 2; $grupo++) {
                $codigo .= '-';
                for ($i = 0; $i < 4; $i++) {
                    $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
                }
            }
        } while (static::where('codigo', $codigo)->exists());

        return $codigo;
    }

    /**
     * Quita espacios y pasa a mayúsculas el código tecleado.
     */
    public static function normalizar(string $codigo): string
    {
        return Str::upper(trim($codigo));
    }

    /** Motivo por el que no se puede canjear, o null si se puede. */
    public function motivoNoCanjeable(): ?string
    {
        if (! $this->activa) {
            return 'Este código está desactivado.';
        }

        if ($this->canjeable_hasta && $this->canjeable_hasta->endOfDay()->isPast()) {
            return 'Este código ha caducado.';
        }

        if ($this->usos >= $this->max_usos) {
            return 'Este código ya se ha utilizado.';
        }

        return null;
    }

    /**
     * Asigna la licencia a la empresa. Se bloquea la fila para que dos
     * canjes a la vez no superen max_usos.
     */
    public static function canjear(string $codigo, Empresa $empresa): ?string
    {
        return DB::transaction(function () use ($codigo, $empresa) {
            $licencia = static::where('codigo', static::normalizar($codigo))->lockForUpdate()->first();

            if (! $licencia) {
                return 'El código no es válido.';
            }

            if ($motivo = $licencia->motivoNoCanjeable()) {
                return $motivo;
            }

            $licencia->increment('usos');

            $empresa->forceFill([
                'licencia_id' => $licencia->id,
                'licencia_hasta' => $licencia->meses ? now()->addMonths($licencia->meses)->toDateString() : null,
            ])->save();

            return null;
        });
    }
}
