<?php

namespace App\Application\Finances\ViewModels;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinancesIndexViewModel
{
    /**
     * @param array<int, array{id:int, name:string}> $categories
     * @param array<int, array{id:int, name:string}> $feeds
     * @param array<int, array{id:int, name:string}> $animals
     * @param array<int, array{id:int, name:string, usage_count:int, can_delete:bool}> $categoryRows
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $summary
     * @param array<string, mixed> $charts
     */
    public function __construct(
        public readonly array $categories,
        public readonly array $feeds,
        public readonly array $animals,
        public readonly array $categoryRows,
        public readonly LengthAwarePaginator $transactions,
        public readonly array $filters,
        public readonly array $summary,
        public readonly array $charts
    ) {
    }
}
