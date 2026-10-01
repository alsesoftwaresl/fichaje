<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fichaje extends Model
{
    use BelongsToTenant, HasFactory;

    // Append-only: no hay updated_at, es una tabla de solo-inserción.
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'tipo',
        'fecha_hora',
        'origen',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function correcciones(): HasMany
    {
        return $this->hasMany(FichajeCorreccion::class, 'fichaje_original_id');
    }
}
