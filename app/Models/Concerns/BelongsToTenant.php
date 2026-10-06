<?php

namespace App\Models\Concerns;

use App\Models\Scopes\EmpresaScope;
use App\Support\Tenant;

/**
 * Aísla los datos por empresa (multi-tenant): añade el filtro automático por empresa_id
 * a toda consulta del modelo y fija empresa_id al crear. Lo usan los modelos con datos de
 * empresa (fichajes, ausencias, nóminas...).
 */
trait BelongsToTenant
{
    /**
     * Se ejecuta solo al arrancar el modelo: registra el scope y el relleno de empresa_id.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new EmpresaScope);

        // El empresa_id nunca viene del request/formulario: siempre se fija
        // aquí desde el tenant actual, para que sea imposible crear (o mover)
        // un registro "hacia" otra empresa aunque el formulario esté manipulado.
        static::creating(function ($model) {
            if (! Tenant::check()) {
                throw new \RuntimeException('No se puede crear '.static::class.' sin un tenant identificado.');
            }

            $model->empresa_id = Tenant::id();
        });
    }
}
