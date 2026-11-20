<?php

namespace App\Domain\Admin;

use Illuminate\Pagination\LengthAwarePaginator;

/** Formato único de lista paginada nas páginas do admin. */
final class Paginacao
{
    /**
     * @template T
     *
     * @param  LengthAwarePaginator<int, T>  $pagina
     * @param  callable(T): array<string, mixed>  $mapa
     * @return array{dados: list<array<string, mixed>>, pagina: int, por_pagina: int, total: int, ultima_pagina: int}
     */
    public static function paraProps(LengthAwarePaginator $pagina, callable $mapa): array
    {
        return [
            'dados' => array_values(array_map($mapa, $pagina->items())),
            'pagina' => $pagina->currentPage(),
            'por_pagina' => $pagina->perPage(),
            'total' => $pagina->total(),
            'ultima_pagina' => $pagina->lastPage(),
        ];
    }
}
