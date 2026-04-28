<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApiPagination
{
    /**
     * @return array{data: array, meta: array{page:int, per_page:int, total:int}}
     */
    public static function format(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->getCollection()->values()->all(),
            'meta' => [
                'page' => (int) $paginator->currentPage(),
                'per_page' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
            ],
        ];
    }
}

