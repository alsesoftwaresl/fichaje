<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Corrección de la hora de un fichaje hecha por un admin. También es append-only: guarda
 * la hora nueva, el motivo y quién la hizo, y el fichaje original queda intacto para la
 * inspección.
 */
class FichajeCorreccion extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * Nombre de la tabla (el plural español no sigue la regla de Laravel).
     */
    protected $table = 'fichaje_correcciones';

    /**
     * Solo-inserción: sin columna updated_at.
     */
    const UPDATED_AT = null;

    /**
     * Campos que se pueden rellenar.
     */
    protected $fillable = [
        'fichaje_original_id',
        'fecha_hora_corregida',
        'motivo',
        'corregido_por',
    ];

    /**
     * La hora corregida se maneja como fecha/hora.
     */
    protected function casts(): array
    {
        return [
            'fecha_hora_corregida' => 'datetime',
        ];
    }

    /**
     * Bloquea cualquier intento de modificar o borrar una corrección.
     */
    protected static function booted(): void
    {
        parent::booted();

        static::updating(function () {
            throw new \RuntimeException('Las correcciones son append-only: no se pueden modificar.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Las correcciones son append-only: no se pueden eliminar.');
        });
    }

    /**
     * Fichaje que se corrige.
     */
    public function fichajeOriginal(): BelongsTo
    {
        return $this->belongsTo(Fichaje::class, 'fichaje_original_id');
    }

    /**
     * Admin que hizo la corrección.
     */
    public function corregidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corregido_por');
    }
}
