<?php

namespace App\Application\Admin\ViewModels;

class PriceListIndexViewModel
{
    /**
     * @param array<int, array{id:int, name:string, sex_label:string, price_formatted:string, has_offer:bool}> $animals
     */
    public function __construct(
        public readonly array $animals,
        public readonly string $printUrl,
        public readonly string $search
    ) {
    }
}
