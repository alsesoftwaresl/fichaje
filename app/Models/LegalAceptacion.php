<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Constancia de que una empresa aceptó un documento legal (términos, contrato de encargo
 * de tratamiento): qué versión, quién, cuándo y desde qué IP. Es la prueba del
 * consentimiento (RGPD).
 */
class LegalAceptacion extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'legal_aceptaciones';

    /**
     * No usa created_at/updated_at: la fecha de aceptación va en aceptado_at.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden rellenar.
     */
    protected $fillable = [
        'empresa_id',
        'user_id',
        'documento',
        'version',
        'aceptado_at',
        'ip_address',
    ];

    /**
     * La fecha de aceptación como Carbon.
     */
    protected function casts(): array
    {
        return [
            'aceptado_at' => 'datetime',
        ];
    }

    /**
     * Empresa que aceptó.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Usuario que lo aceptó.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
