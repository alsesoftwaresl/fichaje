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

    /**
     * $usuario es opcional: por defecto se usa quien tenga la sesión
     * iniciada (Auth::user()). Hace falta pasarlo explícito en flujos sin
     * nadie autenticado todavía — p. ej. el registro público de empresa,
     * donde el admin_empresa que hay que registrar como autor es justo el
     * que se acaba de crear en la misma operación.
     */
    public static function registrar(string $accion, Model $objeto, ?array $detalles = null, ?User $usuario = null): self
    {
        $usuario ??= Auth::user();

        return static::create([
            'empresa_id' => $usuario?->empresa_id,
            'user_id' => $usuario?->id,
            'accion' => $accion,
            'objeto_tipo' => $objeto::class,
            'objeto_id' => $objeto->getKey(),
            'detalles' => $detalles,
        ]);
    }
}
