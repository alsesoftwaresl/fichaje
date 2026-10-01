<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalAceptacion extends Model
{
    protected $table = 'legal_aceptaciones';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'user_id',
        'documento',
        'version',
        'aceptado_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'aceptado_at' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
