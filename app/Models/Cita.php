<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Cita extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'user_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'anulada_en',
        'anulada_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'anulada_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeVigentes($query)
    {
        return $query->whereNull('anulada_en');
    }

    /**
     * Lo que escribió el empleado como motivo, recortado para listados.
     */
    public function descripcion(): string
    {
        return \Illuminate\Support\Str::limit($this->motivo ?: 'Salida avisada', 60);
    }

    public function desde(): Carbon
    {
        return Carbon::parse($this->fecha->toDateString().' '.$this->hora_inicio);
    }

    public function hasta(): Carbon
    {
        return Carbon::parse($this->fecha->toDateString().' '.$this->hora_fin);
    }

    public function rango(): string
    {
        return substr($this->hora_inicio, 0, 5).'–'.substr($this->hora_fin, 0, 5);
    }
}
