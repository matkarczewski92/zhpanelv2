<?php

namespace App\Application\Admin\ViewModels;

class ShippingListPrintViewModel
{
    /**
     * @param array<int, array{
     *     type_name:string,
     *     total:int,
     *     animals:array<int, array{id:int, name:string, sex_label:string}>
     * }> $groups
     */
    public function __construct(
        public readonly array $groups,
        public readonly int $totalAnimals,
        public readonly string $printedAt
    ) {
    }
}

