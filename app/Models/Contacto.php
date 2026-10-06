<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mensaje enviado desde el formulario público de contacto. No pertenece a
 * ninguna empresa: lo lee el super admin.
 */
class Contacto extends Model
{
    protected $table = 'contactos';

    /** Motivos que puede elegir quien escribe: clave => texto visible. */
    const ASUNTOS = [
        'informacion' => 'Quiero información',
        'soporte' => 'Soporte técnico',
        'facturacion' => 'Facturación',
        'urgente' => 'Incidencia urgente',
    ];

    protected $fillable = ['nombre', 'email', 'empresa', 'asunto', 'mensaje', 'ip_address', 'leido_en'];

    protected function casts(): array
    {
        return [
            'leido_en' => 'datetime',
        ];
    }

    /** Texto legible del motivo ("Soporte técnico"). */
    public function asuntoTexto(): string
    {
        return self::ASUNTOS[$this->asunto] ?? $this->asunto;
    }

    /** true si es una incidencia que hay que atender con prioridad. */
    public function esUrgente(): bool
    {
        return $this->asunto === 'urgente';
    }
}
