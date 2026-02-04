<?php

namespace App\Application\Litters\ViewModels;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LittersIndexViewModel
{
    /**
     * @param array<int, array{id:int,name:string}> $maleParents
     * @param array<int, array{id:int,name:string}> $femaleParents
     * @param array<int, int> $plannedSeasons
     * @param array<int, array{value:int,label:string}> $categories
     * @param array<string, mixed> $filters
     * @param array<string, int> $counts
     */
    public function __construct(
        public readonly LengthAwarePaginator $actualLitters,
        public readonly LengthAwarePaginator $plannedLitters,
        public readonly LengthAwarePaginator $closedLitters,
        public readonly array $maleParents,
        public readonly array $femaleParents,
        public readonly array $plannedSeasons,
        public readonly array $categories,
        public readonly array $filters,
        public readonly array $counts
    ) {
    }
}
