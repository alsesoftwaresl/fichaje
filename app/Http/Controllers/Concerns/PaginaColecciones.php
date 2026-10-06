<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Pagina una colección ya calculada en memoria (p. ej. las jornadas agrupadas), que no se
 * puede paginar con ->paginate() de la base de datos.
 */
trait PaginaColecciones
{
    /**
     * Devuelve la página actual de la colección con los enlaces de paginación.
     */
    protected function paginar(Collection $items, int $porPagina = 20): LengthAwarePaginator
    {
        $pagina = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($pagina, $porPagina)->values(),
            $items->count(),
            $porPagina,
            $pagina,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }
}
