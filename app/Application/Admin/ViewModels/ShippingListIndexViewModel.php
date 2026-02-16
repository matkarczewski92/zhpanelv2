<?php

namespace App\Application\Admin\ViewModels;

class ShippingListIndexViewModel
{
    /**
     * @param array<int, array{id:int, name:string, sex_label:string, type_name:string, category_id:int, has_offer:bool}> $animals
     */
    public function __construct(
        public readonly array $animals,
        public readonly string $printUrl
    ) {
    }
}
