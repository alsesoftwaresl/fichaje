<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Aviso de salida dentro de la jornada (médico, gestión...): día, hora de salida y de
 * vuelta y motivo libre. Se usa para no marcar incidencia a quien tiene permiso. Se anula,
 * no se borra.
 */
class Cita extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * Nombre de la tabla.
     */
    protected $table = 'citas';

    /**
     * Campos que se pueden rellenar.
     */
    protected $fillable = [
        'user_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'anulada_en',
        'anulada_por',
    ];

    /**
     * Fechas como Carbon.
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'anulada_en' => 'datetime',
        ];
    }

    /**
     * Empleado que avisa.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Solo los avisos que no se han anulado: Cita::vigentes().
     */
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

    /**
     * Fecha y hora exactas en que empieza la salida.
     */
    public function desde(): Carbon
    {
        return Carbon::parse($this->fecha->toDateString().' '.$this->hora_inicio);
    }

    /**
     * Fecha y hora exactas en que debe volver.
     */
    public function hasta(): Carbon
    {
        return Carbon::parse($this->fecha->toDateString().' '.$this->hora_fin);
    }

    /**
     * Texto "HH:MM–HH:MM" para mostrar en pantalla.
     */
    public function rango(): string
    {
        return substr($this->hora_inicio, 0, 5).'–'.substr($this->hora_fin, 0, 5);
    }
}
