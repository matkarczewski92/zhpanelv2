<?php

namespace App\Application\Admin\ViewModels;

class PriceListPrintViewModel
{
    /**
     * @param array<int, array{id:int, name:string, sex_label:string, price_formatted:string}> $animals
     */
    public function __construct(
        public readonly array $animals,
        public readonly int $totalAnimals,
        public readonly string $printedAt
    ) {
    }
}

