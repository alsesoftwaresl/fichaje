<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una marca de entrada o salida de un empleado. Es el registro legal de jornada, por eso
 * es append-only: nunca se modifica ni se borra (lo impide este modelo y también los
 * permisos de MySQL). Los errores se arreglan con FichajeCorreccion.
 */
class Fichaje extends Model
{
    use BelongsToTenant, HasFactory;

    // Append-only: no hay updated_at, es una tabla de solo-inserción.
    const UPDATED_AT = null;

    /**
     * Campos que se pueden rellenar. empresa_id lo pone BelongsToTenant, nunca el
     * formulario.
     */
    protected $fillable = [
        'user_id',
        'tipo',
        'fecha_hora',
        'origen',
        'ip_address',
        'user_agent',
    ];

    /**
     * La hora se maneja como fecha/hora (Carbon).
     */
    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    /**
     * Bloquea cualquier intento de modificar o borrar un fichaje.
     */
    protected static function booted(): void
    {
        parent::booted();

        static::updating(function () {
            throw new \RuntimeException('Los fichajes son append-only: no se pueden modificar.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Los fichajes son append-only: no se pueden eliminar.');
        });
    }

    /**
     * Empleado al que pertenece la marca.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Empresa a la que pertenece la marca.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Correcciones registradas sobre esta marca.
     */
    public function correcciones(): HasMany
    {
        return $this->hasMany(FichajeCorreccion::class, 'fichaje_original_id');
    }
}
