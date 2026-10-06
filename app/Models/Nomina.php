<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Nómina en PDF de un empleado, subida por un admin o contable. El empleado siempre puede
 * verla y descargarla, aunque la empresa no tenga suscripción.
 */
class Nomina extends Model
{
    use BelongsToTenant, HasFactory;

    // Disco privado (storage/app/private): los PDF nunca se sirven por URL
    // pública, solo a través de NominaController, que comprueba quién pide.
    const DISCO = 'local';

    /**
     * Nombre de la tabla.
     */
    protected $table = 'nominas';

    /**
     * Campos que se pueden rellenar. "ruta" es la del PDF dentro del disco privado.
     */
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

    /**
     * Periodo (mes de la nómina) y fecha de primera descarga como Carbon.
     */
    protected function casts(): array
    {
        return [
            'periodo' => 'date',
            'descargada_en' => 'datetime',
        ];
    }

    /**
     * Empleado dueño de la nómina.
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Quien subió el archivo.
     */
    public function subidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subida_por');
    }

    /**
     * Título para listados: "Mes Año" y la descripción si la hay.
     */
    public function titulo(): string
    {
        $mes = ucfirst($this->periodo->translatedFormat('F Y'));

        return $this->descripcion ? $mes.' — '.$this->descripcion : $mes;
    }

    /**
     * Nombre con el que se descarga el PDF (periodo + nombre del empleado).
     */
    public function nombreDescarga(): string
    {
        return 'Nomina '.$this->periodo->format('Y-m').' - '.str($this->empleado->name)->slug().'.pdf';
    }

    /**
     * Borra el PDF del disco privado (el registro se borra aparte).
     */
    public function borrarArchivo(): void
    {
        Storage::disk(static::DISCO)->delete($this->ruta);
    }
}
