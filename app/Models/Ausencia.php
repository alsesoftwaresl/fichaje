<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ausencia extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'estado',
        'resuelto_por',
        'resuelto_en',
        'motivo_rechazo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'resuelto_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    public function cubre(\Illuminate\Support\Carbon $fecha): bool
    {
        return $this->estado === 'aprobada'
            && $fecha->between($this->fecha_inicio, $this->fecha_fin);
    }
}
