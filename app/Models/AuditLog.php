<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'empresa_id',
        'user_id',
        'accion',
        'objeto_tipo',
        'objeto_id',
        'detalles',
    ];

    protected function casts(): array
    {
        return [
            'detalles' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function registrar(string $accion, Model $objeto, ?array $detalles = null): self
    {
        return static::create([
            'empresa_id' => Auth::user()?->empresa_id,
            'user_id' => Auth::id(),
            'accion' => $accion,
            'objeto_tipo' => $objeto::class,
            'objeto_id' => $objeto->getKey(),
            'detalles' => $detalles,
        ]);
    }
}
