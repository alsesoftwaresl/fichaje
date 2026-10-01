<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait PaginaColecciones
{
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
