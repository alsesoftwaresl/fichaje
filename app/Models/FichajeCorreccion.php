<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FichajeCorreccion extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'fichaje_correcciones';

    const UPDATED_AT = null;

    protected $fillable = [
        'fichaje_original_id',
        'fecha_hora_corregida',
        'motivo',
        'corregido_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora_corregida' => 'datetime',
        ];
    }

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

    public function fichajeOriginal(): BelongsTo
    {
        return $this->belongsTo(Fichaje::class, 'fichaje_original_id');
    }

    public function corregidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corregido_por');
    }
}
