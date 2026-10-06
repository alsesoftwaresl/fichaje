<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Nomina extends Model
{
    use BelongsToTenant, HasFactory;

    // Disco privado (storage/app/private): los PDF nunca se sirven por URL
    // pública, solo a través de NominaController, que comprueba quién pide.
    const DISCO = 'local';

    protected $table = 'nominas';

    protected $fillable = [
        'user_id',
        'periodo',
        'descripcion',
        'ruta',
        'nombre_original',
        'tamano',
        'subida_por',
        'descargada_en',
    ];

    protected function casts(): array
    {
        return [
            'periodo' => 'date',
            'descargada_en' => 'datetime',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subida_por');
    }

    public function titulo(): string
    {
        $mes = ucfirst($this->periodo->translatedFormat('F Y'));

        return $this->descripcion ? $mes.' — '.$this->descripcion : $mes;
    }

    public function nombreDescarga(): string
    {
        return 'Nomina '.$this->periodo->format('Y-m').' - '.str($this->empleado->name)->slug().'.pdf';
    }

    public function borrarArchivo(): void
    {
        Storage::disk(static::DISCO)->delete($this->ruta);
    }
}
