<?php

namespace App\Models\Scopes;

use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filtro global que limita cada consulta a la empresa actual. Sin empresa identificada no
 * devuelve ninguna fila, para que una empresa no pueda ver datos de otra.
 */
class EmpresaScope implements Scope
{
    /**
     * Añade "WHERE empresa_id = <empresa actual>" a la consulta (o la anula si no hay
     * empresa).
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Tenant::check()) {
            $builder->where($model->getTable().'.empresa_id', Tenant::id());
        } else {
            // Sin tenant identificado (p.ej. fuera de una petición web normal),
            // no se debe poder ver ninguna fila de ninguna empresa por defecto.
            $builder->whereRaw('1 = 0');
        }
    }
}
