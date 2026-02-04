<?php

namespace App\Application\Admin\ViewModels;

class ShippingListIndexViewModel
{
    /**
     * @param array<int, array{id:int, name:string, sex_label:string, type_name:string}> $animals
     */
    public function __construct(
        public readonly array $animals,
        public readonly string $printUrl
    ) {
    }
}

