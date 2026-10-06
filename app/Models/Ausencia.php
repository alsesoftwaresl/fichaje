<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitud de vacaciones, baja médica u otra ausencia. La pide el empleado (estado
 * pendiente) y un admin la aprueba o la rechaza.
 */
class Ausencia extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * Campos que se pueden rellenar.
     */
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

    /**
     * Fechas como Carbon.
     */
    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'resuelto_en' => 'datetime',
        ];
    }

    /**
     * Empleado que la solicita.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Admin que la aprobó o rechazó.
     */
    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    /**
     * Indica si la ausencia está aprobada y el día dado cae dentro de sus fechas.
     */
    public function cubre(\Illuminate\Support\Carbon $fecha): bool
    {
        return $this->estado === 'aprobada'
            && $fecha->between($this->fecha_inicio, $this->fecha_fin);
    }
}
